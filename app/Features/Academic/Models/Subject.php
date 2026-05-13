<?php

namespace App\Features\Academic\Models;

use App\Features\Grades\Models\Grade;
use App\Features\Meetings\Models\Meeting;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
    ];

    // Mapel punya banyak pertemuan
    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    // Mapel punya banyak nilai siswa
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }
}
