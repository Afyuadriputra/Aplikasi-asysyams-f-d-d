<?php

namespace Tests\Unit\Payments;

use App\Features\Academic\Models\Semester;
use App\Features\Payments\Models\Payment;
use App\Features\Payments\Services\MidtransService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class MidtransServiceTest extends TestCase
{
    use RefreshDatabase;

    private MidtransService $service;
    private string $serverKey = 'SB-Mid-server-test-key-12345';

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.midtrans.server_key', $this->serverKey);
        $this->service = new MidtransService();
    }

    public function test_webhook_with_valid_signature_updates_payment(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester 1 2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'semester_id' => $semester->id,
            'order_id' => 'ORDER-1001',
            'amount' => 500000,
            'status' => 'pending',
        ]);

        $signature = hash('sha512', 'ORDER-1001' . '200' . '500000.00' . $this->serverKey);

        $payload = [
            'order_id' => 'ORDER-1001',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
            'payment_type' => 'bank_transfer',
        ];

        $result = $this->service->handleWebhook($payload);

        $this->assertTrue($result);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('bank_transfer', $payment->fresh()->payment_type);
    }

    public function test_webhook_with_invalid_signature_returns_false(): void
    {
        $payload = [
            'order_id' => 'ORDER-9999',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => 'invalid-signature-hash',
            'transaction_status' => 'settlement',
        ];

        $result = $this->service->handleWebhook($payload);

        $this->assertFalse($result);
    }

    public function test_webhook_idempotency_does_not_revert_paid_status(): void
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester 1 2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $payment = Payment::create([
            'user_id' => $user->id,
            'semester_id' => $semester->id,
            'order_id' => 'ORDER-1002',
            'amount' => 500000,
            'status' => 'paid',
        ]);

        $signature = hash('sha512', 'ORDER-1002' . '200' . '500000.00' . $this->serverKey);

        $payload = [
            'order_id' => 'ORDER-1002',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'pending',
        ];

        $result = $this->service->handleWebhook($payload);

        $this->assertTrue($result);
        $this->assertSame('paid', $payment->fresh()->status);
    }
}