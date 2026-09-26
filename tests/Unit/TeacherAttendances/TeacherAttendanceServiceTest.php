<?php

namespace Tests\Unit\TeacherAttendances;

use App\Features\TeacherAttendances\Services\TeacherAttendanceService;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TeacherAttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    private TeacherAttendanceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TeacherAttendanceService::class);
    }

    public function test_guru_can_check_in_successfully(): void
    {
        Carbon::setTestNow('2026-09-27 07:30:00');
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $attendance = $this->service->checkIn($guru);

        $this->assertSame('present', $attendance->status);
        $this->assertSame('2026-09-27', $attendance->date->toDateString());
    }

    public function test_guru_check_in_late_sets_late_status(): void
    {
        Carbon::setTestNow('2026-09-27 08:30:00');
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $attendance = $this->service->checkIn($guru);

        $this->assertSame('late', $attendance->status);
    }

    public function test_guru_cannot_double_check_in_on_same_day(): void
    {
        Carbon::setTestNow('2026-09-27 07:30:00');
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->service->checkIn($guru);

        $this->expectException(ValidationException::class);
        $this->service->checkIn($guru);
    }

    public function test_student_cannot_check_in(): void
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $this->expectException(ValidationException::class);
        $this->service->checkIn($student);
    }

    public function test_guru_can_check_out_after_check_in(): void
    {
        Carbon::setTestNow('2026-09-27 07:30:00');
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->service->checkIn($guru);

        Carbon::setTestNow('2026-09-27 12:00:00');
        $attendance = $this->service->checkOut($guru);

        $this->assertNotNull($attendance->check_out_at);
    }

    public function test_guru_cannot_check_out_without_check_in(): void
    {
        Carbon::setTestNow('2026-09-27 12:00:00');
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->expectException(ValidationException::class);
        $this->service->checkOut($guru);
    }
}