<?php

namespace App\Features\Payments\Services;

use App\Features\Payments\Enums\PaymentStatus;
use App\Features\Payments\Models\Payment;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    /**
     * Handle webhook dari Midtrans.
     * Mengembalikan true jika valid dan diproses, false jika invalid.
     */
    public function handleWebhook(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $serverKey = config('services.midtrans.server_key');
        $signatureKey = $payload['signature_key'] ?? '';

        // 1. Validasi Signature Key
        $expectedSignatureKey = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);
        
        if ($expectedSignatureKey !== $signatureKey) {
            Log::error('Invalid Midtrans signature key', [
                'order_id' => $orderId,
                'expected' => $expectedSignatureKey,
                'received' => $signatureKey
            ]);
            return false;
        }

        // 2. Ambil transaksi
        $payment = Payment::where('order_id', $orderId)->first();

        if (!$payment) {
            Log::error('Payment not found for webhook', ['order_id' => $orderId]);
            return false; // Jangan return error 404 agar midtrans berhenti mengirim
        }

        // 3. Mapping status & Idempotent Update
        $transactionStatus = $payload['transaction_status'] ?? '';

        // Jika sudah lunas, jangan ubah status jadi pending/failed lagi (idempotent)
        if (in_array($payment->status, [PaymentStatus::Paid->value, 'success'], true)) {
            return true;
        }

        $newStatus = $payment->status;

        if ($transactionStatus === 'capture' || $transactionStatus === 'settlement') {
            $newStatus = PaymentStatus::Paid->value;
        } elseif ($transactionStatus === 'pending') {
            $newStatus = PaymentStatus::Pending->value;
        } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel', 'failure'], true)) {
            $newStatus = PaymentStatus::Failed->value;
        }

        $payment->update([
            'status' => $newStatus,
            'payment_type' => $payload['payment_type'] ?? null,
            'payment_detail' => $payload
        ]);

        return true;
    }
}
