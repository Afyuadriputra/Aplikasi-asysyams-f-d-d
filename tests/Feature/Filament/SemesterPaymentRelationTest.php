<?php

namespace Tests\Feature\Filament;

use App\Features\Academic\Models\Semester;
use App\Features\Payments\Enums\PaymentStatus;
use App\Features\Payments\Models\Payment;
use App\Filament\Resources\SemesterResource;
use App\Filament\Resources\SemesterResource\Pages\EditSemester;
use App\Filament\Resources\SemesterResource\RelationManagers\PaymentsRelationManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SemesterPaymentRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_semester_resource_registers_payments_relation_manager(): void
    {
        $relations = SemesterResource::getRelations();

        $this->assertContains(
            PaymentsRelationManager::class,
            $relations,
            'SemesterResource must register PaymentsRelationManager'
        );
    }

    public function test_authorized_admin_can_access_semester_payment_relation(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Test',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $this->actingAs($admin);

        $response = $this->get('admin/semesters/' . $semester->id . '/edit');
        $response->assertStatus(200);

        Livewire::test(PaymentsRelationManager::class, [
            'ownerRecord' => $semester,
            'pageClass' => EditSemester::class,
        ])
            ->assertSuccessful()
            ->assertSeeText('Status Pembayaran SPP Siswa')
            ->assertSeeText('Buat Tagihan Untuk Semua Siswa');
    }

    public function test_generate_invoices_creates_payment_for_active_students(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Test Gen',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 350000,
        ]);

        $student1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $count = PaymentsRelationManager::generateInvoicesForSemester($semester);

        $this->assertSame(2, $count);
        $this->assertDatabaseHas('payments', [
            'semester_id' => $semester->id,
            'user_id' => $student1->id,
            'amount' => 350000,
            'status' => PaymentStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('payments', [
            'semester_id' => $semester->id,
            'user_id' => $student2->id,
            'amount' => 350000,
            'status' => PaymentStatus::Pending->value,
        ]);
    }

    public function test_inactive_students_do_not_receive_newly_generated_invoice(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Test Inactive',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 350000,
        ]);

        $activeStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $inactiveStudent = User::factory()->create(['role' => 'student', 'is_active' => false]);

        $count = PaymentsRelationManager::generateInvoicesForSemester($semester);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('payments', [
            'semester_id' => $semester->id,
            'user_id' => $activeStudent->id,
        ]);
        $this->assertDatabaseMissing('payments', [
            'semester_id' => $semester->id,
            'user_id' => $inactiveStudent->id,
        ]);
    }

    public function test_existing_same_semester_payment_is_not_duplicated(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Dup Test',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 400000,
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $countFirst = PaymentsRelationManager::generateInvoicesForSemester($semester);
        $this->assertSame(1, $countFirst);

        // Run second time
        $countSecond = PaymentsRelationManager::generateInvoicesForSemester($semester);
        $this->assertSame(0, $countSecond);

        $this->assertSame(1, Payment::where('semester_id', $semester->id)->where('user_id', $student->id)->count());
    }

    public function test_invoice_belongs_to_correct_semester(): void
    {
        $semesterA = Semester::create([
            'name' => 'Semester A',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 300000,
        ]);
        $semesterB = Semester::create([
            'name' => 'Semester B',
            'start_date' => now()->addMonths(7),
            'end_date' => now()->addMonths(13),
            'is_active' => false,
            'tuition_fee' => 450000,
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        PaymentsRelationManager::generateInvoicesForSemester($semesterA);

        $payment = Payment::where('user_id', $student->id)->first();
        $this->assertSame($semesterA->id, $payment->semester_id);
        $this->assertNotSame($semesterB->id, $payment->semester_id);
    }

    public function test_generated_invoice_status_is_canonical_pending(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Canonical Pending',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        PaymentsRelationManager::generateInvoicesForSemester($semester);

        $payment = Payment::where('user_id', $student->id)->firstOrFail();
        $this->assertSame(PaymentStatus::Pending->value, $payment->status);
    }

    public function test_manual_mark_paid_stores_canonical_status_paid(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Manual Paid',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $payment = Payment::create([
            'semester_id' => $semester->id,
            'user_id' => $student->id,
            'order_id' => 'INV-TEST-001',
            'amount' => 500000,
            'status' => PaymentStatus::Pending->value,
        ]);

        PaymentsRelationManager::markAsPaid($payment);

        $this->assertSame(PaymentStatus::Paid->value, $payment->fresh()->status);
        $this->assertSame('manual_cash', $payment->fresh()->payment_type);
    }

    public function test_paid_payment_remains_paid(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Paid Remains',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $payment = Payment::create([
            'semester_id' => $semester->id,
            'user_id' => $student->id,
            'order_id' => 'INV-TEST-002',
            'amount' => 500000,
            'status' => PaymentStatus::Paid->value,
            'payment_type' => 'qris',
        ]);

        PaymentsRelationManager::markAsPaid($payment);

        $this->assertSame(PaymentStatus::Paid->value, $payment->fresh()->status);
    }

    public function test_relation_filter_lunas_finds_paid_records(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Filter Test',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $student1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = User::factory()->create(['role' => 'student', 'is_active' => true]);

        Payment::create([
            'semester_id' => $semester->id,
            'user_id' => $student1->id,
            'order_id' => 'INV-FILTER-001',
            'amount' => 500000,
            'status' => PaymentStatus::Paid->value,
        ]);

        Payment::create([
            'semester_id' => $semester->id,
            'user_id' => $student2->id,
            'order_id' => 'INV-FILTER-002',
            'amount' => 500000,
            'status' => PaymentStatus::Pending->value,
        ]);

        $paidPayments = $semester->payments()->where('status', PaymentStatus::Paid->value)->get();

        $this->assertCount(1, $paidPayments);
        $this->assertSame($student1->id, $paidPayments->first()->user_id);
    }
}