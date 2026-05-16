# Dokumentasi Project Asy-Syams

## 1. Ringkasan Project

**Asy-Syams** adalah aplikasi web berbasis **Laravel 12** dan **Filament v3** untuk mendukung operasional Yayasan Pendidikan Tilawah Qur'an. Sistem mencakup halaman publik, registrasi santri, dashboard santri, pengelolaan akademik, pertemuan, absensi, penilaian, rapor PDF, berita, kontak, pengaturan situs, RBAC, dan integrasi pembayaran Midtrans.

Backend project saat ini menggunakan arsitektur **Feature-Driven MVC + Service Layer**. Artinya kode domain utama dikelompokkan berdasarkan fitur bisnis, bukan lagi berdasarkan jenis file global seperti `Models`, `Controllers`, atau `Services` saja. Pendekatan ini membuat batas tanggung jawab tiap fitur lebih jelas dan memudahkan developer baru memahami alur sistem.

Status pengujian terbaru:
- **107 tests passed**
- **3076 assertions passed**
- Coverage belum tersedia karena driver **Xdebug/PCOV** belum terpasang.

---

## 2. Arsitektur Terbaru

Arsitektur utama:

```text
Route -> Controller -> Service -> Model -> Database -> Response/View/JSON
```

Prinsip yang digunakan:
- **Feature-Driven MVC**: controller, model, dan service domain ditempatkan di `app/Features/{NamaFitur}`.
- **Service Layer**: logic bisnis penting dipisahkan dari controller agar lebih mudah diuji dan dirawat.
- **Filament Admin tetap terpusat**: resource admin tetap berada di `app/Filament/Resources`.
- **Laravel auth tetap mengikuti struktur standar**: auth controller, profile controller, dan base controller tetap berada di `app/Http/Controllers`.
- **Middleware tetap terpusat**: seluruh middleware aplikasi tetap berada di `app/Http/Middleware`.
- **User tetap global**: `User.php` tetap berada di `app/Models/User.php` karena dipakai lintas fitur.

Model utama selain `User` sudah dipindahkan ke fitur masing-masing. Contoh:
- `Payment` berada di `App\Features\Payments\Models\Payment`
- `Grade`, `Assessment`, dan `Evaluation` berada di `App\Features\Grades\Models`
- `ClassGroup`, `Semester`, dan `Subject` berada di `App\Features\Academic\Models`
- `Meeting` dan `Attendance` berada di `App\Features\Meetings\Models`

---

## 3. Struktur Folder Backend

Struktur backend utama:

```text
app/
├── Features/
│   ├── Academic/
│   ├── Contacts/
│   ├── Grades/
│   ├── Meetings/
│   ├── Payments/
│   ├── Permissions/
│   ├── Posts/
│   ├── Reports/
│   ├── SiteSettings/
│   └── Students/
├── Filament/
│   ├── Concerns/
│   └── Resources/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
├── Mail/
├── Models/
│   └── User.php
├── Providers/
└── View/
```

File penting:
- `app/Models/User.php`: model user global, role, permission helper, dan akses panel Filament.
- `app/Filament/Resources`: semua resource admin Filament.
- `app/Filament/Concerns/ChecksResourcePermission.php`: trait permission resource Filament.
- `app/Http/Middleware/EnsureUserHasPermission.php`: proteksi permission route web.
- `app/Http/Middleware/EnsureUserIsActive.php`: proteksi user aktif.
- `app/Http/Middleware/RedirectUnauthorizedFilamentAccess.php`: proteksi akses admin Filament.
- `app/Providers/Filament/AdminPanelProvider.php`: konfigurasi panel admin Filament.

---

## 4. Penjelasan Fitur di `app/Features`

### Academic

Berisi domain akademik dasar:
- `ClassGroup`
- `Semester`
- `Subject`
- `AcademicService`

Fitur ini menjadi fondasi untuk kelas, semester, mata pelajaran, relasi guru, relasi santri, meeting, assessment, evaluation, dan report.

### Contacts

Mengelola form kontak publik:
- `ContactController`
- `ContactService`

Pengiriman email kontak diuji menggunakan `Mail::fake()` agar tidak mengirim email asli saat test.

### Grades

Mengelola penilaian:
- `Assessment`
- `Evaluation`
- `Grade`
- `GradeCalculationService`

`GradeCalculationService` menghitung:
- nilai huruf assessment seperti `L`, `C`, `TL`
- nilai lowercase dan nilai dengan spasi seperti `" c "`
- nilai numerik seperti `80`, `90`, `100`
- rata-rata assessment
- rata-rata evaluation
- final grade sesuai bobot sistem
- data kosong tanpa division by zero

### Meetings

Mengelola pertemuan dan absensi:
- `Meeting`
- `Attendance`
- `MeetingService`

Status attendance yang valid:
- `present`
- `sick`
- `permission`
- `alpha`

Meeting sekarang terhubung ke `ClassGroup`, dan subject dibaca melalui:

```php
$meeting->classGroup->subject
```

### Payments

Mengelola pembayaran santri dan integrasi Midtrans:
- `Payment`
- `PaymentController`
- `MidtransService`

`MidtransService` bertugas memvalidasi signature webhook, mencari payment berdasarkan `order_id`, memetakan status transaksi, dan menjaga update tetap idempotent.

### Permissions

Mengelola RBAC:
- `RolePermission`
- `PermissionService`

Permission dibaca oleh `User::hasAccess()` dan `User::hasAnyAccess()`. Superadmin bypass semua permission, sedangkan guru dan student mengikuti data `role_permissions`.

### Posts

Mengelola berita/artikel publik:
- `Post`
- `PostController`
- `PostService`

Post memiliki relasi author ke `User`. Halaman detail post menggunakan slug.

### Reports

Mengelola laporan dan rapor:
- `AssessmentReportController`
- `ReportService`

Route rapor PDF:

```text
/rapor-pdf/{class_group}/{user}
```

Route ini menggunakan model binding `ClassGroup` dari namespace baru:

```php
App\Features\Academic\Models\ClassGroup
```

### SiteSettings

Mengelola pengaturan situs:
- `SiteSetting`
- `SiteSettingController`
- `SiteSettingService`

Termasuk pengaturan `spmb_deadline`, hero image, dan setting landing page lain. Halaman Filament `ManageSPMBDeadline` memakai namespace model baru `App\Features\SiteSettings\Models\SiteSetting`.

### Students

Mengelola dashboard dan halaman santri:
- `StudentController`
- `StudentService`

Dashboard santri menampilkan ringkasan pembayaran, absensi, dan nilai milik santri yang sedang login. Query dashboard dan attendance dibatasi berdasarkan `Auth::user()` agar data antar santri tidak bocor.

---

## 5. Alur Kerja Request

Alur umum request web:

```text
routes/web.php
    -> Controller di app/Features/{Feature}/Controllers
    -> Service di app/Features/{Feature}/Services
    -> Model di app/Features/{Feature}/Models atau app/Models/User.php
    -> Database
    -> View/Redirect/JSON/PDF Response
```

Contoh alur dashboard santri:

```text
GET /dashboard
    -> StudentController@dashboard
    -> Payment, Attendance, Grade, Semester
    -> resources/views/pages/student/dashboard.blade.php
```

Contoh alur contact:

```text
POST /contact/send
    -> ContactController@send
    -> ContactService
    -> ContactFormMail
    -> Redirect response
```

Contoh alur rapor PDF:

```text
GET /rapor-pdf/{class_group}/{user}
    -> Route closure dengan middleware auth + reports.download
    -> ClassGroup model binding
    -> User model binding
    -> DomPDF loadView
    -> PDF stream response
```

---

## 6. Filament Admin dan RBAC

Filament admin tetap berada di:

```text
app/Filament/Resources
```

Panel dikonfigurasi di:

```text
app/Providers/Filament/AdminPanelProvider.php
```

Role utama:
- `superadmin`: dapat mengakses seluruh panel dan bypass permission.
- `guru`: dapat mengakses admin jika memiliki minimal satu permission panel yang diizinkan.
- `student`: tidak boleh mengakses admin panel dan diarahkan ke dashboard.

Komponen RBAC penting:
- `User::canAccessPanel()`
- `User::hasAccess()`
- `User::hasAnyAccess()`
- `RolePermission`
- `ChecksResourcePermission`
- `RedirectUnauthorizedFilamentAccess`
- `EnsureUserHasPermission`

Resource Filament menggunakan model namespace baru, misalnya:

```php
App\Filament\Resources\PaymentResource
    -> App\Features\Payments\Models\Payment
```

Catatan penting:
- `SubjectResource.php` saat ini masih kosong.
- Jika fitur Subject ingin diaktifkan di Filament, resource tersebut perlu dilengkapi form, table, permission, dan pages sesuai pola resource lain.
- Jangan mengaktifkan atau mengisi `SubjectResource.php` tanpa test resource dan permission yang sesuai.

---

## 7. Alur Payment dan Midtrans Webhook

Alur checkout:

```text
GET /payment/checkout
    -> PaymentController@checkout
    -> Semester aktif
    -> Payment milik user login
    -> Midtrans Snap token
    -> JSON response
```

Alur finish redirect:

```text
GET /payment/success
    -> PaymentController@success
    -> Verifikasi status Midtrans jika memungkinkan
    -> Update status payment
    -> Redirect dashboard
```

Alur webhook:

```text
POST /payment/webhook
    -> PaymentController@webhook
    -> MidtransService::handleWebhook()
    -> Validasi signature_key
    -> Cari Payment berdasarkan order_id
    -> Update status payment
    -> JSON response
```

Mapping status penting:
- `settlement` dan `capture` menjadi `paid`
- `pending` tetap `pending`
- `deny`, `expire`, `cancel`, dan `failure` menjadi `failed`

Bug yang sudah diperbaiki:
- Status Midtrans `failure` sebelumnya belum dipetakan ke `failed`.
- `PaymentController::success()` sekarang juga menangani `failure`.
- Payment yang sudah `paid` atau `success` tidak boleh downgrade menjadi `failed` atau `pending` akibat webhook ulang.
- Invalid signature tidak mengubah data payment.
- `order_id` tidak ditemukan tidak menyebabkan fatal error 500.

---

## 8. Academic, Grade, Attendance, dan Report/Rapor

### Academic

`ClassGroup` menjadi pusat relasi akademik:
- belongs to `Subject`
- belongs to `Semester`
- belongs to teacher `User`
- belongs to many student `User`
- has many `Meeting`
- has many `Assessment`
- has many `Evaluation`

### Attendance

Meeting dibuat untuk `ClassGroup`, bukan langsung untuk `Subject`. Karena itu akses subject dari attendance mengikuti relasi:

```php
$attendance->meeting->classGroup->subject
```

Bug yang sudah diperbaiki:
- `StudentController` sebelumnya eager-load `meeting.subject`.
- Karena `Meeting` sekarang berbasis `class_group_id`, eager-load diperbaiki menjadi `meeting.classGroup.subject`.
- `attendance.blade.php` juga diperbaiki pada path data menjadi `meeting.classGroup.subject` tanpa mengubah tampilan.

### Grades

Penilaian dibagi menjadi:
- `Assessment`: data penilaian berbasis item dan nilai huruf/numerik.
- `Evaluation`: data evaluasi berbasis item dan score.
- `Grade`: nilai akhir per user, subject, dan semester.

`GradeCalculationService` menangani konversi dan perhitungan sehingga controller/resource tidak memuat logic kalkulasi berat.

### Report/Rapor

Rapor PDF menggunakan:
- `ClassGroup` dari feature Academic.
- `User` dari `app/Models/User.php`.
- View PDF di `resources/views/filament/report/rapor-pdf.blade.php`.
- DomPDF sebagai generator PDF.

Pada test, DomPDF dimock agar tidak bergantung pada driver PDF/GD lokal.

---

## 9. Testing dan Hasil Pengujian Terbaru

Test framework:
- PHPUnit 11
- Laravel testing
- SQLite in-memory pada `phpunit.xml`
- Mail fake untuk kontak
- Fake payload/signature untuk Midtrans
- Mock DomPDF untuk PDF

Hasil akhir terbaru:

```text
Tests: 107 passed
Assertions: 3076 passed
Routes: 78
Coverage: belum tersedia karena Xdebug/PCOV belum terpasang
```

Kategori test yang sudah mencakup:
- Namespace regression
- Auth dan role
- RBAC dan permission
- Filament resource
- Academic flow
- Meetings dan attendance
- GradeCalculationService
- Report PDF
- Payment dan Midtrans webhook
- Student dashboard
- Posts
- Contacts
- SiteSettings
- Security regression

Test yang diperkuat antara lain:
- Tidak ada namespace model/service lama seperti `App\Models\Payment` atau `App\Services\MidtransService`.
- Semua feature model dan service bisa di-resolve.
- Superadmin bisa mengakses semua.
- Guru tanpa permission ditolak/redirect sesuai flow sistem.
- Student tidak bisa mengakses admin.
- Debug routes tidak tersedia di environment testing/production.
- Webhook invalid signature aman.
- Data payment, attendance, dan grade antar student tetap terisolasi.

---

## 10. Security Hardening

Peningkatan dan verifikasi keamanan:
- Debug routes hanya didaftarkan saat `app()->environment('local')`.
- Route debug berikut return 404 di testing:
  - `/check-db`
  - `/clear-cache-sekarang`
  - `/cek-rute`
  - `/cek-pintu`
- Guest tidak bisa mengakses dashboard dan admin.
- Student aktif tidak bisa mengakses admin panel.
- Student inactive diarahkan ke halaman approval sesuai flow sistem.
- Guru tanpa permission tidak bisa membuka resource admin tertentu.
- Superadmin tetap bypass permission.
- Midtrans webhook memvalidasi `signature_key`.
- Invalid webhook tidak mengubah payment.
- Payment paid tidak downgrade.
- Dashboard dan attendance santri tidak menampilkan data santri lain.

---

## 11. Catatan Teknis dan Known Issue

Catatan teknis:
- `User.php` tetap di `app/Models/User.php` dan menjadi model global lintas fitur.
- Filament resource tetap di `app/Filament/Resources`.
- Middleware tetap di `app/Http/Middleware`.
- Auth controller, `ProfileController`, dan base `Controller` tetap di `app/Http/Controllers`.
- Service penting sekarang berada di fitur masing-masing, contohnya:
  - `app/Features/Payments/Services/MidtransService.php`
  - `app/Features/Grades/Services/GradeCalculationService.php`

Known issue/perhatian:
- `SubjectResource.php` masih kosong.
- Coverage belum bisa dijalankan karena Xdebug/PCOV belum terpasang.
- `php artisan optimize:clear` pada local tertentu bisa gagal jika cache store mengarah ke database MySQL yang tidak aktif.
- Untuk local yang cache-nya mengarah ke MySQL, gunakan cache store array saat clear cache.
- Jangan mengubah route, nama route, schema database, atau behavior Filament tanpa test regression.

---

## 12. Cara Menjalankan Command Penting

Generate autoload:

```bash
composer dump-autoload
```

Clear cache Laravel:

```bash
php artisan optimize:clear
```

Jika local cache mengarah ke MySQL dan database tidak aktif:

```bash
CACHE_STORE=array php artisan optimize:clear
```

PowerShell:

```powershell
$env:CACHE_STORE='array'; php artisan optimize:clear
```

Lihat daftar route:

```bash
php artisan route:list
```

Jalankan seluruh test:

```bash
php artisan test
```

Jalankan coverage jika Xdebug/PCOV sudah tersedia:

```bash
php artisan test --coverage
```

---

## 13. Rekomendasi Maintenance Lanjutan

Rekomendasi yang belum dikerjakan:
- Lengkapi `SubjectResource.php` jika Subject perlu dikelola melalui Filament.
- Pasang Xdebug atau PCOV untuk menjalankan coverage.
- Pertimbangkan helper/factory internal test untuk mengurangi duplikasi setup semester, subject, class group, dan user.
- Tambahkan dokumentasi permission matrix untuk seluruh resource Filament.
- Tambahkan test end-to-end manual checklist untuk flow pembayaran Midtrans di sandbox.
- Review konfigurasi cache local agar `optimize:clear` tidak bergantung pada MySQL saat development.
- Pertahankan test namespace regression setiap kali memindahkan model/service.

---

## 14. Kesimpulan

Project Asy-Syams saat ini sudah berada pada struktur **Feature-Driven MVC + Service Layer** yang lebih jelas dan modular. Model domain utama sudah berada di fitur masing-masing, sementara `User`, Filament Resource, middleware, dan controller Laravel standar tetap berada di lokasi Laravel yang sesuai.

Flow utama sistem, URL route, nama route, schema database, UI, behavior Filament, dan permission rule tetap dijaga. Perubahan terbaru berfokus pada penguatan test, security regression, dan bugfix minimal yang terbukti oleh test.
