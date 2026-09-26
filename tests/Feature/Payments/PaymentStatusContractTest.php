<?php

namespace Tests\Feature\Payments;

use App\Features\Academic\Models\Semester;
use App\Features\Payments\Enums\PaymentStatus;
use App\Features\Payments\Models\Payment;
use App\Features\Payments\Services\MidtransService;
use App\Filament\Resources\PaymentResource;
use App\Filament\Resources\SemesterResource\RelationManagers\PaymentsRelationManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentStatusContractTest extends TestCase
{
    use RefreshDatabase;

    private string $serverKey = 'SB-test-key';
    private User $student;
    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.midtrans.server_key', $this->serverKey);

        $this->student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->semester = Semester::create([
            'name' => 'Semester 1 2026',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
    }

    public function test_midtrans_status_matches_filament_resource_status_options(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'ORDER-STATUS-TEST-1',
            'amount' => 500000,
            'status' => PaymentStatus::Pending->value,
        ]);

        $signature = hash('sha512', 'ORDER-STATUS-TEST-1' . '200' . '500000.00' . $this->serverKey);
        $payload = [
            'order_id' => 'ORDER-STATUS-TEST-1',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'settlement',
        ];

        app(MidtransService::class)->handleWebhook($payload);
        $payment->refresh();

        $formStatusOptions = PaymentResource::getStatusOptions();

        $this->assertSame(PaymentStatus::Paid->value, $payment->status);
        $this->assertArrayHasKey(
            $payment->status,
            $formStatusOptions,
            "Midtrans status '{$payment->status}' must be present in Filament PaymentResource options"
        );
    }

    public function test_filament_payments_table_and_relation_manager_filter_paid_status(): void
    {
        $relationManagerFilterOptions = PaymentsRelationManager::getStatusFilterOptions();
        $resourceFilterOptions = PaymentResource::getStatusFilterOptions();

        $payment = Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'ORDER-RELATION-TEST-2',
            'amount' => 500000,
            'status' => PaymentStatus::Paid->value,
        ]);

        $this->assertArrayHasKey(
            $payment->status,
            $relationManagerFilterOptions,
            "PaymentsRelationManager filter must support 'paid' status"
        );
        $this->assertArrayHasKey(
            $payment->status,
            $resourceFilterOptions,
            "PaymentResource filter must support 'paid' status"
        );
        $this->assertSame('Lunas', $relationManagerFilterOptions['paid']);
    }

    public function test_new_successful_payment_is_stored_as_paid(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'ORDER-NEW-SUCCESS-3',
            'amount' => 500000,
            'status' => PaymentStatus::Pending->value,
        ]);

        $signature = hash('sha512', 'ORDER-NEW-SUCCESS-3' . '200' . '500000.00' . $this->serverKey);
        $payload = [
            'order_id' => 'ORDER-NEW-SUCCESS-3',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'capture',
        ];

        $handled = app(MidtransService::class)->handleWebhook($payload);
        $this->assertTrue($handled);

        $this->assertDatabaseHas('payments', [
            'order_id' => 'ORDER-NEW-SUCCESS-3',
            'status' => 'paid',
        ]);
        $this->assertDatabaseMissing('payments', [
            'order_id' => 'ORDER-NEW-SUCCESS-3',
            'status' => 'success',
        ]);
    }

    public function test_legacy_success_status_is_migrated_to_paid(): void
    {
        DB::table('payments')->insert([
            'order_id' => 'LEGACY-SUCCESS-4',
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'amount' => 500000,
            'status' => 'success',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('payments', [
            'order_id' => 'LEGACY-SUCCESS-4',
            'status' => 'success',
        ]);

        $migration = require database_path('migrations/2026_09_26_231000_migrate_legacy_payment_success_status_to_paid.php');
        $migration->up();

        $this->assertDatabaseHas('payments', [
            'order_id' => 'LEGACY-SUCCESS-4',
            'status' => 'paid',
        ]);
        $this->assertDatabaseMissing('payments', [
            'order_id' => 'LEGACY-SUCCESS-4',
            'status' => 'success',
        ]);
    }

    public function test_paid_payment_has_lunas_badge(): void
    {
        $this->assertSame('Lunas', PaymentStatus::Paid->label());
        $this->assertSame('success', PaymentStatus::Paid->color());
    }

    public function test_lunas_filter_matches_paid_records(): void
    {
        Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'ORDER-PAID-5',
            'amount' => 500000,
            'status' => PaymentStatus::Paid->value,
        ]);

        Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'ORDER-PENDING-6',
            'amount' => 500000,
            'status' => PaymentStatus::Pending->value,
        ]);

        $paidCount = Payment::where('status', PaymentStatus::Paid->value)->count();
        $this->assertSame(1, $paidCount);

        $filterOptions = PaymentResource::getStatusFilterOptions();
        $this->assertSame('Lunas', $filterOptions[PaymentStatus::Paid->value]);
    }

    public function test_paid_payment_cannot_be_downgraded(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'ORDER-IDEMPOTENT-7',
            'amount' => 500000,
            'status' => PaymentStatus::Paid->value,
        ]);

        $signature = hash('sha512', 'ORDER-IDEMPOTENT-7' . '200' . '500000.00' . $this->serverKey);
        $pendingPayload = [
            'order_id' => 'ORDER-IDEMPOTENT-7',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'pending',
        ];

        $this->assertTrue(app(MidtransService::class)->handleWebhook($pendingPayload));
        $this->assertSame(PaymentStatus::Paid->value, $payment->fresh()->status);

        $cancelPayload = [
            'order_id' => 'ORDER-IDEMPOTENT-7',
            'status_code' => '200',
            'gross_amount' => '500000.00',
            'signature_key' => $signature,
            'transaction_status' => 'cancel',
        ];

        $this->assertTrue(app(MidtransService::class)->handleWebhook($cancelPayload));
        $this->assertSame(PaymentStatus::Paid->value, $payment->fresh()->status);
    }

    public function test_manual_payment_uses_paid_status(): void
    {
        $payment = Payment::create([
            'user_id' => $this->student->id,
            'semester_id' => $this->semester->id,
            'order_id' => 'MANUAL-8',
            'amount' => 500000,
            'status' => PaymentStatus::Pending->value,
        ]);

        // Simulating manual set lunas
        $payment->update([
            'status' => PaymentStatus::Paid->value,
            'payment_type' => 'manual_cash',
        ]);

        $this->assertSame(PaymentStatus::Paid->value, $payment->fresh()->status);
        $this->assertSame('manual_cash', $payment->fresh()->payment_type);
    }
}