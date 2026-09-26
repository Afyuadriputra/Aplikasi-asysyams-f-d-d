<?php

namespace Tests\Feature\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Features\Grades\Services\GradeCalculationService;
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

    public function test_grade_uses_40_60_weighting_from_grade_calculation_service(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        // Assessment average = 80
        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 80]],
        ]);

        // Evaluation average = 90
        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['name' => 'Tahsin', 'score' => 90, 'checked' => true]],
        ]);

        // Expected score: (80 * 0.40) + (90 * 0.60) = 32 + 54 = 86.0
        $grade = Grade::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->first();

        $this->assertNotNull($grade);
        $this->assertEquals(86.0, (float) $grade->score);
    }

    public function test_grade_is_upserted_not_duplicated(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 80]],
        ]);

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['name' => 'Test', 'score' => 90]],
        ]);

        // Second assessment in another month
        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 2,
            'year' => 2026,
            'data' => [['nilai' => 100]],
        ]);

        $count = Grade::where('user_id', $student->id)
            ->where('subject_id', $subject->id)
            ->where('semester_id', $semester->id)
            ->count();

        $this->assertSame(1, $count, 'Grade record must be upserted and not duplicated');
    }

    public function test_updating_assessment_recalculates_grade(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        $assessment = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 70]],
        ]);

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 80]],
        ]);

        // Initial: (70 * 0.4) + (80 * 0.6) = 28 + 48 = 76.0
        $grade = Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->first();
        $this->assertEquals(76.0, (float) $grade->score);

        // Update assessment to 100: (100 * 0.4) + (80 * 0.6) = 40 + 48 = 88.0
        $assessment->update([
            'data' => [['nilai' => 100]],
        ]);

        $this->assertEquals(88.0, (float) $grade->fresh()->score);
    }

    public function test_updating_evaluation_recalculates_grade(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 100]],
        ]);

        $evaluation = Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 70]],
        ]);

        // Initial: (100 * 0.4) + (70 * 0.6) = 40 + 42 = 82.0
        $grade = Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->first();
        $this->assertEquals(82.0, (float) $grade->score);

        // Update evaluation to 100: (100 * 0.4) + (100 * 0.6) = 40 + 60 = 100.0
        $evaluation->update([
            'items' => [['score' => 100]],
        ]);

        $this->assertEquals(100.0, (float) $grade->fresh()->score);
    }

    public function test_deleting_assessment_recalculates_grade(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 100]],
        ]);

        $assessment2 = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 2,
            'year' => 2026,
            'data' => [['nilai' => 60]],
        ]);

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 90]],
        ]);

        // Assessment avg: (100 + 60)/2 = 80. Score: (80 * 0.4) + (90 * 0.6) = 32 + 54 = 86.0
        $grade = Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->first();
        $this->assertEquals(86.0, (float) $grade->score);

        // Delete assessment2: Remaining assessment avg = 100. Score: (100 * 0.4) + (90 * 0.6) = 40 + 54 = 94.0
        $assessment2->delete();

        $this->assertEquals(94.0, (float) $grade->fresh()->score);
    }

    public function test_deleting_evaluation_recalculates_grade(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 90]],
        ]);

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 100]],
        ]);

        $eval2 = Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 2,
            'items' => [['score' => 80]],
        ]);

        // Evaluation avg: (100 + 80)/2 = 90. Assessment: 90. Grade = 90.0
        $grade = Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->first();
        $this->assertEquals(90.0, (float) $grade->score);

        // Delete eval2: Remaining evaluation avg = 100. Score: (90 * 0.4) + (100 * 0.6) = 36 + 60 = 96.0
        $eval2->delete();

        $this->assertEquals(96.0, (float) $grade->fresh()->score);
    }

    public function test_grade_is_scoped_to_correct_subject(): void
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

        $subjectTahsin = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $subjectTahfidz = Subject::create(['name' => 'Tahfidz', 'slug' => 'tahfidz']);

        $classTahsin = ClassGroup::create([
            'name' => 'Tahsin A',
            'slug' => 'tahsin-a',
            'subject_id' => $subjectTahsin->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $classTahsin->students()->attach($student->id);

        $classTahfidz = ClassGroup::create([
            'name' => 'Tahfidz B',
            'slug' => 'tahfidz-b',
            'subject_id' => $subjectTahfidz->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tilawah',
            'class_letter' => 'B',
        ]);
        $classTahfidz->students()->attach($student->id);

        // Class Tahsin: Assessment = 80, Evaluation = 80 -> Final = 80.0
        Assessment::create([
            'class_group_id' => $classTahsin->id,
            'user_id' => $student->id,
            'assessment_type' => 'tahsin',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 80]],
        ]);
        Evaluation::create([
            'class_group_id' => $classTahsin->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 80]],
        ]);

        // Class Tahfidz: Assessment = 95, Evaluation = 95 -> Final = 95.0
        Assessment::create([
            'class_group_id' => $classTahfidz->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 95]],
        ]);
        Evaluation::create([
            'class_group_id' => $classTahfidz->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 95]],
        ]);

        $gradeTahsin = Grade::where('user_id', $student->id)->where('subject_id', $subjectTahsin->id)->first();
        $gradeTahfidz = Grade::where('user_id', $student->id)->where('subject_id', $subjectTahfidz->id)->first();

        $this->assertNotNull($gradeTahsin);
        $this->assertNotNull($gradeTahfidz);
        $this->assertEquals(80.0, (float) $gradeTahsin->score);
        $this->assertEquals(95.0, (float) $gradeTahfidz->score);
    }

    public function test_grade_is_scoped_to_correct_semester(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);

        $sem1 = Semester::create([
            'name' => 'Semester 1',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->startOfYear()->addMonths(5),
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);

        $sem2 = Semester::create([
            'name' => 'Semester 2',
            'start_date' => now()->startOfYear()->addMonths(6),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $classSem1 = ClassGroup::create([
            'name' => 'Tahsin Sem 1',
            'slug' => 'tahsin-sem-1',
            'subject_id' => $subject->id,
            'semester_id' => $sem1->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $classSem1->students()->attach($student->id);

        $classSem2 = ClassGroup::create([
            'name' => 'Tahsin Sem 2',
            'slug' => 'tahsin-sem-2',
            'subject_id' => $subject->id,
            'semester_id' => $sem2->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'B',
        ]);
        $classSem2->students()->attach($student->id);

        // Sem 1: 85
        Assessment::create([
            'class_group_id' => $classSem1->id,
            'user_id' => $student->id,
            'assessment_type' => 'tahsin',
            'month' => 2,
            'year' => 2026,
            'data' => [['nilai' => 85]],
        ]);
        Evaluation::create([
            'class_group_id' => $classSem1->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 85]],
        ]);

        // Sem 2: 92
        $assessmentSem2 = Assessment::create([
            'class_group_id' => $classSem2->id,
            'user_id' => $student->id,
            'assessment_type' => 'tahsin',
            'month' => 8,
            'year' => 2026,
            'data' => [['nilai' => 92]],
        ]);
        Evaluation::create([
            'class_group_id' => $classSem2->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 92]],
        ]);

        $gradeSem1 = Grade::where('user_id', $student->id)->where('semester_id', $sem1->id)->first();
        $gradeSem2 = Grade::where('user_id', $student->id)->where('semester_id', $sem2->id)->first();

        $this->assertEquals(85.0, (float) $gradeSem1->score);
        $this->assertEquals(92.0, (float) $gradeSem2->score);

        // Mutating Sem 2 assessment does not affect Sem 1 grade
        $assessmentSem2->update(['data' => [['nilai' => 100]]]);

        $this->assertEquals(85.0, (float) $gradeSem1->fresh()->score);
        $this->assertNotEquals(92.0, (float) $gradeSem2->fresh()->score);
    }

    public function test_grade_does_not_use_other_student_data(): void
    {
        [$teacher, $studentA, $semester, $subject, $class] = $this->createClassContext();
        $studentB = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $class->students()->attach($studentB->id);

        // Student A: 100
        $assessA = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $studentA->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 100]],
        ]);
        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $studentA->id,
            'evaluation_number' => 1,
            'items' => [['score' => 100]],
        ]);

        // Student B: 70
        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $studentB->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 70]],
        ]);
        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $studentB->id,
            'evaluation_number' => 1,
            'items' => [['score' => 70]],
        ]);

        $gradeA = Grade::where('user_id', $studentA->id)->where('subject_id', $subject->id)->first();
        $gradeB = Grade::where('user_id', $studentB->id)->where('subject_id', $subject->id)->first();

        $this->assertEquals(100.0, (float) $gradeA->score);
        $this->assertEquals(70.0, (float) $gradeB->score);

        // Update Student A should NOT change Student B's grade
        $assessA->update(['data' => [['nilai' => 50]]]);

        $this->assertEquals(70.0, (float) $gradeB->fresh()->score);
    }

    public function test_grade_does_not_use_other_teacher_class_data(): void
    {
        $teacherA = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $teacherB = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $studentA = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $studentB = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $semester = Semester::create([
            'name' => 'Semester 1',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $subjectA = Subject::create(['name' => 'Subject A', 'slug' => 'subject-a']);
        $subjectB = Subject::create(['name' => 'Subject B', 'slug' => 'subject-b']);

        $classA = ClassGroup::create([
            'name' => 'Class A',
            'slug' => 'class-a',
            'subject_id' => $subjectA->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacherA->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $classA->students()->attach($studentA->id);

        $classB = ClassGroup::create([
            'name' => 'Class B',
            'slug' => 'class-b',
            'subject_id' => $subjectB->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacherB->id,
            'class_type' => 'murottal',
            'class_letter' => 'B',
        ]);
        $classB->students()->attach($studentB->id);

        Assessment::create([
            'class_group_id' => $classA->id,
            'user_id' => $studentA->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 95]],
        ]);
        Evaluation::create([
            'class_group_id' => $classA->id,
            'user_id' => $studentA->id,
            'evaluation_number' => 1,
            'items' => [['score' => 95]],
        ]);

        $gradeA = Grade::where('user_id', $studentA->id)->where('subject_id', $subjectA->id)->first();
        $this->assertEquals(95.0, (float) $gradeA->score);

        $gradeB = Grade::where('user_id', $studentB->id)->where('subject_id', $subjectB->id)->first();
        $this->assertNull($gradeB, 'Student B should not receive grade from Class A');
    }

    public function test_missing_component_does_not_fabricate_grade(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        // Only Assessment exists, Evaluation is missing
        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 100]],
        ]);

        $this->assertDatabaseMissing('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);
    }

    public function test_deleting_last_component_removes_stale_grade(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        $assessment = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 90]],
        ]);

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 90]],
        ]);

        $this->assertDatabaseHas('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);

        // Delete the only assessment
        $assessment->delete();

        // Stale grade must be removed
        $this->assertDatabaseMissing('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);
    }

    public function test_legacy_manual_grade_is_upserted_without_duplicates(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        // Existing legacy manual grade
        $manualGrade = Grade::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 75,
            'notes' => 'Catatan lama',
        ]);

        $this->assertSame(1, Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->count());

        // Now teacher inputs Assessment and Evaluation
        Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 90]],
        ]);

        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 90]],
        ]);

        // Grade row count must still be 1 (upserted, not duplicated)
        $this->assertSame(1, Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->count());
        $this->assertEquals(90.0, (float) $manualGrade->fresh()->score);
    }

    public function test_same_student_same_subject_semester_in_two_classes_does_not_silently_overwrite_grade(): void
    {
        $guruA = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $guruB = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $studentX = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $semester = Semester::create([
            'name' => 'Semester 1 2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $subjectTahsin = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);

        // Class A: Guru A, Tahsin, Semester 1
        $classA = ClassGroup::create([
            'name' => 'Tahsin Kelas A',
            'slug' => 'tahsin-kelas-a',
            'subject_id' => $subjectTahsin->id,
            'semester_id' => $semester->id,
            'teacher_id' => $guruA->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $classA->students()->attach($studentX->id);

        // Class B: Guru B, Tahsin, Semester 1
        $classB = ClassGroup::create([
            'name' => 'Tahsin Kelas B',
            'slug' => 'tahsin-kelas-b',
            'subject_id' => $subjectTahsin->id,
            'semester_id' => $semester->id,
            'teacher_id' => $guruB->id,
            'class_type' => 'murottal',
            'class_letter' => 'B',
        ]);

        // Create complete Assessment + Evaluation data for Class A (expected calculated score: 85)
        Assessment::create([
            'class_group_id' => $classA->id,
            'user_id' => $studentX->id,
            'assessment_type' => 'tahsin',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 85]],
        ]);
        Evaluation::create([
            'class_group_id' => $classA->id,
            'user_id' => $studentX->id,
            'evaluation_number' => 1,
            'items' => [['score' => 85]],
        ]);

        $gradeA = Grade::where('user_id', $studentX->id)
            ->where('subject_id', $subjectTahsin->id)
            ->where('semester_id', $semester->id)
            ->first();

        $this->assertNotNull($gradeA);
        $this->assertEquals(85.0, (float) $gradeA->score);

        // Student X is attached to Class B (simulating bypass/legacy duplicate enrollment)
        $classB->students()->attach($studentX->id);

        // Now attempt creating complete Assessment + Evaluation for Class B (expected calculated score: 92)
        // Pipeline must detect GRADE_IDENTITY_COLLISION and reject silent last-write-wins overwrite
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('GRADE_IDENTITY_COLLISION');

        try {
            Assessment::create([
                'class_group_id' => $classB->id,
                'user_id' => $studentX->id,
                'assessment_type' => 'tahsin',
                'month' => 2,
                'year' => 2026,
                'data' => [['nilai' => 92]],
            ]);
            Evaluation::create([
                'class_group_id' => $classB->id,
                'user_id' => $studentX->id,
                'evaluation_number' => 1,
                'items' => [['score' => 92]],
            ]);
        } finally {
            // Verify Class A score remains 85.0 and was not silently overwritten
            $freshGrade = Grade::where('user_id', $studentX->id)
                ->where('subject_id', $subjectTahsin->id)
                ->where('semester_id', $semester->id)
                ->first();
            $this->assertEquals(85.0, (float) $freshGrade->score);
        }
    }

    public function test_grade_database_identity_contract(): void
    {
        $this->assertTrue(
            \Illuminate\Support\Facades\Schema::hasColumns('grades', [
                'user_id', 'subject_id', 'semester_id', 'score', 'notes'
            ]),
            'Grades table must contain authoritative identity columns'
        );
    }

    public function test_duplicate_grade_identity_is_rejected_by_database(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        \Illuminate\Support\Facades\DB::table('grades')->insert([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 80,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        \Illuminate\Support\Facades\DB::table('grades')->insert([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 90,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_manual_grade_behavior_when_auto_recalculation_occurs(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        // 1. Auto calculated Grade = 82
        $assessment = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 82]],
        ]);
        Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 82]],
        ]);

        $grade = Grade::where('user_id', $student->id)->where('subject_id', $subject->id)->first();
        $this->assertEquals(82.0, (float) $grade->score);

        // 2. Guru manually edits Grade.score = 95
        $grade->update(['score' => 95]);
        $this->assertEquals(95.0, (float) $grade->fresh()->score);

        // 3. Later Assessment changes, new average = 87 (87 * 0.4 + 82 * 0.6 = 34.8 + 49.2 = 84.0)
        $assessment->update(['data' => [['nilai' => 87]]]);

        // Auto pipeline recalculates to 84.0 according to AUTO_AUTHORITATIVE rule
        $this->assertEquals(84.0, (float) $grade->fresh()->score);
    }

    public function test_grade_delete_behavior_respects_authority_policy(): void
    {
        [$teacher, $student, $semester, $subject, $class] = $this->createClassContext();

        // SCENARIO A: Complete Auto Grade exists -> Deleting component removes stale grade
        $assessmentA = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 1,
            'year' => 2026,
            'data' => [['nilai' => 90]],
        ]);
        $evalA = Evaluation::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'evaluation_number' => 1,
            'items' => [['score' => 90]],
        ]);

        $this->assertDatabaseHas('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);

        $assessmentA->delete();
        $this->assertDatabaseMissing('grades', [
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
        ]);
        $evalA->delete();

        // SCENARIO B: Manual Grade exists beforehand -> Deleting lonely incomplete assessment must NOT delete manual grade
        $manualGrade = Grade::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 77,
            'notes' => 'Catatan nilai manual murni',
        ]);

        $lonelyAssessment = Assessment::create([
            'class_group_id' => $class->id,
            'user_id' => $student->id,
            'assessment_type' => 'ziyadah',
            'month' => 2,
            'year' => 2026,
            'data' => [['nilai' => 100]],
        ]);

        $this->assertEquals(77.0, (float) $manualGrade->fresh()->score);

        $lonelyAssessment->delete();

        // Authority policy: Manual grade must NOT be deleted silently!
        $this->assertDatabaseHas('grades', [
            'id' => $manualGrade->id,
            'user_id' => $student->id,
            'score' => 77,
        ]);
    }

    private function createClassContext(): array
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester 1 2026/2027',
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

        return [$teacher, $student, $semester, $subject, $class];
    }
}