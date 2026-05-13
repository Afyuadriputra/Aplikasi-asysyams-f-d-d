<?php

namespace Tests\Feature\Security;

use App\Models\User;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Assessment;
use App\Features\Payments\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_protected_areas()
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get('/admin')->assertRedirect('admin/login');
    }

    public function test_inactive_student_is_redirected_to_approval_notice()
    {
        $student = User::factory()->create([
            'role' => 'student',
            'is_active' => false
        ]);

        $this->actingAs($student);
        $response = $this->get(route('dashboard'));
        $response->assertRedirect(route('approval.notice'));
    }

    public function test_webhook_with_invalid_signature_is_rejected()
    {
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        
        $payment = Payment::create([
            'order_id' => 'ORDER-123',
            'amount' => 100000,
            'status' => 'pending',
            'user_id' => User::factory()->create()->id,
            'semester_id' => $semester->id
        ]);

        $payload = [
            'order_id' => 'ORDER-123',
            'status_code' => '200',
            'gross_amount' => '100000.00',
            'signature_key' => 'wrong-signature',
            'transaction_status' => 'settlement',
        ];

        $response = $this->postJson(route('payment.webhook'), $payload);
        $response->assertStatus(403);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'pending',
        ]);
    }

    public function test_student_cannot_see_other_students_data()
    {
        $s1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $s2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        $paymentS2 = Payment::create(['order_id' => 'S2-PAY', 'amount' => 100, 'status' => 'pending', 'user_id' => $s2->id, 'semester_id' => $semester->id]);
        $assessmentS2 = Assessment::create(['class_group_id' => $classGroup->id, 'user_id' => $s2->id, 'assessment_type' => 'ziyadah', 'data' => []]);

        $this->actingAs($s1);
        
        // Check student dashboard doesn't show S2 data (simple check for now)
        $response = $this->get(route('dashboard'));
        $response->assertDontSee('S2-PAY');
        
        // Try direct route if it exists (hypothetical, based on system design)
        // Usually handled by query scopes in controllers
    }

    public function test_debug_routes_are_not_accessible_outside_local()
    {
        // Testing environment is 'testing', not 'local'
        $debugRoutes = [
            '/check-db',
            '/clear-cache-sekarang',
            '/cek-rute',
            '/cek-pintu',
        ];

        foreach ($debugRoutes as $url) {
            $response = $this->get($url);
            $response->assertStatus(404);
        }
    }
}
