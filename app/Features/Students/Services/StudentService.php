<?php

namespace App\Features\Students\Services;

use App\Features\Meetings\Models\Meeting;
use App\Features\TeacherAttendances\Services\TeacherAttendanceService;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StudentService
{
    public function __construct(private readonly TeacherAttendanceService $teacherAttendanceService)
    {
    }

    public function teacherDashboardData(User $user): array
    {
        $baseQuery = $this->meetingQueryForTeacher($user);
        $isGuru = $user->role === 'guru';

        return [
            'totalMeetings' => (clone $baseQuery)->count(),
            'todayClasses' => $this->meetingQueryForTeacher($user)
                ->whereDate('date', now())
                ->orderBy('date')
                ->orderBy('id')
                ->get(),
            'scheduleMeetings' => $this->meetingQueryForTeacher($user)
                ->whereDate('date', '>=', now()->toDateString())
                ->orderBy('date')
                ->orderBy('id')
                ->limit(8)
                ->get(),
            'attendanceSummary' => $this->attendanceSummary($baseQuery),
            'teacherAttendanceToday' => $isGuru ? $this->teacherAttendanceService->getTodayAttendance($user) : null,
            'teacherAttendanceRecent' => $isGuru ? $this->teacherAttendanceService->getRecentAttendances($user) : collect(),
            'teacherAttendanceSummary' => $this->teacherAttendanceService->getTodaySummary(),
            'teacherAttendanceTodayRows' => $user->role === 'superadmin' ? $this->teacherAttendanceService->getTodayAttendances() : collect(),
        ];
    }

    private function meetingQueryForTeacher(User $user): Builder
    {
        return Meeting::query()
            ->with(['classGroup.subject', 'classGroup.semester'])
            ->withCount([
                'attendances as total_attendance_count',
                'attendances as present_count' => fn (Builder $query) => $query->where('status', 'present'),
                'attendances as sick_count' => fn (Builder $query) => $query->where('status', 'sick'),
                'attendances as permission_count' => fn (Builder $query) => $query->where('status', 'permission'),
                'attendances as alpha_count' => fn (Builder $query) => $query->where('status', 'alpha'),
            ])
            ->when($user->role !== 'superadmin', fn (Builder $query) => $query->where('user_id', $user->id));
    }

    private function attendanceSummary(Builder $meetingQuery): array
    {
        $meetings = (clone $meetingQuery)->get();

        return [
            'present' => $meetings->sum('present_count'),
            'sick' => $meetings->sum('sick_count'),
            'permission' => $meetings->sum('permission_count'),
            'alpha' => $meetings->sum('alpha_count'),
            'total' => $meetings->sum('total_attendance_count'),
        ];
    }
}
