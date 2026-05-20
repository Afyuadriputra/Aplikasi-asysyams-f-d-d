<?php

namespace App\Features\Academic\Models;

use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Meetings\Models\Meeting;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClassGroup extends Model
{
    use HasFactory;

    public const TYPE_TAHSIN = 'tahsin';
    public const TYPE_BACA_TULIS = 'baca_tulis';
    public const TYPE_MUROTTAL = 'murottal';
    public const TYPE_TILAWAH = 'tilawah';

    public const CLASS_TYPES = [
        self::TYPE_TAHSIN => 'Kelas Tahsin',
        self::TYPE_BACA_TULIS => 'Kelas Baca Tulis',
        self::TYPE_MUROTTAL => 'Kelas Murottal',
        self::TYPE_TILAWAH => 'Kelas Tilawah',
    ];

    public const LETTERED_TYPES = [
        self::TYPE_MUROTTAL,
        self::TYPE_TILAWAH,
    ];

    protected $table = 'class_groups';

    protected $fillable = [
        'name',
        'slug',
        'class_type',
        'class_letter',
        'subject_id',
        'semester_id',
        'teacher_id',
        'description',
    ];

    protected static function booted(): void
    {
        static::saving(function (ClassGroup $classGroup): void {
            if (! $classGroup->class_type) {
                return;
            }

            $classGroup->class_letter = $classGroup->class_letter
                ? strtoupper((string) $classGroup->class_letter)
                : null;

            static::validateClassNaming($classGroup);

            $classGroup->name = static::generateNameFromTypeAndLetter(
                $classGroup->class_type,
                $classGroup->class_letter,
            );

            $classGroup->slug = Str::slug($classGroup->name . '-' . $classGroup->semester_id);
        });
    }

    public static function classTypeOptions(): array
    {
        return self::CLASS_TYPES;
    }

    public static function classLetterOptions(): array
    {
        return array_combine(range('A', 'Z'), range('A', 'Z'));
    }

    public static function classTypeLabel(?string $type): string
    {
        return self::CLASS_TYPES[$type] ?? '-';
    }

    public static function needsClassLetter(?string $type): bool
    {
        return in_array($type, self::LETTERED_TYPES, true);
    }

    public static function generateNameFromTypeAndLetter(?string $type, ?string $letter = null): string
    {
        $label = self::classTypeLabel($type);

        if (self::needsClassLetter($type) && $letter) {
            return $label . ' ' . strtoupper($letter);
        }

        return $label;
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->class_type) {
            return self::generateNameFromTypeAndLetter($this->class_type, $this->class_letter);
        }

        return $this->name;
    }

    private static function validateClassNaming(ClassGroup $classGroup): void
    {
        if (! array_key_exists($classGroup->class_type, self::CLASS_TYPES)) {
            throw ValidationException::withMessages([
                'class_type' => 'Jenis kelas tidak valid.',
            ]);
        }

        if (self::needsClassLetter($classGroup->class_type)) {
            if (! preg_match('/^[A-Z]$/', (string) $classGroup->class_letter)) {
                throw ValidationException::withMessages([
                    'class_letter' => 'Huruf kelas wajib A sampai Z untuk kelas Murottal dan Tilawah.',
                ]);
            }
        } elseif ($classGroup->class_letter !== null) {
            throw ValidationException::withMessages([
                'class_letter' => 'Kelas Tahsin dan Baca Tulis tidak boleh memakai huruf kelas.',
            ]);
        }

        $duplicateQuery = static::query()
            ->where('semester_id', $classGroup->semester_id)
            ->where('class_type', $classGroup->class_type)
            ->when(
                $classGroup->class_letter === null,
                fn ($query) => $query->whereNull('class_letter'),
                fn ($query) => $query->where('class_letter', $classGroup->class_letter),
            );

        if ($classGroup->exists) {
            $duplicateQuery->whereKeyNot($classGroup->getKey());
        }

        if ($duplicateQuery->exists()) {
            throw ValidationException::withMessages([
                'class_type' => 'Kelas dengan jenis, huruf, dan semester yang sama sudah ada.',
            ]);
        }
    }

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
