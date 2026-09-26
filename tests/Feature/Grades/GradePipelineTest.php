<?php

namespace Tests\Feature\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradePipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_assessment_does_not_automatically_sync_to_grades_table(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $semester = Semester::create([
            'name' => 'Semester 1',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $subject = Subject::create(['name' => 'Tahfidz', 'slug' => 'tahfidz']);

        $class = ClassGroup::create([
            'name' => 'Tahfidz A',
            'slug' => 'tahfidz-a',
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $class->students()->attach($student->id);

        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 9,
            'year' => 2026,
            'data' => [['surah' => 'Al-Fatihah', 'ayat' => '1-7', 'nilai' => 'L']],
        ]);

        $this->assertDatabaseMissing('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
        ]);
    }
}