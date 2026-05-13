<?php

namespace App\Features\Academic\Models;

use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Meetings\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassGroup extends Model
{
    use HasFactory;

    protected $table = 'class_groups';

    protected $fillable = [
        'name',
        'slug',
        'subject_id',
        'semester_id',
        'teacher_id',
        'description',
    ];

    // Kelas milik subject apa? (Tilawah, Murottal, dst)
    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    // Kelas ini di semester berapa?
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }

    // Kelas diajar oleh ustad siapa?
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    // Kelas punya banyak santri
    public function students()
    {
        return $this->belongsToMany(User::class, 'class_group_student', 'class_group_id', 'user_id')
            ->wherePivot('deleted_at', null)
            ->withPivot('joined_at', 'deleted_at')
            ->withTimestamps();
            
    }

    // Kelas punya banyak penilaian
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    // Kelas punya banyak evaluasi
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

// app/Models/ClassGroup.php

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'class_group_id');
    }


}
