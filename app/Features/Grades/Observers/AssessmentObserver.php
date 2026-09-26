<?php

namespace App\Features\Grades\Observers;

use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Services\GradeSynchronizationService;

class AssessmentObserver
{
    public function __construct(
        protected GradeSynchronizationService $syncService
    ) {}

    public function saved(Assessment $assessment): void
    {
        if ($assessment->user_id && $assessment->class_group_id) {
            $this->syncService->syncForStudentClass($assessment->user_id, $assessment->class_group_id, false);
        }

        if ($assessment->wasChanged('user_id') || $assessment->wasChanged('class_group_id')) {
            $prevUserId = $assessment->getOriginal('user_id');
            $prevClassGroupId = $assessment->getOriginal('class_group_id');
            if ($prevUserId && $prevClassGroupId) {
                $this->syncService->syncForStudentClass($prevUserId, $prevClassGroupId, true);
            }
        }
    }

    public function deleted(Assessment $assessment): void
    {
        if ($assessment->user_id && $assessment->class_group_id) {
            $this->syncService->syncForStudentClass($assessment->user_id, $assessment->class_group_id, true);
        }
    }

    public function restored(Assessment $assessment): void
    {
        if ($assessment->user_id && $assessment->class_group_id) {
            $this->syncService->syncForStudentClass($assessment->user_id, $assessment->class_group_id, false);
        }
    }
}
