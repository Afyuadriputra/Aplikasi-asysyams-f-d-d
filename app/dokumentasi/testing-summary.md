# Testing Summary Asy-Syams

## Ringkasan

Project memakai Laravel 12, Filament 3.3, PHPUnit 11, dan SQLite in-memory untuk test. Arsitektur yang diuji adalah Feature-Driven MVC + Service Layer.

Status terbaru setelah penambahan fitur dan update dokumentasi:
- Route aktif: 86 routes.
- Test target Teacher Attendance: 11 passed.
- Test target dashboard + teacher attendance: 15 passed.
- Test target Filament + dashboard + teacher attendance: 21 passed.
- Namespace regression: 4 passed.
- Full test: 127 passed, 3416 assertions.
- Coverage belum tersedia karena Xdebug/PCOV belum terpasang.

## Command Yang Dijalankan

```bash
php artisan migrate --force
php artisan route:list
php artisan route:list --path=teacher-attendances
php artisan route:list --path=admin/site-settings
php artisan test tests/Feature/TeacherAttendances/TeacherAttendanceTest.php
php artisan test tests/Feature/TeacherAttendances/TeacherAttendanceTest.php tests/Feature/Students/StudentDashboardTest.php
php artisan test tests/Feature/TeacherAttendances/TeacherAttendanceTest.php tests/Feature/Students/StudentDashboardTest.php tests/Feature/Filament/FilamentResourceTest.php
php artisan test
```

## Route Baru Diverifikasi

Teacher Attendance:
- `POST /teacher-attendances/check-in`
- `POST /teacher-attendances/check-out`
- `GET /admin/teacher-attendances`
- `GET /admin/teacher-attendances/create`
- `GET /admin/teacher-attendances/{record}`
- `GET /admin/teacher-attendances/{record}/edit`

Site Setting:
- `GET /admin/site-settings/teacher-attendance-schedule`

Grade Control:
- `GET /admin/grades/student/{user}/control`
- `GET /admin/grades/student/{user}/pdf`

## Kategori Test Saat Ini

Test yang tersedia:
- Unit basic.
- Grade service.
- Grade report service.
- Academic flow.
- Auth.
- Email verification.
- Password confirmation/reset/update.
- Registration.
- Contacts.
- Filament resource.
- Student grade control report.
- Meetings dan attendance santri.
- Midtrans webhook.
- Permissions/RBAC.
- Posts.
- Profile.
- Namespace regression.
- Reports PDF.
- Security regression.
- Site settings.
- SPMB registration.
- Student dashboard.
- Student dashboard payment.
- Teacher attendances.

## Test Teacher Attendance

File:
- `tests/Feature/TeacherAttendances/TeacherAttendanceTest.php`

Skenario:
- Guru bisa check-in untuk dirinya sendiri.
- Check-in lewat batas waktu menghasilkan status `late`.
- Batas waktu terlambat dapat dikonfigurasi dari `site_settings`.
- Guru tidak bisa double check-in di tanggal yang sama.
- Guru bisa check-out setelah check-in.
- Guru tidak bisa check-out sebelum check-in.
- Student tidak boleh check-in.
- Superadmin bisa membuat absensi manual via service.
- Unique constraint `user_id + date` bekerja.
- Dashboard guru menampilkan status absensi hari ini.
- Filament resource terlindungi permission.

Hasil:

```text
Tests: 11 passed
Assertions: 24 assertions
```

## Test Dashboard

File:
- `tests/Feature/Students/StudentDashboardTest.php`

Skenario penting:
- Guest tidak bisa akses dashboard.
- Student melihat ringkasan miliknya.
- Student tidak melihat data student lain.
- Dashboard aman saat data kosong.
- Guru melihat jadwal dan rekap absensi meeting miliknya.
- Dashboard guru juga menerima data teacher attendance dari service.

## Test Filament

File:
- `tests/Feature/Filament/FilamentResourceTest.php`

Pembaruan:
- `TeacherAttendanceResource` ditambahkan ke daftar resource yang diverifikasi.
- Superadmin dapat akses `/admin/teacher-attendances`.
- Model resource diverifikasi memakai model feature yang benar.
- Permission resource mengikuti trait `ChecksResourcePermission`.

## Test Grade Control

File:
- `tests/Unit/Grades/GradeReportServiceTest.php`
- `tests/Feature/Grades/StudentGradeControlReportTest.php`

Skenario:
- Assessment dipetakan ke tabel kontrol santri.
- Catatan pembelajaran dari assessment masuk ke report.
- Evaluation muncul sebelum rekap absensi.
- Rekap attendance santri dihitung benar.
- Superadmin dapat melihat tabel kontrol.
- Guru hanya dapat akses santri di kelasnya.
- Student tidak bisa akses PDF admin.
- Grade query parameter harus cocok dengan student.

## Test Payment/Midtrans

File:
- `tests/Feature/Payments/MidtransWebhookTest.php`

Skenario:
- Webhook settlement/capture update payment menjadi paid.
- Invalid signature ditolak.
- Order ID tidak ditemukan tidak fatal.
- Payment lunas tidak downgrade.
- Cancel/deny/expire/failure menjadi failed.

## Test Security

File:
- `tests/Feature/Security/SecurityRegressionTest.php`

Skenario:
- Guest tidak bisa akses area protected.
- Student inactive diarahkan ke approval notice.
- Invalid webhook ditolak.
- Student tidak bisa melihat data student lain.
- Debug route tidak tersedia di testing/production.

## Regression Namespace

Sebelum dokumentasi diperbarui, full test sempat gagal karena dokumentasi lama masih memuat namespace legacy yang dilarang oleh regression test.

Setelah dokumentasi dibersihkan:

```text
Tests: 4 passed
Assertions: 3088 assertions
```

Full suite terbaru:

```text
Tests: 127 passed
Assertions: 3416 assertions
```

## Cara Menjalankan Test

```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan test
```

Jika cache store lokal mengarah ke database dan database tidak aktif:

```powershell
$env:CACHE_STORE='array'; php artisan optimize:clear
```

Coverage:

```bash
php artisan test --coverage
```

Coverage membutuhkan Xdebug atau PCOV.
