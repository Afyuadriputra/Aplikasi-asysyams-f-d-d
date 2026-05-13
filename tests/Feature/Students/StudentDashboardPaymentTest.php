<?php

namespace Tests\Feature\Students;

use App\Features\Payments\Models\Payment;
use App\Features\Permissions\Models\RolePermission;
use App\Features\Academic\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_pay_button_is_visible_when_status_is_pending()
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        RolePermission::create([
            'role' => 'student',
            'permission' => 'payments.checkout',
            'is_allowed' => true,
        ]);

        $semester = Semester::create([
            'name' => 'Ganjil',
            'year' => '2026/2027',
            'tuition_fee' => 500000,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        Payment::create([
            'user_id' => $user->id,
            'semester_id' => $semester->id,
            'order_id' => 'SPP-1',
            'amount' => 500000,
            'status' => 'pending',
        ]);

        $this->actingAs($user);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Bayar Sekarang');
        $response->assertSee('MENUNGGU PEMBAYARAN');
        $response->assertDontSee('Sudah Lunas');
    }

    public function test_pay_button_is_disabled_and_shows_lunas_when_status_is_paid()
    {
        $user = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Genap',
            'year' => '2026/2027',
            'tuition_fee' => 500000,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        Payment::create([
            'user_id' => $user->id,
            'semester_id' => $semester->id,
            'order_id' => 'SPP-2',
            'amount' => 500000,
            'status' => 'paid', // Status dari webhook
        ]);

        $this->actingAs($user);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('id="pay-button"', false);
        $response->assertSee('Sudah Lunas');
        $response->assertSee('LUNAS');
    }

    public function test_student_hanya_melihat_payment_miliknya()
    {
        $student1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        
        $semester = Semester::create([
            'name' => 'Genap',
            'year' => '2026/2027',
            'tuition_fee' => 500000,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        // Payment untuk student 2 sudah lunas
        Payment::create([
            'user_id' => $student2->id,
            'semester_id' => $semester->id,
            'order_id' => 'SPP-STUDENT-2',
            'amount' => 500000,
            'status' => 'paid', 
        ]);

        // Student 1 tidak punya payment
        $this->actingAs($student1);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        // Karena student 1 tidak ada data payment, dia seharusnya 'unpaid' atau 'Belum Dibayar' (atau tombol Bayar aktif)
        // Dia TIDAK BOLEH melihat status 'Sudah Lunas' dari student 2.
        $response->assertDontSee('Sudah Lunas');
    }
}
