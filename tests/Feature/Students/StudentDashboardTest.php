<?php

namespace Tests\Feature\Students;

use App\Models\User;
use App\Features\Academic\Models\Semester;
use App\Features\Meetings\Models\Attendance;
use App\Features\Meetings\Models\Meeting;
use App\Features\Academic\Models\Subject;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Grade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_student_dashboard()
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    public function test_student_can_see_dashboard_with_summary()
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        
        $semester = Semester::create([
            'name' => 'Ganjil',
            'year' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        $guru = User::factory()->create(['role' => 'guru']);
        $subject = Subject::create(['name' => 'S', 'slug' => 's']);
        $classGroup = ClassGroup::create([
            'name' => 'C', 
            'slug' => 'c', 
            'teacher_id' => $guru->id, 
            'subject_id' => $subject->id, 
            'semester_id' => $semester->id,
            'is_active' => true
        ]);

        // Mock attendance
        $meeting = Meeting::create([
            'class_group_id' => $classGroup->id,
            'user_id' => $guru->id,
            'title' => 'Meeting 1',
            'date' => now()
        ]);
        Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $student->id,
            'status' => 'present'
        ]);

        // Mock grade
        Grade::create([
            'user_id' => $student->id,
            'semester_id' => $semester->id,
            'subject_id' => $subject->id,
            'class_group_id' => $classGroup->id,
            'score' => 90
        ]);

        $this->actingAs($student);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee($student->name);
        $response->assertSee('90'); // Average score
        $response->assertSee('1'); // Present count
    }

    public function test_student_only_sees_their_own_attendance_and_grade()
    {
        $student1 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $student2 = User::factory()->create(['role' => 'student', 'is_active' => true]);
        
        $semester = Semester::create([
            'name' => 'Ganjil',
            'year' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true
        ]);

        $guru = User::factory()->create(['role' => 'guru']);
        $subject = Subject::create(['name' => 'S2', 'slug' => 's2']);
        $classGroup = ClassGroup::create([
            'name' => 'C2', 
            'slug' => 'c2', 
            'teacher_id' => $guru->id, 
            'subject_id' => $subject->id, 
            'semester_id' => $semester->id,
            'is_active' => true
        ]);

        // Student 2 has attendance and grade
        $meeting = Meeting::create(['class_group_id' => $classGroup->id, 'user_id' => $guru->id, 'title' => 'M1', 'date' => now()]);
        Attendance::create(['meeting_id' => $meeting->id, 'user_id' => $student2->id, 'status' => 'present']);
        Grade::create(['user_id' => $student2->id, 'semester_id' => $semester->id, 'subject_id' => $subject->id, 'class_group_id' => $classGroup->id, 'score' => 99.99]);

        // Student 1 has nothing
        $this->actingAs($student1);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('0'); // Present count for student 1
        $response->assertSee('0'); // Average score for student 1
        $response->assertDontSee('99.99'); // Should not see student 2's grade
    }

    public function test_dashboard_does_not_error_when_data_is_empty()
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        
        $this->actingAs($student);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('0');
    }

    public function test_guru_sees_teacher_dashboard_view_data()
    {
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $otherGuru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $otherStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $semester = Semester::create([
            'name' => 'Ganjil Guru',
            'year' => '2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
        ]);

        $subject = Subject::create(['name' => 'Tahsin Dashboard', 'slug' => 'tahsin-dashboard']);
        $otherSubject = Subject::create(['name' => 'Tahfidz Dashboard', 'slug' => 'tahfidz-dashboard']);
        $classGroup = ClassGroup::create([
            'name' => 'Tahsin Dashboard A',
            'slug' => 'tahsin-dashboard-a',
            'teacher_id' => $guru->id,
            'subject_id' => $otherSubject->id,
            'semester_id' => $semester->id,
        ]);
        $otherClassGroup = ClassGroup::create([
            'name' => 'Kelas Guru Lain',
            'slug' => 'kelas-guru-lain',
            'teacher_id' => $otherGuru->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);

        $meeting = Meeting::create([
            'class_group_id' => $classGroup->id,
            'user_id' => $guru->id,
            'title' => 'Latihan Mad Dashboard',
            'date' => now(),
        ]);
        $otherMeeting = Meeting::create([
            'class_group_id' => $otherClassGroup->id,
            'user_id' => $otherGuru->id,
            'title' => 'Jadwal Guru Lain',
            'date' => now(),
        ]);

        Attendance::create(['meeting_id' => $meeting->id, 'user_id' => $student->id, 'status' => 'present']);
        Attendance::create(['meeting_id' => $meeting->id, 'user_id' => $otherStudent->id, 'status' => 'sick']);
        Attendance::create(['meeting_id' => $otherMeeting->id, 'user_id' => $student->id, 'status' => 'alpha']);
        
        $this->actingAs($guru);
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewHas('totalMeetings');
        $response->assertViewHas('todayClasses');
        $response->assertViewHas('scheduleMeetings');
        $response->assertViewHas('attendanceSummary', [
            'present' => 1,
            'sick' => 1,
            'permission' => 0,
            'alpha' => 0,
            'total' => 2,
        ]);
        $response->assertSee('Jadwal & Absensi', false);
        $response->assertSee('Latihan Mad Dashboard');
        $response->assertDontSee('Jadwal Guru Lain');
        $response->assertViewMissing('presentCount');
    }
}
