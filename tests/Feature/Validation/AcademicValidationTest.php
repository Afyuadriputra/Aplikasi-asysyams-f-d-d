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
        $this->expectException(ValidationException::class);

        Semester::create([
            'name' => 'Semester Invalid Dates',
            'start_date' => now(),
            'end_date' => now()->subMonth(),
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);
    }

    public function test_semester_end_date_after_start_date_is_allowed(): void
    {
        $startDate = now()->startOfDay();
        $endDate = now()->addMonths(6)->startOfDay();

        $semester = Semester::create([
            'name' => 'Semester Valid Dates',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);

        $this->assertTrue(
            $semester->end_date->gte($semester->start_date),
            'Semester end_date must be greater than or equal to start_date'
        );
        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'name' => 'Semester Valid Dates',
        ]);
    }

    public function test_semester_end_date_equal_start_date_is_allowed(): void
    {
        $sameDate = now()->startOfDay();

        $semester = Semester::create([
            'name' => 'Semester Same Day',
            'start_date' => $sameDate,
            'end_date' => $sameDate,
            'is_active' => false,
            'tuition_fee' => 500000,
        ]);

        $this->assertTrue(
            $semester->end_date->equalTo($semester->start_date),
            'Semester end_date equal to start_date must be allowed'
        );
        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'name' => 'Semester Same Day',
        ]);
    }

    public function test_negative_tuition_fee_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        Semester::create([
            'name' => 'Semester Negative Fee',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => false,
            'tuition_fee' => -100000,
        ]);
    }

    public function test_zero_tuition_fee_is_allowed(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Free',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => false,
            'tuition_fee' => 0,
        ]);

        $this->assertEquals(0, (float) $semester->tuition_fee);
        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'name' => 'Semester Free',
        ]);
    }

    public function test_positive_tuition_fee_is_allowed(): void
    {
        $semester = Semester::create([
            'name' => 'Semester Paid Fee',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => false,
            'tuition_fee' => 750000,
        ]);

        $this->assertEquals(750000, (float) $semester->tuition_fee);
        $this->assertDatabaseHas('semesters', [
            'id' => $semester->id,
            'name' => 'Semester Paid Fee',
        ]);
    }

    public function test_invalid_active_semester_does_not_deactivate_current_active_semester(): void
    {
        $currentActiveSemester = Semester::create([
            'name' => 'Semester Aktif Tetap',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $this->assertTrue($currentActiveSemester->fresh()->is_active);

        try {
            Semester::create([
                'name' => 'Semester Invalid Mencoba Aktif',
                'start_date' => now(),
                'end_date' => now()->subMonth(),
                'is_active' => true,
                'tuition_fee' => 500000,
            ]);
            $this->fail('Expected ValidationException was not thrown for invalid semester dates.');
        } catch (ValidationException $e) {
            // Expected validation failure
        }

        $this->assertTrue(
            $currentActiveSemester->fresh()->is_active,
            'Current active semester must not be deactivated when creating an invalid active semester'
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