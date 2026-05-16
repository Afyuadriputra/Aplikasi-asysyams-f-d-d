# Testing Summary Asy-Syams

## Ringkasan

Audit dilakukan pada project Laravel 12 + Filament 3.3 dengan arsitektur Feature-Driven MVC + Service Layer.

Baseline sebelum perubahan:
- `php artisan route:list`: 78 routes.
- `php artisan test`: 90 tests passed, 234 assertions.
- `composer dump-autoload`: berhasil.
- `php artisan optimize:clear`: gagal pada cache store database karena MySQL lokal `127.0.0.1:3307` tidak aktif.

Hasil akhir:
- `php artisan route:list`: 78 routes.
- `php artisan test`: 107 tests passed, 3076 assertions.
- `CACHE_STORE=array php artisan optimize:clear`: berhasil.
- `php artisan test --coverage`: tidak tersedia karena Xdebug/PCOV belum terpasang.

## Kategori Test

Test yang tersedia dan diperkuat:
- Architecture / namespace regression.
- Auth, role, RBAC, dan permission middleware.
- Filament resource render, namespace model, dan action permission.
- Academic flow dan relasi class group.
- Meetings dan attendance, termasuk status `present`, `sick`, `permission`, `alpha`.
- GradeCalculationService.
- Report PDF dengan mock DomPDF.
- Payment dan Midtrans webhook dengan fake payload/signature.
- Student dashboard dan isolasi data antar student.
- Posts, contacts, site settings, dan SPMB deadline.
- Security regression untuk debug routes, admin access, webhook signature, dan data isolation.

## Bug Logic Diperbaiki

1. Midtrans `failure` status belum dipetakan ke `failed`.
   - File: `app/Features/Payments/Services/MidtransService.php`
   - File: `app/Features/Payments/Controllers/PaymentController.php`
   - Alasan: webhook atau finish redirect dengan `transaction_status=failure` sebelumnya tetap `pending`.

2. Halaman riwayat absensi siswa error 500.
   - File: `app/Features/Students/Controllers/StudentController.php`
   - File: `resources/views/pages/student/attendance.blade.php`
   - Alasan: kode lama memakai `meeting.subject`, sementara model `Meeting` sekarang memakai `class_group_id`; akses subject harus lewat `meeting.classGroup.subject`.

## Route Penting Diverifikasi

- `/dashboard`
- `/attendance`
- `/payment/webhook`
- `/payment/checkout`
- `/berita/{slug}`
- `/contact/send`
- `/rapor-pdf/{class_group}/{user}`
- `/admin`
- `/admin/payments`
- `/admin/users`
- `/admin/semesters`
- `/admin/site-settings/spmb-deadline`
- Debug routes `/check-db`, `/clear-cache-sekarang`, `/cek-rute`, `/cek-pintu` return 404 di testing.

## Security Notes

- Guest tetap diarahkan ke login untuk dashboard dan admin.
- Student aktif diarahkan keluar dari admin ke dashboard.
- Guru tanpa permission resource tidak bisa membuka direct admin resource URL.
- Superadmin tetap bypass permission.
- Webhook invalid signature tidak mengubah payment.
- Payment yang sudah `paid` tidak downgrade akibat webhook ulang.
- Dashboard dan attendance student hanya mengambil data milik user login.

## Catatan

- `SubjectResource.php` masih kosong dan tidak diubah.
- PDF test memakai mock DomPDF agar tidak bergantung pada driver PDF/GD environment.
- Midtrans API asli tidak dipanggil; semua test memakai fake payload dan fake signature.
- Tidak ada URL route, nama route, schema database, migration, atau desain UI yang diubah.
- Perubahan Blade hanya memperbaiki path data absensi yang rusak, tanpa mengubah tampilan.

## Cara Menjalankan Test

```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan test
```

Jika cache store lokal memakai database dan MySQL tidak aktif, jalankan:

```powershell
$env:CACHE_STORE='array'; php artisan optimize:clear
```

Coverage membutuhkan Xdebug atau PCOV:

```bash
php artisan test --coverage
```
