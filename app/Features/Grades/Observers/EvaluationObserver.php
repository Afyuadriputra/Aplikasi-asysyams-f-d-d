<?php

namespace App\Features\Grades\Observers;

use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Services\GradeSynchronizationService;

class EvaluationObserver
{
    public function __construct(
        protected GradeSynchronizationService $syncService
    ) {}

    public function saved(Evaluation $evaluation): void
    {
        if ($evaluation->user_id && $evaluation->class_group_id) {
            $this->syncService->syncForStudentClass($evaluation->user_id, $evaluation->class_group_id, false);
        }

        if ($evaluation->wasChanged('user_id') || $evaluation->wasChanged('class_group_id')) {
            $prevUserId = $evaluation->getOriginal('user_id');
            $prevClassGroupId = $evaluation->getOriginal('class_group_id');
            if ($prevUserId && $prevClassGroupId) {
                $this->syncService->syncForStudentClass($prevUserId, $prevClassGroupId, true);
            }
        }
    }

    public function deleted(Evaluation $evaluation): void
    {
        if ($evaluation->user_id && $evaluation->class_group_id) {
            $this->syncService->syncForStudentClass($evaluation->user_id, $evaluation->class_group_id, true);
        }
    }

    public function restored(Evaluation $evaluation): void
    {
        if ($evaluation->user_id && $evaluation->class_group_id) {
            $this->syncService->syncForStudentClass($evaluation->user_id, $evaluation->class_group_id, false);
        }
    }
}
