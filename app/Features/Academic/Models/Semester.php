<?php

namespace App\Features\Academic\Models;

use App\Features\Grades\Models\Grade;
use App\Features\Payments\Models\Payment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class Semester extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'is_active',
        'tuition_fee',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'tuition_fee' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::saving(function (Semester $semester): void {
            static::validateSemester($semester);

            if ($semester->is_active && (! $semester->exists || $semester->isDirty('is_active'))) {
                DB::transaction(function () use ($semester) {
                    static::withoutEvents(function () use ($semester) {
                        static::query()
                            ->when($semester->exists, fn ($q) => $q->whereKeyNot($semester->getKey()))
                            ->where('is_active', true)
                            ->update(['is_active' => false]);
                    });
                });
            }
        });
    }

    public static function validateSemester(Semester $semester): void
    {
        if ($semester->start_date && $semester->end_date) {
            $startDate = Carbon::parse($semester->start_date)->startOfDay();
            $endDate = Carbon::parse($semester->end_date)->startOfDay();

            if ($endDate->lt($startDate)) {
                throw ValidationException::withMessages([
                    'end_date' => 'Tanggal berakhir semester tidak boleh mendahului tanggal mulai.',
                ]);
            }
        }

        if ($semester->tuition_fee !== null && (float) $semester->tuition_fee < 0) {
            throw ValidationException::withMessages([
                'tuition_fee' => 'Biaya SPP tidak boleh bernilai negatif.',
            ]);
        }
    }

    // Semester punya banyak data nilai siswa
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // Semester punya banyak data pembayaran
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
