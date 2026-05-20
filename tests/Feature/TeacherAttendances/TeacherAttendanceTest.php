<?php

namespace Tests\Feature\TeacherAttendances;

use App\Features\Permissions\Models\RolePermission;
use App\Features\SiteSettings\Models\SiteSetting;
use App\Features\TeacherAttendances\Models\TeacherAttendance;
use App\Features\TeacherAttendances\Services\TeacherAttendanceService;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TeacherAttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_guru_can_check_in_for_themselves(): void
    {
        Carbon::setTestNow('2026-05-20 07:50:00');
        $guru = $this->guruWithCheckInPermission();

        $response = $this->actingAs($guru)->post(route('teacher-attendances.check-in'));

        $response->assertRedirect();
        $attendance = TeacherAttendance::where('user_id', $guru->id)->first();

        $this->assertSame('2026-05-20', $attendance?->date?->toDateString());
        $this->assertSame('present', $attendance?->status);
    }

    public function test_late_check_in_sets_late_status(): void
    {
        Carbon::setTestNow('2026-05-20 08:01:00');
        $guru = $this->guruWithCheckInPermission();

        $this->actingAs($guru)->post(route('teacher-attendances.check-in'));

        $this->assertDatabaseHas('teacher_attendances', [
            'user_id' => $guru->id,
            'status' => 'late',
        ]);
    }

    public function test_late_limit_can_be_configured_by_site_setting(): void
    {
        Carbon::setTestNow('2026-05-20 13:25:00');
        SiteSetting::create([
            'key' => TeacherAttendanceService::LATE_AFTER_SETTING_KEY,
            'value' => '14:00',
        ]);
        $guru = $this->guruWithCheckInPermission();

        $this->actingAs($guru)->post(route('teacher-attendances.check-in'));

        $this->assertSame('present', TeacherAttendance::where('user_id', $guru->id)->first()?->status);
    }

    public function test_guru_cannot_double_check_in_on_same_date(): void
    {
        Carbon::setTestNow('2026-05-20 07:50:00');
        $guru = $this->guruWithCheckInPermission();

        $this->actingAs($guru)->post(route('teacher-attendances.check-in'));
        $response = $this->actingAs($guru)->post(route('teacher-attendances.check-in'));

        $response->assertSessionHasErrors('attendance');
        $this->assertSame(1, TeacherAttendance::where('user_id', $guru->id)->count());
    }

    public function test_guru_can_check_out_after_check_in(): void
    {
        Carbon::setTestNow('2026-05-20 07:50:00');
        $guru = $this->guruWithCheckInPermission();
        $this->actingAs($guru)->post(route('teacher-attendances.check-in'));

        Carbon::setTestNow('2026-05-20 11:30:00');
        $response = $this->actingAs($guru)->post(route('teacher-attendances.check-out'));

        $response->assertRedirect();
        $this->assertNotNull(TeacherAttendance::where('user_id', $guru->id)->first()?->check_out_at);
    }

    public function test_guru_cannot_check_out_before_check_in(): void
    {
        Carbon::setTestNow('2026-05-20 11:30:00');
        $guru = $this->guruWithCheckInPermission();

        $response = $this->actingAs($guru)->post(route('teacher-attendances.check-out'));

        $response->assertSessionHasErrors('attendance');
    }

    public function test_student_cannot_check_in(): void
    {
        RolePermission::create(['role' => 'student', 'permission' => 'teacher-attendances.check-in', 'is_allowed' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $response = $this->actingAs($student)->post(route('teacher-attendances.check-in'));

        $response->assertSessionHasErrors('attendance');
        $this->assertDatabaseCount('teacher_attendances', 0);
    }

    public function test_superadmin_can_create_manual_attendance_through_service(): void
    {
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $attendance = app(TeacherAttendanceService::class)->createManualAttendance([
            'user_id' => $guru->id,
            'date' => '2026-05-20',
            'status' => 'permission',
            'note' => 'Izin mengikuti pelatihan.',
        ], $superadmin);

        $this->assertTrue($attendance->creator->is($superadmin));
        $this->assertSame('permission', $attendance->status);
    }

    public function test_unique_user_id_and_date_constraint_works(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        TeacherAttendance::create([
            'user_id' => $guru->id,
            'date' => '2026-05-20',
            'status' => 'present',
        ]);

        $this->expectException(QueryException::class);

        TeacherAttendance::create([
            'user_id' => $guru->id,
            'date' => '2026-05-20',
            'status' => 'late',
        ]);
    }

    public function test_dashboard_guru_displays_today_teacher_attendance_status(): void
    {
        Carbon::setTestNow('2026-05-20 07:50:00');
        $guru = $this->guruWithCheckInPermission();

        TeacherAttendance::create([
            'user_id' => $guru->id,
            'date' => '2026-05-20',
            'check_in_at' => now(),
            'status' => 'present',
        ]);

        $response = $this->actingAs($guru)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Absensi Saya Hari Ini');
        $response->assertSee('Hadir');
        $response->assertSee('Check Out');
    }

    public function test_filament_teacher_attendance_resource_is_protected_by_permission(): void
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->actingAs($guru);
        $this->assertFalse(\App\Filament\Resources\TeacherAttendanceResource::canViewAny());

        RolePermission::create(['role' => 'guru', 'permission' => 'teacher-attendances.view', 'is_allowed' => true]);

        $this->assertTrue(\App\Filament\Resources\TeacherAttendanceResource::canViewAny());
    }

    private function guruWithCheckInPermission(): User
    {
        RolePermission::updateOrCreate(
            ['role' => 'guru', 'permission' => 'teacher-attendances.check-in'],
            ['is_allowed' => true],
        );

        return User::factory()->create(['role' => 'guru', 'is_active' => true]);
    }
}
