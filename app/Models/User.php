<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Features\Payments\Models\Payment;
use App\Features\Grades\Models\Grade;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Meetings\Models\Attendance;
use App\Features\Meetings\Models\Meeting;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Permissions\Models\RolePermission;

// --- 1. TAMBAHAN IMPORT FILAMENT ---
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

// --- 2. TAMBAHKAN implements FilamentUser DI SINI ---
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    public const PANEL_PERMISSIONS = [
        'dashboard.view',
        'users.view',
        'users.create',
        'users.update',
        'users.delete',
        'users.manage',
        'spmb.view',
        'spmb.create',
        'spmb.update',
        'spmb.delete',
        'spmb.approve',
        'spmb.manage',
        'classes.view',
        'classes.create',
        'classes.update',
        'classes.delete',
        'classes.manage',
        'semesters.view',
        'semesters.create',
        'semesters.update',
        'semesters.delete',
        'semesters.manage',
        'meetings.view',
        'meetings.create',
        'meetings.update',
        'meetings.delete',
        'meetings.manage',
        'attendances.view',
        'attendances.create',
        'attendances.update',
        'attendances.delete',
        'attendances.manage',
        'assessments.view',
        'assessments.create',
        'assessments.update',
        'assessments.delete',
        'assessments.manage',
        'evaluations.view',
        'evaluations.create',
        'evaluations.update',
        'evaluations.delete',
        'evaluations.manage',
        'grades.view',
        'grades.create',
        'grades.update',
        'grades.delete',
        'grades.manage',
        'reports.view',
        'reports.download',
        'payments.view',
        'payments.create',
        'payments.update',
        'payments.delete',
        'payments.manage',
        'posts.view',
        'posts.create',
        'posts.update',
        'posts.delete',
        'posts.manage',
        'settings.view',
        'settings.update',
        'settings.manage',
    ];

    public const FILAMENT_FALLBACK_ROUTES = [
        'dashboard.view' => 'dashboard',
        'classes.view' => 'filament.admin.resources.class-groups.index',
        'classes.manage' => 'filament.admin.resources.class-groups.index',
        'meetings.view' => 'filament.admin.resources.meetings.index',
        'meetings.manage' => 'filament.admin.resources.meetings.index',
        'attendances.view' => 'filament.admin.resources.meetings.index',
        'attendances.manage' => 'filament.admin.resources.meetings.index',
        'assessments.view' => 'filament.admin.resources.assessments.index',
        'assessments.manage' => 'filament.admin.resources.assessments.index',
        'evaluations.view' => 'filament.admin.resources.evaluations.index',
        'evaluations.manage' => 'filament.admin.resources.evaluations.index',
        'grades.view' => 'filament.admin.resources.grades.index',
        'grades.manage' => 'filament.admin.resources.grades.index',
        'reports.view' => 'filament.admin.resources.raport.index',
        'payments.view' => 'filament.admin.resources.payments.index',
        'payments.manage' => 'filament.admin.resources.payments.index',
        'posts.view' => 'filament.admin.resources.posts.index',
        'posts.manage' => 'filament.admin.resources.posts.index',
        'spmb.view' => 'filament.admin.resources.candidates.index',
        'spmb.manage' => 'filament.admin.resources.candidates.index',
        'settings.view' => 'filament.admin.resources.site-settings.index',
        'settings.manage' => 'filament.admin.resources.site-settings.index',
        'semesters.view' => 'filament.admin.resources.semesters.index',
        'semesters.manage' => 'filament.admin.resources.semesters.index',
        'users.view' => 'filament.admin.resources.users.index',
        'users.manage' => 'filament.admin.resources.users.index',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',         // superadmin, guru, student
        'nisn',         // Khusus siswa
        'phone',
        'address',
        'is_active',    // Status aktif/tidak
        'gender',

        // --- TAMBAHAN DATA PPDB (WAJIB ADA DISINI) ---
        'grade_level',   // Jenjang (SD/SMP/SMA)
        'birth_date',    // Tanggal Lahir
        'mother_name',   // Nama Ibu
        'school_origin', // Asal Sekolah
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'birth_date' => 'date', // Agar otomatis jadi object Date (bukan string)
        ];
    }

    // --- RELASI UNTUK SISWA ---

    // Siswa punya banyak nilai
    public function grades()
    {
        return $this->hasMany(Grade::class);
    }

    // Siswa punya banyak pembayaran
    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    // Siswa punya banyak absensi
    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    // --- RELASI UNTUK GURU ---

    // Ustad membuat banyak pertemuan
    public function teachingMeetings()
    {
        return $this->hasMany(Meeting::class, 'user_id');
    }

    // Ustad mengajar banyak kelas
    public function classGroupsAsTeacher()
    {
        return $this->hasMany(ClassGroup::class, 'teacher_id');
    }

    // --- RELASI UNTUK SANTRI ---

    // Santri berada di banyak kelas
    public function classGroups()
    {
        return $this->belongsToMany(ClassGroup::class, 'class_group_student', 'user_id', 'class_group_id')
            ->wherePivot('deleted_at', null)
            ->withPivot('joined_at', 'deleted_at')
            ->withTimestamps();
    }

    // Santri punya banyak penilaian
    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    // Santri punya banyak evaluasi
    public function evaluations()
    {
        return $this->hasMany(Evaluation::class);
    }

    public function hasAccess(string $permission): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        if (! in_array($this->role, ['guru', 'student'], true)) {
            return false;
        }

        return RolePermission::query()
            ->where('role', $this->role)
            ->where('permission', $permission)
            ->where('is_allowed', true)
            ->exists();
    }

    public function hasAnyAccess(array $permissions): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        if (! in_array($this->role, ['guru', 'student'], true)) {
            return false;
        }

        return RolePermission::query()
            ->where('role', $this->role)
            ->whereIn('permission', $permissions)
            ->where('is_allowed', true)
            ->exists();
    }

    public function getFirstAllowedFilamentRoute(): ?string
    {
        if ($this->role === 'superadmin') {
            return route('filament.admin.home');
        }

        if ($this->role === 'student') {
            return route('dashboard');
        }

        if ($this->role !== 'guru') {
            return null;
        }

        foreach (self::FILAMENT_FALLBACK_ROUTES as $permission => $routeName) {
            if ($this->hasAccess($permission) && app('router')->has($routeName)) {
                return route($routeName);
            }
        }

        return null;
    }

    // --- 3. FUNGSI WAJIB FILAMENT UNTUK IZIN MASUK ---
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->role === 'superadmin') {
            return true;
        }

        return $this->role === 'guru'
            && $this->hasAnyAccess(self::PANEL_PERMISSIONS);
    }
}
