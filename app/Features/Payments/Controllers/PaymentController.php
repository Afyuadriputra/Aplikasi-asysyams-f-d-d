<?php

namespace App\Features\Payments\Controllers;

use App\Features\Payments\Models\Payment;
use App\Features\Academic\Models\Semester;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;
use Throwable;

class PaymentController extends Controller
{
    public function checkout()
    {
        /** @var User $user */
        $user = Auth::user();
        $activeSemester = Semester::where('is_active', true)->first();

        if (!$activeSemester) {
            return response()->json(['message' => 'Tidak ada semester aktif'], 404);
        }

        if ((int) $activeSemester->tuition_fee <= 0) {
            return response()->json([
                'message' => 'Nominal tagihan semester aktif belum diatur.',
            ], 422);
        }

        $midtransConfigError = $this->getMidtransConfigError();

        if ($midtransConfigError) {
            Log::warning('Midtrans checkout blocked: missing config key', [
                'user_id' => $user->id,
                'role' => $user->role,
                'has_payments_checkout' => $user->hasAccess('payments.checkout'),
                'is_production' => (bool) config('services.midtrans.is_production'),
                'reason' => $midtransConfigError,
            ]);

            return response()->json([
                'message' => $midtransConfigError,
            ], 500);
        }

        // 1. Konfigurasi Midtrans
        $this->configureMidtrans();

        // 2. Cek apakah sudah ada data pembayaran pending?
        $payment = Payment::where('user_id', $user->id)
            ->where('semester_id', $activeSemester->id)
            ->first();

        // Jika belum ada, buat baru
        if (!$payment) {
            $orderId = 'SPP-' . $user->id . '-' . time(); // ID Unik: SPP-USERID-TIMESTAMP
            
            $payment = Payment::create([
                'user_id' => $user->id,
                'semester_id' => $activeSemester->id,
                'order_id' => $orderId,
                'amount' => $activeSemester->tuition_fee,
                'status' => 'pending',
            ]);
        }

        // 3. Buat token baru untuk memastikan callback terbaru ikut terkirim ke Midtrans.
        if (! in_array($payment->status, ['paid', 'success'], true)) {
            $params = [
                'transaction_details' => [
                    'order_id' => $payment->order_id,
                    'gross_amount' => (int) $payment->amount,
                ],
                'customer_details' => [
                    'first_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                ],
                'item_details' => [[
                    'id' => 'SPP-' . $activeSemester->id,
                    'price' => (int) $payment->amount,
                    'quantity' => 1,
                    'name' => 'SPP Semester ' . $activeSemester->name,
                ]],
                'callbacks' => [
                    'finish' => route('payment.success'),
                ],
            ];

            try {
                $snapToken = Snap::getSnapToken($params);
            } catch (Throwable $exception) {
                Log::error('Failed to create Midtrans Snap token', [
                    'user_id' => $user->id,
                    'role' => $user->role,
                    'has_payments_checkout' => $user->hasAccess('payments.checkout'),
                    'payment_id' => $payment->id,
                    'order_id' => $payment->order_id,
                    'exception' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                return response()->json([
                    'message' => config('app.debug')
                        ? 'Gagal membuat token pembayaran: ' . $exception->getMessage()
                        : 'Gagal membuat token pembayaran. Silakan coba lagi.',
                ], 500);
            }
            
            // Simpan token ke database biar gak request ulang terus
            $payment->update(['snap_token' => $snapToken]);
        }

        Log::info('Midtrans Snap token ready', [
            'user_id' => $user->id,
            'role' => $user->role,
            'has_payments_checkout' => $user->hasAccess('payments.checkout'),
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'snap_token_created' => ! empty($payment->snap_token),
        ]);

        return response()->json(['snap_token' => $payment->snap_token]);
    }

    public function success(Request $request)
    {
        $orderId = $request->query('order_id');

        if ($orderId && Auth::check()) {
            $payment = Payment::where('order_id', $orderId)
                ->where('user_id', Auth::id())
                ->first();

            if ($payment && ! in_array($payment->status, ['paid', 'success'], true)) {
                $status = [];
                $transactionStatus = $request->query('transaction_status');

                try {
                    $this->configureMidtrans();

                    $status = (array) Transaction::status($orderId);
                    $transactionStatus = $status['transaction_status'] ?? $transactionStatus;
                } catch (Throwable $exception) {
                    Log::warning('Unable to verify Midtrans status on finish redirect', [
                        'user_id' => Auth::id(),
                        'order_id' => $orderId,
                        'message' => $exception->getMessage(),
                    ]);
                }

                if (in_array($transactionStatus, ['capture', 'settlement'], true)) {
                    $payment->update([
                        'status' => 'paid',
                        'payment_type' => $status['payment_type'] ?? $request->query('payment_type') ?? $payment->payment_type,
                        'payment_detail' => $status !== [] ? $status : [
                            'order_id' => $orderId,
                            'transaction_status' => $transactionStatus,
                            'status_code' => $request->query('status_code'),
                            'payment_type' => $request->query('payment_type'),
                            'source' => 'finish_redirect',
                        ],
                    ]);
                } elseif (in_array($transactionStatus, ['deny', 'expire', 'cancel'], true)) {
                    $payment->update([
                        'status' => 'failed',
                        'payment_type' => $status['payment_type'] ?? $request->query('payment_type') ?? $payment->payment_type,
                        'payment_detail' => $status !== [] ? $status : [
                            'order_id' => $orderId,
                            'transaction_status' => $transactionStatus,
                            'status_code' => $request->query('status_code'),
                            'payment_type' => $request->query('payment_type'),
                            'source' => 'finish_redirect',
                        ],
                    ]);
                }
            }
        }

        return redirect()->route('dashboard')->with('success', 'Pembayaran sedang diproses atau telah berhasil!');
    }

    public function webhook(Request $request, \App\Features\Payments\Services\MidtransService $midtransService)
    {
        $payload = $request->all();
        $handled = $midtransService->handleWebhook($payload);

        if (!$handled) {
            return response()->json(['message' => 'Invalid signature or payment not found'], 403);
        }

        return response()->json(['message' => 'Webhook handled successfully'], 200);
    }

    private function configureMidtrans(): void
    {
        Config::$serverKey = config('services.midtrans.server_key');
        Config::$isProduction = (bool) config('services.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    private function getMidtransConfigError(): ?string
    {
        $serverKey = (string) config('services.midtrans.server_key');
        $clientKey = (string) config('services.midtrans.client_key');
        $isProduction = (bool) config('services.midtrans.is_production');

        if ($serverKey === '' || $clientKey === '') {
            return 'Konfigurasi pembayaran belum lengkap.';
        }

        return null;
    }
}
