<?php

namespace App\Features\Grades\Models;

use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'subject_id',
        'semester_id',
        'score',
        'notes',
    ];

    protected $casts = [
        'score' => 'float',
    ];

    // Nilai milik siswa siapa?
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Nilai pelajaran apa?
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Nilai semester berapa?
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
