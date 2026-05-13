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
        'items',
    ];

    protected $casts = [
        'items' => 'array',
    ];

    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function classGroup()
    {
        return $this->belongsTo(\App\Features\Academic\Models\ClassGroup::class);
    }
}
