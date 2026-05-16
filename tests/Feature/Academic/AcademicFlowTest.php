<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Meetings\Models\Meeting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AcademicFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_semester_with_valid_data()
    {
        $semester = Semester::create([
            'name' => 'Ganjil 2024',
            'year' => '2024/2025',
            'start_date' => '2024-07-01',
            'end_date' => '2024-12-31',
            'tuition_fee' => 500000,
            'is_active' => true
        ]);

        $this->assertDatabaseHas('semesters', ['name' => 'Ganjil 2024']);
    }

    public function test_can_create_subject_with_valid_data()
    {
        $subject = Subject::create([
            'name' => 'Tahfidz Qur\'an',
            'slug' => 'tahfidz-quran'
        ]);

        $this->assertDatabaseHas('subjects', ['name' => 'Tahfidz Qur\'an']);
    }

    public function test_can_create_class_group_with_relations()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create([
            'name' => 'S1', 
            'year' => '2024', 
            'start_date' => now(), 
            'end_date' => now(), 
            'is_active' => true
        ]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);

        $classGroup = ClassGroup::create([
            'name' => 'Kelas Tahfidz A',
            'slug' => 'kelas-tahfidz-a',
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $guru->id,
        ]);

        $this->assertDatabaseHas('class_groups', ['name' => 'Kelas Tahfidz A']);
        $this->assertEquals($subject->id, $classGroup->subject->id);
        $this->assertEquals($semester->id, $classGroup->semester->id);
        $this->assertEquals($guru->id, $classGroup->teacher->id);
    }

    public function test_class_group_can_have_many_students()
    {
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        $s1 = User::factory()->create(['role' => 'student']);
        $s2 = User::factory()->create(['role' => 'student']);

        $classGroup->students()->attach([$s1->id, $s2->id], ['joined_at' => now()]);

        $this->assertCount(2, $classGroup->students);
    }

    public function test_user_class_group_relationship_works_from_student_side()
    {
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);
        $student = User::factory()->create(['role' => 'student']);

        $student->classGroups()->attach($classGroup->id, ['joined_at' => now()]);

        $this->assertTrue($student->classGroups->contains($classGroup));
    }

    public function test_class_group_is_connected_to_meeting_assessment_and_evaluation()
    {
        $guru = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        $meeting = Meeting::create(['class_group_id' => $classGroup->id, 'user_id' => $guru->id, 'title' => 'T', 'date' => now()]);
        $assessment = Assessment::create([
            'class_group_id' => $classGroup->id, 
            'user_id' => $guru->id, 
            'assessment_type' => 'ziyadah',
            'data' => []
        ]);
        $evaluation = Evaluation::create([
            'class_group_id' => $classGroup->id, 
            'user_id' => $guru->id, 
            'evaluation_number' => 1,
            'items' => []
        ]);

        $this->assertTrue($classGroup->meetings->contains($meeting));
        $this->assertTrue($classGroup->assessments->contains($assessment));
        $this->assertTrue($classGroup->evaluations->contains($evaluation));
    }
}
