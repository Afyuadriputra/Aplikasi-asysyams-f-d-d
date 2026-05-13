<?php

namespace App\Features\Meetings\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'user_id',
        'status', // present, sick, permission, alpha
    ];

    // Absensi milik pertemuan mana?
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    // Absensi milik siswa siapa?
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
