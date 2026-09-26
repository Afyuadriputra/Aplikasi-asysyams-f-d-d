<?php

namespace Tests\Feature\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Enums\AssessmentType;
use App\Features\Grades\Models\Assessment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentSchemaContractTest extends TestCase
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

    public function test_assessment_type_enum_has_canonical_values(): void
    {
        $expectedValues = ['ziyadah', 'murojaah', 'tahsin', 'tilawah', 'tahfidz', 'tajwid'];
        $this->assertEqualsCanonicalizing($expectedValues, AssessmentType::values());
    }

    public function test_all_canonical_assessment_types_can_be_stored(): void
    {
        foreach (AssessmentType::values() as $index => $type) {
            $assessment = Assessment::create([
                'class_group_id' => $this->classGroup->id,
                'user_id' => $this->student->id,
                'assessment_type' => $type,
                'month' => ($index % 12) + 1,
                'year' => 2026,
                'data' => [
                    ['surah' => 'Al-Fatihah', 'ayat' => '1-7', 'nilai' => 'L'],
                ],
            ]);

            $this->assertDatabaseHas('assessments', [
                'id' => $assessment->id,
                'assessment_type' => $type,
            ]);
        }
    }

    public function test_filament_assessment_resource_uses_canonical_assessment_type_options(): void
    {
        $options = AssessmentType::options();
        $this->assertArrayHasKey('ziyadah', $options);
        $this->assertArrayHasKey('murojaah', $options);
        $this->assertArrayHasKey('tahsin', $options);
        $this->assertArrayHasKey('tilawah', $options);
        $this->assertArrayHasKey('tahfidz', $options);
        $this->assertArrayHasKey('tajwid', $options);
    }
}