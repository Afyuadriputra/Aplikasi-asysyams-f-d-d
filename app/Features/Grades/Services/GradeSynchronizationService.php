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

        // MISSING COMPONENT POLICY (CONSERVATIVE_DOMAIN_POLICY):
        // Auto final grade is only calculated/synced when BOTH Assessment AND Evaluation are present.
        // If neither exists or only one exists, do not fabricate a final score.
        if (! $hasAssessments || ! $hasEvaluations) {
            if ($isDelete) {
                // If a component was deleted such that no assessments or evaluations remain,
                // delete the calculated Grade to prevent leaving a stale grade behind.
                Grade::query()
                    ->where('user_id', $studentId)
                    ->where('subject_id', $subjectId)
                    ->where('semester_id', $semesterId)
                    ->delete();
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
