<?php

namespace Tests\Feature;

use App\Features\Payments\Models\Payment;
use App\Models\User;
use App\Features\Academic\Models\Semester;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MidtransWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock konfigurasi Midtrans untuk testing
        Config::set('services.midtrans.server_key', 'test-server-key');
    }

    public function test_webhook_can_update_payment_status_to_paid_on_settlement()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $semester = Semester::create([
            'name' => 'Ganjil',
            'year' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        $orderId = 'SPP-' . $user->id . '-' . time();
        $payment = Payment::create([
            'user_id' => $user->id,
            'semester_id' => $semester->id,
            'order_id' => $orderId,
            'amount' => 500000,
            'status' => 'pending',
        ]);

        // 2. Buat Signature Key Valid
        $statusCode = '200';
        $grossAmount = '500000.00';
        $serverKey = 'test-server-key';
        
        // Aturan Midtrans: order_id + status_code + gross_amount + server_key
        $signatureKey = hash("sha512", $orderId . $statusCode . $grossAmount . $serverKey);

        $payload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'transaction_status' => 'settlement',
            'signature_key' => $signatureKey,
            'payment_type' => 'bank_transfer',
        ];

        // 3. Kirim Webhook (Bypass CSRF dengan withoutMiddleware sudah di web.php)
        // Tetapi dalam Feature Test di Laravel 11/12 with withoutMiddleware mungkin butuh disable khusus
        // Kita langsung hit route-nya.
        $response = $this->postJson(route('payment.webhook'), $payload);

        // 4. Assert response dan status di DB
        $response->assertStatus(200);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
            'payment_type' => 'bank_transfer'
        ]);
    }

    public function test_webhook_rejects_invalid_signature()
    {
        // 1. Setup Data
        $user = User::factory()->create();
        $semester = Semester::create([
            'name' => 'Genap',
            'year' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        $orderId = 'SPP-' . $user->id . '-' . time();
        $payment = Payment::create([
            'user_id' => $user->id,
            'semester_id' => $semester->id,
            'order_id' => $orderId,
            'amount' => 500000,
            'status' => 'pending',
        ]);

        // 2. Payload dengan Signature yang SALAH
        $payload = [
            'order_id' => $orderId,
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'transaction_status' => 'settlement',
            'signature_key' => 'invalid-signature-12345',
        ];

        // 3. Kirim Webhook
        $response = $this->postJson(route('payment.webhook'), $payload);

        // 4. Assert response 403 (Forbidden) dan status tetap pending
        $response->assertStatus(403);
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending', // TIDAK BERUBAH
        ]);
    }
}
