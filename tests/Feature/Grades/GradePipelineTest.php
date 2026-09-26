<?php

namespace Tests\Feature\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradePipelineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Business Pipeline Gap (P3):
     * When Assessment and Evaluation records are created/updated,
     * the system is expected to automatically calculate and synchronize Grade.score.
     * Currently, no observer/service orchestrator connects Assessment/Evaluation to Grade,
     * so this test must remain RED until the P3 grading pipeline is implemented.
     */
    public function test_assessment_and_evaluation_pipeline_automatically_calculates_and_syncs_grade(): void
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

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'date' => now()->toDateString(),
            'scores' => [
                'adab' => 95,
                'tajwid' => 90,
            ],
        ]);

        // EXPECTED INTENDED PIPELINE BEHAVIOR:
        // A Grade record should be created/updated with the aggregated score for this student in this subject & semester.
        $this->assertDatabaseHas('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);

        $grade = Grade::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->first();

        $this->assertNotNull($grade);
        $this->assertGreaterThan(0, $grade->score);
    }
}