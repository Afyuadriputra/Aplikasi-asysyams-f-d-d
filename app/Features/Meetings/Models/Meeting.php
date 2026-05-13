<?php

namespace App\Features\Meetings\Models;

use App\Features\Academic\Models\ClassGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    use HasFactory;

    // Pastikan 'subject_id' diganti menjadi 'class_group_id'
    protected $fillable = [
        'class_group_id',
        'user_id',
        'title',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relasi ke Kelas (Menggantikan Mata Pelajaran)
     */
    public function classGroup()
    {
        return $this->belongsTo(ClassGroup::class, 'class_group_id');
    }

    /**
     * Pertemuan dibuat oleh siapa (Ustad)?
     */
    public function teacher()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Pertemuan punya banyak absensi siswa
     */
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}
