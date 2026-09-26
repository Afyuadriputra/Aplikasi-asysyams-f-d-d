<?php

namespace Tests\Feature\Academic;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Filament\Resources\ClassGroupResource\Pages\EditClassGroup;
use App\Filament\Resources\ClassGroupResource\RelationManagers\StudentsRelationManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class ClassGroupStudentEligibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_inactive_pending_students_must_not_be_eligible_for_class_assignment(): void
    {
        $activeStudent = User::factory()->create([
            'role' => 'student',
            'is_active' => true,
        ]);

        $pendingStudent = User::factory()->create([
            'role' => 'student',
            'is_active' => false,
        ]);

        $eligibleStudentsQuery = StudentsRelationManager::getEligibleStudentsQuery();

        $eligibleIds = (clone $eligibleStudentsQuery)->where('is_active', true)->pluck('id')->toArray();
        $actualIds = $eligibleStudentsQuery->pluck('id')->toArray();

        $this->assertEqualsCanonicalizing(
            $eligibleIds,
            $actualIds,
            'StudentsRelationManager options query must exclude inactive/pending students'
        );
        $this->assertContains($activeStudent->id, $actualIds);
        $this->assertNotContains($pendingStudent->id, $actualIds);
    }

    public function test_active_student_can_be_attached(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tahsin',
        ]);

        $activeStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);

        $classGroup->attachStudent($activeStudent);

        $this->assertTrue($classGroup->students()->where('users.id', $activeStudent->id)->exists());
    }

    public function test_inactive_pending_student_cannot_be_attached_through_tampered_request(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tahsin',
        ]);

        $inactiveStudent = User::factory()->create(['role' => 'student', 'is_active' => false]);

        $this->expectException(ValidationException::class);
        $classGroup->attachStudent($inactiveStudent);

        $this->assertFalse($classGroup->students()->where('users.id', $inactiveStudent->id)->exists());
    }

    public function test_non_student_user_cannot_be_attached(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tahsin',
        ]);

        $otherTeacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);

        $this->expectException(ValidationException::class);
        $classGroup->attachStudent($otherTeacher);

        $this->assertFalse($classGroup->students()->where('users.id', $otherTeacher->id)->exists());
    }

    public function test_already_attached_student_cannot_be_duplicated(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tahsin',
        ]);

        $activeStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $classGroup->attachStudent($activeStudent);

        $this->expectException(ValidationException::class);
        $classGroup->attachStudent($activeStudent);
    }

    public function test_existing_historical_membership_remains_readable_if_student_later_becomes_inactive(): void
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tahsin',
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $classGroup->attachStudent($student);

        $this->assertTrue($classGroup->fresh()->students()->where('users.id', $student->id)->exists());

        // Account becomes deactivated later
        $student->update(['is_active' => false]);

        // Historical membership is still preserved and readable
        $this->assertTrue($classGroup->fresh()->students()->where('users.id', $student->id)->exists());
        $this->assertCount(1, $classGroup->fresh()->students);
    }

    public function test_students_relation_manager_attach_action_halts_for_inactive_student(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
            'class_type' => 'tahsin',
        ]);

        $inactiveStudent = User::factory()->create(['role' => 'student', 'is_active' => false]);

        $this->actingAs($admin);

        Livewire::test(StudentsRelationManager::class, [
            'ownerRecord' => $classGroup,
            'pageClass' => EditClassGroup::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $inactiveStudent->id,
            ]);

        $this->assertFalse(
            $classGroup->fresh()->students()->where('users.id', $inactiveStudent->id)->exists(),
            'Inactive student must not be attached via relation manager action'
        );
    }

    public function test_student_cannot_join_two_classes_for_same_subject_and_semester(): void
    {
        $teacherA = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $teacherB = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Murottal', 'slug' => 'murottal']);
        $classA = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacherA->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $classB = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacherB->id,
            'class_type' => 'murottal',
            'class_letter' => 'B',
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $classA->attachStudent($student);

        $this->expectException(ValidationException::class);
        $classB->attachStudent($student);
    }

    public function test_students_relation_manager_halts_attaching_student_to_duplicate_subject_semester_class(): void
    {
        $admin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $teacherA = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $teacherB = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Semester Aktif',
            'start_date' => now(),
            'end_date' => now()->addMonths(6),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);
        $subject = Subject::create(['name' => 'Murottal', 'slug' => 'murottal']);
        $classA = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacherA->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $classB = ClassGroup::create([
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacherB->id,
            'class_type' => 'murottal',
            'class_letter' => 'B',
        ]);

        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $classA->attachStudent($student);

        $this->actingAs($admin);

        Livewire::test(StudentsRelationManager::class, [
            'ownerRecord' => $classB,
            'pageClass' => EditClassGroup::class,
        ])
            ->callTableAction('attach', data: [
                'recordId' => $student->id,
            ]);

        $this->assertFalse(
            $classB->fresh()->students()->where('users.id', $student->id)->exists(),
            'Student already in another class for same subject and semester must not be attached via relation manager'
        );
    }
}