<?php

namespace App\Features\TeacherAttendances\Services;

use App\Features\SiteSettings\Models\SiteSetting;
use App\Features\TeacherAttendances\Models\TeacherAttendance;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class TeacherAttendanceService
{
    public const DEFAULT_LATE_AFTER = '08:00';
    public const LATE_AFTER_SETTING_KEY = 'teacher_attendance_late_after';

    public function getTodayAttendance(User $user): ?TeacherAttendance
    {
        return TeacherAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', now()->toDateString())
            ->first();
    }

    public function checkIn(User $user): TeacherAttendance
    {
        $this->ensureGuru($user);

        if ($this->getTodayAttendance($user)) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda sudah melakukan check-in hari ini.',
            ]);
        }

        $now = now();

        return TeacherAttendance::create([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
            'check_in_at' => $now,
            'status' => $this->isLate($now) ? 'late' : 'present',
        ]);
    }

    public function checkOut(User $user): TeacherAttendance
    {
        $this->ensureGuru($user);

        $attendance = $this->getTodayAttendance($user);

        if (! $attendance || ! $attendance->check_in_at) {
            throw ValidationException::withMessages([
                'attendance' => 'Check-out hanya bisa dilakukan setelah check-in.',
            ]);
        }

        if ($attendance->check_out_at) {
            throw ValidationException::withMessages([
                'attendance' => 'Anda sudah melakukan check-out hari ini.',
            ]);
        }

        if (in_array($attendance->status, ['permission', 'sick', 'alpha'], true)) {
            throw ValidationException::withMessages([
                'attendance' => 'Status absensi hari ini tidak membutuhkan check-out.',
            ]);
        }

        $attendance->update(['check_out_at' => now()]);

        return $attendance->refresh();
    }

    public function getRecentAttendances(User $user, int $days = 7): Collection
    {
        return TeacherAttendance::query()
            ->where('user_id', $user->id)
            ->whereDate('date', '>=', now()->subDays(max(1, $days) - 1)->toDateString())
            ->latest('date')
            ->get();
    }

    public function getTodaySummary(): array
    {
        $query = TeacherAttendance::query()->whereDate('date', now()->toDateString());
        $teacherCount = User::query()->where('role', 'guru')->where('is_active', true)->count();
        $recordedCount = (clone $query)->distinct('user_id')->count('user_id');

        return [
            'present' => (clone $query)->where('status', 'present')->count(),
            'late' => (clone $query)->where('status', 'late')->count(),
            'permission_or_sick' => (clone $query)->whereIn('status', ['permission', 'sick'])->count(),
            'alpha' => (clone $query)->where('status', 'alpha')->count(),
            'not_checked_in' => max(0, $teacherCount - $recordedCount),
        ];
    }

    public function getTodayAttendances(): Collection
    {
        return TeacherAttendance::query()
            ->with(['user', 'creator'])
            ->whereDate('date', now()->toDateString())
            ->orderBy('check_in_at')
            ->orderBy('id')
            ->get();
    }

    public function createManualAttendance(array $data, User $createdBy): TeacherAttendance
    {
        $this->ensureAdmin($createdBy);

        return TeacherAttendance::create([
            ...$data,
            'created_by' => $createdBy->id,
        ]);
    }

    public function updateManualAttendance(TeacherAttendance $attendance, array $data): TeacherAttendance
    {
        $attendance->update($data);

        return $attendance->refresh();
    }

    private function ensureGuru(User $user): void
    {
        if ($user->role !== 'guru') {
            throw ValidationException::withMessages([
                'attendance' => 'Hanya guru yang bisa melakukan absensi ustad.',
            ]);
        }
    }

    private function ensureAdmin(User $user): void
    {
        if ($user->role !== 'superadmin') {
            throw ValidationException::withMessages([
                'attendance' => 'Hanya superadmin yang bisa membuat absensi manual.',
            ]);
        }
    }

    private function isLate(CarbonInterface $time): bool
    {
        return $time->format('H:i') > $this->lateAfterTime();
    }

    public function lateAfterTime(): string
    {
        $value = SiteSetting::query()
            ->where('key', self::LATE_AFTER_SETTING_KEY)
            ->value('value');

        return preg_match('/^\d{2}:\d{2}$/', (string) $value)
            ? (string) $value
            : self::DEFAULT_LATE_AFTER;
    }
}
