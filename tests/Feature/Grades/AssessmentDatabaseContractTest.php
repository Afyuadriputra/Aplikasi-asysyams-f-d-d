<?php

namespace Tests\Feature\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AssessmentDatabaseContractTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;
    private ClassGroup $classGroup;

    protected function setUp(): void
    {
        parent::setUp();

        $this->teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $this->student = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $semester = Semester::create([
            'name' => 'Semester 1 2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $subject = Subject::create(['name' => 'Al-Quran', 'slug' => 'al-quran']);

        $this->classGroup = ClassGroup::create([
            'name' => 'Tahfidz A',
            'slug' => 'tahfidz-a',
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $this->teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $this->classGroup->students()->attach($this->student->id, ['joined_at' => now()]);
    }

    public function test_assessments_table_has_month_and_year_columns(): void
    {
        $this->assertTrue(Schema::hasColumn('assessments', 'month'), 'assessments table must have month column');
        $this->assertTrue(Schema::hasColumn('assessments', 'year'), 'assessments table must have year column');
    }

    public function test_month_and_year_are_mass_assignable_and_cast_properly(): void
    {
        $assessment = Assessment::create([
            'class_group_id' => $this->classGroup->id,
            'user_id' => $this->student->id,
            'assessment_type' => 'ziyadah',
            'month' => 9,
            'year' => 2026,
            'data' => [
                ['surah' => 'Al-Baqarah', 'ayat' => '1-10', 'nilai' => 'L'],
            ],
        ]);

        $this->assertDatabaseHas('assessments', [
            'id' => $assessment->id,
            'month' => 9,
            'year' => 2026,
        ]);

        $fresh = $assessment->fresh();
        $this->assertSame(9, $fresh->month);
        $this->assertSame(2026, $fresh->year);
    }

    public function test_assessments_can_be_stored_for_different_months(): void
    {
        $assessmentSep = Assessment::create([
            'class_group_id' => $this->classGroup->id,
            'user_id' => $this->student->id,
            'assessment_type' => 'ziyadah',
            'month' => 9,
            'year' => 2026,
            'data' => [['surah' => 'Al-Baqarah', 'ayat' => '1-10', 'nilai' => 'L']],
        ]);

        $assessmentOct = Assessment::create([
            'class_group_id' => $this->classGroup->id,
            'user_id' => $this->student->id,
            'assessment_type' => 'ziyadah',
            'month' => 10,
            'year' => 2026,
            'data' => [['surah' => 'Al-Baqarah', 'ayat' => '11-20', 'nilai' => 'L']],
        ]);

        $this->assertNotNull($assessmentSep->id);
        $this->assertNotNull($assessmentOct->id);
        $this->assertNotEquals($assessmentSep->id, $assessmentOct->id);
    }
}