<?php

namespace Tests\Feature\Payments;

use App\Features\Academic\Models\Semester;
use App\Features\Payments\Models\Payment;
use App\Features\Payments\Services\MidtransService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PaymentStatusContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_midtrans_status_matches_filament_resource_status_options(): void
    {
        $serverKey = 'SB-test-key';
        Config::set('services.midtrans.server_key', $serverKey);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester 1 2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $payment = Payment::create([
            'user_id' => $student->id,
            'semester_id' => $semester->id,
            'order_id' => 'ORDER-STATUS-TEST',
            'amount' => 500000,
            'status' => 'pending',
        ]);

        $signature = hash('sha512', 'ORDER-STATUS-TEST' . '200' . '500000.00' . $serverKey);
        $payload = [
            'order_id' => 'ORDER-STATUS-TEST',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ];

        app(MidtransService::class)->handleWebhook($payload);

        $payment->refresh();

        // BUG REPRODUCED: Midtrans sets 'paid', but Filament PaymentResource options only have ['pending', 'success', 'failed']
        $formStatusOptions = [
            'pending' => 'Pending (Menunggu)',
            'success' => 'Lunas (Success)',
            'failed' => 'Gagal',
        ];

        $this->assertArrayHasKey(
            $payment->status,
            $formStatusOptions,
            "Midtrans status '{$payment->status}' must be present in Filament PaymentResource options"
        );
    }

    public function test_filament_payments_table_and_relation_manager_filter_paid_status(): void
    {
        // Status contract mismatch: Midtrans sets 'paid', but RelationManager filter only recognizes 'success'
        $relationManagerFilterOptions = [
            'success' => 'Sudah Bayar (Lunas)',
            'pending' => 'Belum Bayar (Pending)',
        ];

        $payment = Payment::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'semester_id' => Semester::create([
                'name' => 'Sem Test',
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'is_active' => true,
                'tuition_fee' => 100000,
            ])->id,
            'order_id' => 'ORDER-RELATION-TEST',
            'amount' => 100000,
            'status' => 'paid',
        ]);

        $this->assertArrayHasKey(
            $payment->status,
            $relationManagerFilterOptions,
            "PaymentsRelationManager filter must support Midtrans 'paid' status"
        );
    }
}