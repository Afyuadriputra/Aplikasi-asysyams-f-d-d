<?php

namespace App\Features\Grades\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'class_group_id',
        'evaluation_number',
        'surah_name',
        'song_name',
        'items',
        'scores',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function setScoresAttribute(mixed $value): void
    {
        if (is_array($value)) {
            $existingItems = $this->items ?? [];
            if (is_string($existingItems)) {
                $existingItems = json_decode($existingItems, true) ?? [];
            }

            foreach ($value as $key => $score) {
                $existingItems[] = [
                    'name' => is_string($key) ? $key : 'Item',
                    'checked' => true,
                    'score' => (float) $score,
                ];
            }

            $this->attributes['items'] = json_encode($existingItems);
        }
    }

    public function getScoresAttribute(): array
    {
        $scores = [];
        $items = $this->items ?? [];
        if (is_string($items)) {
            $items = json_decode($items, true) ?? [];
        }

        foreach ($items as $item) {
            if (isset($item['name']) && isset($item['score'])) {
                $scores[$item['name']] = $item['score'];
            }
        }

        return $scores;
    }

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classGroup()
    {
        return $this->belongsTo(\App\Features\Academic\Models\ClassGroup::class);
    }
}
