<?php

namespace Tests\Feature\Filament;

use App\Models\User;
use App\Features\Permissions\Models\RolePermission;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Meetings\Models\Meeting;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Features\Payments\Models\Payment;
use App\Features\Posts\Models\Post;
use App\Features\SiteSettings\Models\SiteSetting;
use App\Features\TeacherAttendances\Models\TeacherAttendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Create active semester since many resources depend on it
        Semester::create([
            'name' => 'S1', 
            'year' => '2024', 
            'start_date' => now(), 
            'end_date' => now()->addMonths(6), 
            'is_active' => true
        ]);
    }

    public function test_superadmin_can_access_all_resources()
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $this->actingAs($admin);

        $resources = [
            'admin/payments',
            'admin/posts',
            'admin/class-groups',
            'admin/meetings',
            'admin/teacher-attendances',
            'admin/assessments',
            'admin/evaluations',
            'admin/grades',
            'admin/role-permissions',
            'admin/site-settings',
            'admin/raport',
            'admin/candidates',
            'admin/semesters',
            'admin/users',
        ];

        foreach ($resources as $url) {
            $response = $this->get($url);
            $response->assertStatus(200);
        }
    }

    public function test_guru_access_restricted_by_permissions()
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $this->actingAs($guru);

        // Try to access meetings (requires meetings.manage usually)
        $response = $this->get('admin/meetings');
        // Filament redirects to dashboard if canAccessPanel is false for current role/permissions
        $response->assertStatus(302);

        // Grant permission to access panel and resource
        RolePermission::create([
            'role' => 'guru',
            'permission' => 'meetings.view',
            'is_allowed' => true
        ]);

        $response = $this->get('admin/meetings');
        $response->assertStatus(200);
    }

    public function test_resources_use_expected_model_namespaces()
    {
        $this->assertSame(Payment::class, \App\Filament\Resources\PaymentResource::getModel());
        $this->assertSame(Post::class, \App\Filament\Resources\PostResource::getModel());
        $this->assertSame(ClassGroup::class, \App\Filament\Resources\ClassGroupResource::getModel());
        $this->assertSame(Meeting::class, \App\Filament\Resources\MeetingResource::getModel());
        $this->assertSame(TeacherAttendance::class, \App\Filament\Resources\TeacherAttendanceResource::getModel());
        $this->assertSame(Assessment::class, \App\Filament\Resources\AssessmentResource::getModel());
        $this->assertSame(Evaluation::class, \App\Filament\Resources\EvaluationResource::getModel());
        $this->assertSame(Grade::class, \App\Filament\Resources\GradeResource::getModel());
        $this->assertSame(RolePermission::class, \App\Filament\Resources\RolePermissionResource::getModel());
        $this->assertSame(SiteSetting::class, \App\Filament\Resources\SiteSettingResource::getModel());
        $this->assertSame(Semester::class, \App\Filament\Resources\SemesterResource::getModel());
        $this->assertSame(User::class, \App\Filament\Resources\UserResource::getModel());
        $this->assertSame(User::class, \App\Filament\Resources\CandidateResource::getModel());
        $this->assertSame(ClassGroup::class, \App\Filament\Resources\ReportResource::getModel());
    }

    public function test_resource_actions_follow_permissions()
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $payment = Payment::create([
            'user_id' => User::factory()->create(['role' => 'student'])->id,
            'semester_id' => Semester::first()->id,
            'order_id' => 'PERMISSION-ACTION-1',
            'amount' => 100000,
            'status' => 'pending',
        ]);

        $this->actingAs($guru);
        $this->assertFalse(\App\Filament\Resources\PaymentResource::canViewAny());
        $this->assertFalse(\App\Filament\Resources\PaymentResource::canCreate());
        $this->assertFalse(\App\Filament\Resources\PaymentResource::canEdit($payment));
        $this->assertFalse(\App\Filament\Resources\PaymentResource::canDelete($payment));

        RolePermission::create(['role' => 'guru', 'permission' => 'payments.view', 'is_allowed' => true]);
        RolePermission::create(['role' => 'guru', 'permission' => 'payments.create', 'is_allowed' => true]);
        RolePermission::create(['role' => 'guru', 'permission' => 'payments.update', 'is_allowed' => true]);
        RolePermission::create(['role' => 'guru', 'permission' => 'payments.delete', 'is_allowed' => true]);

        $this->assertTrue(\App\Filament\Resources\PaymentResource::canViewAny());
        $this->assertTrue(\App\Filament\Resources\PaymentResource::canCreate());
        $this->assertTrue(\App\Filament\Resources\PaymentResource::canEdit($payment));
        $this->assertTrue(\App\Filament\Resources\PaymentResource::canDelete($payment));
    }

    public function test_student_cannot_access_admin_panel()
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $this->actingAs($student);

        $response = $this->get('admin');
        $response->assertStatus(302);
    }

    public function test_unauthenticated_user_redirected_to_login()
    {
        $response = $this->get('admin');
        $response->assertRedirect('admin/login');
    }
}
