<?php

namespace App\Features\Payments\Models;

use App\Models\User;
use App\Features\Academic\Models\Semester;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'user_id',
        'semester_id',
        'amount',
        'status',
        'snap_token',
        'payment_type',
        'payment_detail',
    ];

    protected $casts = [
        'payment_detail' => 'array', // Agar data JSON otomatis jadi Array PHP
        'amount' => 'decimal:2',
    ];

    // Pembayaran milik siswa siapa?
    public function student()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Pembayaran untuk semester berapa?
    public function semester()
    {
        return $this->belongsTo(Semester::class);
    }
}
