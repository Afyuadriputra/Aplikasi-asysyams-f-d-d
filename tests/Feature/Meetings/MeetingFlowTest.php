<?php

namespace Tests\Feature\Meetings;

use App\Models\User;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Meetings\Models\Meeting;
use App\Features\Meetings\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MeetingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_meeting_relations_work_correctly()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Subj1', 'slug' => 'subj1']);
        
        $classGroup = ClassGroup::create([
            'name' => 'Class 1',
            'slug' => 'class-1',
            'teacher_id' => $guru->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'is_active' => true
        ]);

        $meeting = Meeting::create([
            'class_group_id' => $classGroup->id,
            'user_id' => $guru->id,
            'title' => 'Meeting Title',
            'date' => now()
        ]);

        $this->assertEquals($classGroup->id, $meeting->classGroup->id);
        $this->assertEquals($guru->id, $meeting->teacher->id);
    }

    public function test_attendance_relations_work_correctly()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $student = User::factory()->create(['role' => 'student']);
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Subj1', 'slug' => 'subj1']);
        
        $classGroup = ClassGroup::create([
            'name' => 'Class 1',
            'slug' => 'class-1',
            'teacher_id' => $guru->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'is_active' => true
        ]);

        $meeting = Meeting::create(['class_group_id' => $classGroup->id, 'user_id' => $guru->id, 'title' => 'T', 'date' => now()]);

        $attendance = Attendance::create([
            'meeting_id' => $meeting->id,
            'user_id' => $student->id,
            'status' => 'present'
        ]);

        $this->assertEquals($meeting->id, $attendance->meeting->id);
        $this->assertEquals($student->id, $attendance->student->id);
        $this->assertTrue($student->attendances->contains($attendance));
    }

    public function test_meeting_can_have_multiple_attendances()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Subj1', 'slug' => 'subj1']);
        
        $classGroup = ClassGroup::create([
            'name' => 'Class 1',
            'slug' => 'class-1',
            'teacher_id' => $guru->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'is_active' => true
        ]);

        $meeting = Meeting::create(['class_group_id' => $classGroup->id, 'user_id' => $guru->id, 'title' => 'T', 'date' => now()]);
        
        $s1 = User::factory()->create(['role' => 'student']);
        $s2 = User::factory()->create(['role' => 'student']);

        Attendance::create(['meeting_id' => $meeting->id, 'user_id' => $s1->id, 'status' => 'present']);
        Attendance::create(['meeting_id' => $meeting->id, 'user_id' => $s2->id, 'status' => 'alpha']);

        $this->assertCount(2, $meeting->attendances);
    }
}
