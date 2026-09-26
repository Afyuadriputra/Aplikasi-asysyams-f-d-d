<?php

namespace Tests\Feature\Academic;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $eligibleStudentsQuery = User::query()->where('role', 'student');

        $eligibleIds = (clone $eligibleStudentsQuery)->where('is_active', true)->pluck('id')->toArray();
        $actualIds = $eligibleStudentsQuery->pluck('id')->toArray();

        $this->assertEqualsCanonicalizing(
            $eligibleIds,
            $actualIds,
            'StudentsRelationManager options query must exclude inactive/pending students'
        );
    }
}