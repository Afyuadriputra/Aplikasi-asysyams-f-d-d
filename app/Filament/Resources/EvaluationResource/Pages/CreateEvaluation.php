<?php

namespace App\Filament\Resources\EvaluationResource\Pages;

use App\Filament\Resources\EvaluationResource;
use App\Features\Grades\Models\Evaluation;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateEvaluation extends CreateRecord
{
    protected static string $resource = EvaluationResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $userIds = $data['user_ids'] ?? [];
        $firstRecord = null;

        foreach ($userIds as $userId) {
            $record = Evaluation::create([
                'class_group_id' => $data['class_group_id'],
                'user_id' => $userId,
                'evaluation_number' => $data['evaluation_number'],
                'items' => $data['items'] ?? [],
            ]);

            if (!$firstRecord) {
                $firstRecord = $record;
            }
        }

        Notification::make()
            ->title('Evaluasi berhasil dibuat untuk ' . count($userIds) . ' santri!')
            ->success()
            ->send();

        return $firstRecord;
    }
}
