<?php

namespace App\Features\TeacherAttendances\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherAttendance extends Model
{
    use HasFactory;

    public const STATUSES = [
        'present' => 'Hadir',
        'late' => 'Terlambat',
        'permission' => 'Izin',
        'sick' => 'Sakit',
        'alpha' => 'Alpha',
    ];

    protected $fillable = [
        'user_id',
        'date',
        'check_in_at',
        'check_out_at',
        'status',
        'note',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'check_in_at' => 'datetime',
        'check_out_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
