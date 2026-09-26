<?php

namespace Tests\Feature\Validation;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AcademicValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_semester_end_date_cannot_be_before_start_date(): void
    {
        $startDate = now();
        $invalidEndDate = now()->subMonth();

        $semester = Semester::create([
            'name' => 'Semester Invalid Dates',
            'start_date' => $startDate,
            'end_date' => $invalidEndDate,
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);

        $this->assertTrue(
            $semester->end_date->gte($semester->start_date),
            'Semester end_date must be greater than or equal to start_date'
        );
    }

    public function test_class_group_letter_must_be_single_uppercase_alphabet(): void
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create([
            'name' => 'Semester Valid Letter',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Subject Letter', 'slug' => 'subject-letter']);

        $class = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'b', // lowercase should be normalized to uppercase 'B'
        ]);

        $this->assertSame('B', $class->class_letter);
        $this->assertStringContainsString('Kelas Murottal B', $class->name);
    }

    public function test_class_group_rejects_multi_character_letter(): void
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create([
            'name' => 'Semester Multi Letter',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Subject Multi Letter', 'slug' => 'subject-multi-letter']);

        $this->expectException(ValidationException::class);

        ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => 'AB',
        ]);
    }

    public function test_class_group_rejects_numeric_or_special_character_letter(): void
    {
        $teacher = User::factory()->create(['role' => 'guru']);
        $semester = Semester::create([
            'name' => 'Semester Num Letter',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Subject Num Letter', 'slug' => 'subject-num-letter']);

        $this->expectException(ValidationException::class);

        ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'murottal',
            'class_letter' => '1',
        ]);
    }
}