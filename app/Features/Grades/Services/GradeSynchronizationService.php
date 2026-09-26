<?php

namespace App\Features\Grades\Services;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GradeSynchronizationService
{
    public function __construct(
        protected GradeCalculationService $calculationService
    ) {}

    /**
     * Synchronize Grade for a student within a specific ClassGroup.
     *
     * @param User|int $student
     * @param ClassGroup|int $classGroup
     * @param bool $isDelete
     * @return Grade|null
     */
    public function syncForStudentClass(User|int $student, ClassGroup|int $classGroup, bool $isDelete = false): ?Grade
    {
        $studentId = $student instanceof User ? $student->id : (int) $student;
        $classGroupModel = $classGroup instanceof ClassGroup 
            ? $classGroup 
            : ClassGroup::query()->find($classGroup);

        if (! $classGroupModel || ! $studentId) {
            return null;
        }

        $subjectId = $classGroupModel->subject_id;
        $semesterId = $classGroupModel->semester_id;

        if (! $subjectId || ! $semesterId) {
            return null;
        }

        // Query assessments and evaluations scoped strictly to this student and class group
        $assessments = Assessment::query()
            ->where('class_group_id', $classGroupModel->id)
            ->where('user_id', $studentId)
            ->get();

        $evaluations = Evaluation::query()
            ->where('class_group_id', $classGroupModel->id)
            ->where('user_id', $studentId)
            ->get();

        $hasAssessments = $assessments->isNotEmpty();
        $hasEvaluations = $evaluations->isNotEmpty();

        // MULTI-CLASS COLLISION DETECTION (HIGH DATA INTEGRITY):
        // If student is enrolled in multiple classes for the same subject and semester,
        // do not silently overwrite grade data across classes.
        $duplicateClassCount = ClassGroup::query()
            ->where('id', '!=', $classGroupModel->id)
            ->where('subject_id', $subjectId)
            ->where('semester_id', $semesterId)
            ->whereHas('students', fn ($q) => $q->where('users.id', $studentId))
            ->count();

        if ($duplicateClassCount > 0) {
            throw new \DomainException(
                "GRADE_IDENTITY_COLLISION: Student {$studentId} belongs to multiple classes for subject {$subjectId} and semester {$semesterId}."
            );
        }

        // MISSING COMPONENT POLICY (CONSERVATIVE_DOMAIN_POLICY):
        // Auto final grade is only calculated/synced when BOTH Assessment AND Evaluation are present.
        // If neither exists or only one exists, do not fabricate a final score.
        if (! $hasAssessments || ! $hasEvaluations) {
            if ($isDelete) {
                // If a component was deleted such that one component is missing,
                // only delete the grade if the other component still exists (meaning an auto-calculated
                // grade previously had both components and is now incomplete).
                // If neither component exists or was never complete, do NOT delete an existing manual grade.
                $otherComponentExisted = ($hasAssessments || $hasEvaluations);
                if ($otherComponentExisted) {
                    Grade::query()
                        ->where('user_id', $studentId)
                        ->where('subject_id', $subjectId)
                        ->where('semester_id', $semesterId)
                        ->delete();
                }
            }

            return null;
        }

        $finalScore = $this->calculationService->calculateFinalGrade($assessments, $evaluations);

        return DB::transaction(function () use ($studentId, $subjectId, $semesterId, $finalScore) {
            return Grade::updateOrCreate(
                [
                    'user_id' => $studentId,
                    'subject_id' => $subjectId,
                    'semester_id' => $semesterId,
                ],
                [
                    'score' => round($finalScore, 2),
                ]
            );
        });
    }
}
