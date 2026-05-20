<?php

namespace App\Features\Permissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RolePermission extends Model
{
    use HasFactory;

    public const ROLES = [
        'guru' => 'Guru',
        'student' => 'Student',
    ];

    public const PERMISSION_GROUPS = [
        'Dashboard' => [
            'dashboard.view' => 'Lihat Dashboard',
        ],
        'User' => [
            'users.view' => 'Lihat Pengguna',
            'users.create' => 'Tambah Pengguna',
            'users.update' => 'Edit Pengguna',
            'users.delete' => 'Hapus Pengguna',
            'users.manage' => 'Kelola Semua Pengguna',
        ],
        'SPMB' => [
            'spmb.view' => 'Lihat SPMB',
            'spmb.create' => 'Tambah Data SPMB',
            'spmb.update' => 'Edit Data SPMB',
            'spmb.delete' => 'Hapus Data SPMB',
            'spmb.approve' => 'Terima Calon Siswa',
            'spmb.manage' => 'Kelola Semua SPMB',
        ],
        'Akademik' => [
            'classes.view' => 'Lihat Kelas',
            'classes.create' => 'Tambah Kelas',
            'classes.update' => 'Edit Kelas',
            'classes.delete' => 'Hapus Kelas',
            'classes.manage' => 'Kelola Semua Kelas',
            'semesters.view' => 'Lihat Semester',
            'semesters.create' => 'Tambah Semester',
            'semesters.update' => 'Edit Semester',
            'semesters.delete' => 'Hapus Semester',
            'semesters.manage' => 'Kelola Semua Semester',
            'meetings.view' => 'Lihat Pertemuan',
            'meetings.create' => 'Tambah Pertemuan',
            'meetings.update' => 'Edit Pertemuan',
            'meetings.delete' => 'Hapus Pertemuan',
            'meetings.manage' => 'Kelola Semua Pertemuan',
        ],
        'Absensi' => [
            'attendances.view' => 'Lihat Absensi',
            'attendances.create' => 'Tambah Absensi',
            'attendances.update' => 'Edit Absensi',
            'attendances.delete' => 'Hapus Absensi',
            'attendances.manage' => 'Kelola Semua Absensi',
            'teacher-attendances.view' => 'Lihat Absensi Ustad',
            'teacher-attendances.create' => 'Tambah Absensi Ustad',
            'teacher-attendances.update' => 'Edit Absensi Ustad',
            'teacher-attendances.delete' => 'Hapus Absensi Ustad',
            'teacher-attendances.manage' => 'Kelola Semua Absensi Ustad',
            'teacher-attendances.check-in' => 'Check In/Out Absensi Ustad',
            'teacher-attendances.report' => 'Laporan Absensi Ustad',
        ],
        'Assessment & Evaluation' => [
            'assessments.view' => 'Lihat Assessment',
            'assessments.create' => 'Tambah Assessment',
            'assessments.update' => 'Edit Assessment',
            'assessments.delete' => 'Hapus Assessment',
            'assessments.manage' => 'Kelola Semua Assessment',
            'evaluations.view' => 'Lihat Evaluasi',
            'evaluations.create' => 'Tambah Evaluasi',
            'evaluations.update' => 'Edit Evaluasi',
            'evaluations.delete' => 'Hapus Evaluasi',
            'evaluations.manage' => 'Kelola Semua Evaluasi',
        ],
        'Nilai & Rapor' => [
            'grades.view' => 'Lihat Nilai',
            'grades.create' => 'Tambah Nilai',
            'grades.update' => 'Edit Nilai',
            'grades.delete' => 'Hapus Nilai',
            'grades.manage' => 'Kelola Semua Nilai',
            'reports.view' => 'Lihat Rapor',
            'reports.download' => 'Unduh Rapor',
        ],
        'Pembayaran' => [
            'payments.view' => 'Lihat Pembayaran',
            'payments.create' => 'Tambah Pembayaran',
            'payments.update' => 'Edit Pembayaran',
            'payments.delete' => 'Hapus Pembayaran',
            'payments.manage' => 'Kelola Semua Pembayaran',
            'payments.checkout' => 'Checkout Pembayaran',
        ],
        'Berita' => [
            'posts.view' => 'Lihat Berita',
            'posts.create' => 'Tambah Berita',
            'posts.update' => 'Edit Berita',
            'posts.delete' => 'Hapus Berita',
            'posts.manage' => 'Kelola Semua Berita',
        ],
        'Setting' => [
            'settings.view' => 'Lihat Pengaturan',
            'settings.update' => 'Edit Pengaturan',
            'settings.manage' => 'Kelola Semua Pengaturan',
        ],
    ];

    public const PERMISSIONS = [
        'dashboard.view' => 'Lihat Dashboard',
        'users.view' => 'Lihat Pengguna',
        'users.create' => 'Tambah Pengguna',
        'users.update' => 'Edit Pengguna',
        'users.delete' => 'Hapus Pengguna',
        'users.manage' => 'Kelola Semua Pengguna',
        'spmb.view' => 'Lihat SPMB',
        'spmb.create' => 'Tambah Data SPMB',
        'spmb.update' => 'Edit Data SPMB',
        'spmb.delete' => 'Hapus Data SPMB',
        'spmb.approve' => 'Terima Calon Siswa',
        'spmb.manage' => 'Kelola Semua SPMB',
        'classes.view' => 'Lihat Kelas',
        'classes.create' => 'Tambah Kelas',
        'classes.update' => 'Edit Kelas',
        'classes.delete' => 'Hapus Kelas',
        'classes.manage' => 'Kelola Semua Kelas',
        'semesters.view' => 'Lihat Semester',
        'semesters.create' => 'Tambah Semester',
        'semesters.update' => 'Edit Semester',
        'semesters.delete' => 'Hapus Semester',
        'semesters.manage' => 'Kelola Semua Semester',
        'meetings.view' => 'Lihat Pertemuan',
        'meetings.create' => 'Tambah Pertemuan',
        'meetings.update' => 'Edit Pertemuan',
        'meetings.delete' => 'Hapus Pertemuan',
        'meetings.manage' => 'Kelola Semua Pertemuan',
        'attendances.view' => 'Lihat Absensi',
        'attendances.create' => 'Tambah Absensi',
        'attendances.update' => 'Edit Absensi',
        'attendances.delete' => 'Hapus Absensi',
        'attendances.manage' => 'Kelola Semua Absensi',
        'teacher-attendances.view' => 'Lihat Absensi Ustad',
        'teacher-attendances.create' => 'Tambah Absensi Ustad',
        'teacher-attendances.update' => 'Edit Absensi Ustad',
        'teacher-attendances.delete' => 'Hapus Absensi Ustad',
        'teacher-attendances.manage' => 'Kelola Semua Absensi Ustad',
        'teacher-attendances.check-in' => 'Check In/Out Absensi Ustad',
        'teacher-attendances.report' => 'Laporan Absensi Ustad',
        'assessments.view' => 'Lihat Assessment',
        'assessments.create' => 'Tambah Assessment',
        'assessments.update' => 'Edit Assessment',
        'assessments.delete' => 'Hapus Assessment',
        'assessments.manage' => 'Kelola Semua Assessment',
        'evaluations.view' => 'Lihat Evaluasi',
        'evaluations.create' => 'Tambah Evaluasi',
        'evaluations.update' => 'Edit Evaluasi',
        'evaluations.delete' => 'Hapus Evaluasi',
        'evaluations.manage' => 'Kelola Semua Evaluasi',
        'grades.view' => 'Lihat Nilai',
        'grades.create' => 'Tambah Nilai',
        'grades.update' => 'Edit Nilai',
        'grades.delete' => 'Hapus Nilai',
        'grades.manage' => 'Kelola Semua Nilai',
        'reports.view' => 'Lihat Rapor',
        'reports.download' => 'Unduh Rapor',
        'payments.view' => 'Lihat Pembayaran',
        'payments.create' => 'Tambah Pembayaran',
        'payments.update' => 'Edit Pembayaran',
        'payments.delete' => 'Hapus Pembayaran',
        'payments.manage' => 'Kelola Semua Pembayaran',
        'payments.checkout' => 'Checkout Pembayaran',
        'posts.view' => 'Lihat Berita',
        'posts.create' => 'Tambah Berita',
        'posts.update' => 'Edit Berita',
        'posts.delete' => 'Hapus Berita',
        'posts.manage' => 'Kelola Semua Berita',
        'settings.view' => 'Lihat Pengaturan',
        'settings.update' => 'Edit Pengaturan',
        'settings.manage' => 'Kelola Semua Pengaturan',
    ];

    protected $fillable = [
        'role',
        'permission',
        'is_allowed',
    ];

    protected function casts(): array
    {
        return [
            'is_allowed' => 'boolean',
        ];
    }
}
