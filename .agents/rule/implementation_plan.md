# Migrasi Struktur Backend Asy-Syams ke Feature-Driven MVC + Service Layer

Migrasi ini memindahkan file Model, Controller, dan Service dari struktur flat Laravel ke arsitektur **Feature-Driven** di folder `app/Features/`, kemudian menyesuaikan seluruh namespace, import, dan referensi di Filament Resource, route, middleware, test, seeder, dan factory. **Tidak ada perubahan pada alur kerja, fitur, URL, tabel database, tampilan, atau logic bisnis.**

## User Review Required

> [!IMPORTANT]
> **SubjectResource.php kosong** — File `app/Filament/Resources/SubjectResource.php` saat ini berisi file kosong (0 bytes). Apakah ini sengaja atau seharusnya ada resource Filament untuk Subject? Migrasi akan tetap memindahkan model `Subject.php` ke `Features/Academic/Models/` tanpa menyentuh file resource yang kosong ini.

> [!IMPORTANT]
> **Service baru yang "kosong"** — Sesuai permintaan, beberapa service baru akan dibuat (AcademicService, MeetingService, PostService, ContactService, SiteSettingService, PermissionService, StudentService, ReportService) meskipun saat ini belum ada logic yang perlu dipindahkan ke sana. Service ini dibuat sebagai placeholder dengan struktur class yang benar untuk memenuhi arsitektur target. Jika tidak diinginkan, mohon beritahu.

> [!WARNING]
> **`GradeCalculationService` → `GradeService`** — Isi logic dari `GradeCalculationService` akan dipindahkan menjadi `GradeService` dengan namespace baru. Semua method (`calculateFinalGrade`, `calculateAssessmentAverage`, `calculateEvaluationAverage`) akan identik. Test unit `GradeCalculationTest` akan diperbarui import-nya ke class baru.

> [!WARNING]
> **`MidtransService` → `PaymentService`** — Isi logic dari `MidtransService` akan dipindahkan menjadi `PaymentService`. Method `handleWebhook()` dan seluruh logika validasi signature, idempotent update tidak berubah. `PaymentController@webhook` yang saat ini meng-inject `MidtransService` akan diperbarui ke `PaymentService`.

## Proposed Changes

### Tahap 1: Pembuatan Folder Struktur

Buat seluruh folder target di `app/Features/`:

```
app/Features/
├── Academic/Models/
├── Academic/Controllers/
├── Academic/Services/
├── Contacts/Controllers/
├── Contacts/Services/
├── Grades/Models/
├── Grades/Controllers/
├── Grades/Services/
├── Meetings/Models/
├── Meetings/Controllers/
├── Meetings/Services/
├── Payments/Models/
├── Payments/Controllers/
├── Payments/Services/
├── Permissions/Models/
├── Permissions/Controllers/
├── Permissions/Services/
├── Posts/Models/
├── Posts/Controllers/
├── Posts/Services/
├── Reports/Controllers/
├── Reports/Services/
├── SiteSettings/Models/
├── SiteSettings/Controllers/
├── SiteSettings/Services/
├── Students/Controllers/
└── Students/Services/
```

---

### Tahap 2: Migrasi Posts & Contacts (Risiko rendah)

#### [MOVE] Post.php
- **Dari**: `app/Models/Post.php`
- **Ke**: `app/Features/Posts/Models/Post.php`
- **Perubahan**: namespace `App\Models` → `App\Features\Posts\Models`
- **Relasi**: `belongsTo(User::class)` tetap — User masih di `App\Models`

#### [MOVE] PostController.php
- **Dari**: `app/Http/Controllers/PostController.php`
- **Ke**: `app/Features/Posts/Controllers/PostController.php`
- **Perubahan**: namespace, import Post, tetap `extends Controller`

#### [NEW] PostService.php
- **Di**: `app/Features/Posts/Services/PostService.php`
- **Konten**: Placeholder service class kosong

#### [MOVE] ContactController.php
- **Dari**: `app/Http/Controllers/ContactController.php`
- **Ke**: `app/Features/Contacts/Controllers/ContactController.php`
- **Perubahan**: namespace saja, import `ContactFormMail` tetap di `App\Mail`

#### [NEW] ContactService.php
- **Di**: `app/Features/Contacts/Services/ContactService.php`
- **Konten**: Placeholder service class kosong

#### File yang perlu diupdate import-nya (Tahap 2):

| File | Import lama | Import baru |
|------|-------------|-------------|
| `routes/web.php` | `App\Http\Controllers\PostController` | `App\Features\Posts\Controllers\PostController` |
| `routes/web.php` | `App\Models\Post` | `App\Features\Posts\Models\Post` |
| `routes/web.php` | `App\Http\Controllers\ContactController` → `use App\Features\Contacts\Controllers\ContactController` |
| `Filament/Resources/PostResource.php` | `App\Models\Post` | `App\Features\Posts\Models\Post` |
| `Filament/Resources/UserResource.php` | Tidak ada import Post — aman |

---

### Tahap 3: Migrasi Payments

#### [MOVE] Payment.php
- **Dari**: `app/Models/Payment.php`
- **Ke**: `app/Features/Payments/Models/Payment.php`
- **Relasi**: `belongsTo(User::class)` tetap, `belongsTo(Semester::class)` → akan diupdate setelah Tahap 5

#### [MOVE] PaymentController.php
- **Dari**: `app/Http/Controllers/PaymentController.php`
- **Ke**: `app/Features/Payments/Controllers/PaymentController.php`
- **Perubahan**: namespace, import Payment, import Semester (akan diupdate setelah Tahap 5), tetap `extends Controller`
- **PENTING**: `webhook()` method signature berubah: `\App\Services\MidtransService` → `\App\Features\Payments\Services\PaymentService`

#### [MOVE+RENAME] MidtransService.php → PaymentService.php
- **Dari**: `app/Services/MidtransService.php`
- **Ke**: `app/Features/Payments/Services/PaymentService.php`
- **Perubahan**: namespace, class name `MidtransService` → `PaymentService`, import Payment
- **Logic `handleWebhook()`**: IDENTIK — signature validation, idempotent update, status mapping

#### File yang perlu diupdate import-nya (Tahap 3):

| File | Import lama | Import baru |
|------|-------------|-------------|
| `routes/web.php` | `App\Http\Controllers\PaymentController` | `App\Features\Payments\Controllers\PaymentController` |
| `Filament/Resources/PaymentResource.php` | `App\Models\Payment` | `App\Features\Payments\Models\Payment` |
| `tests/Feature/MidtransWebhookTest.php` | `App\Models\Payment` | `App\Features\Payments\Models\Payment` |
| `tests/Feature/StudentDashboardPaymentTest.php` | `App\Models\Payment` | `App\Features\Payments\Models\Payment` |
| `app/Models/User.php` | `App\Models\Payment` | `App\Features\Payments\Models\Payment` (jika ada use statement, atau implicit via relation — perlu dicek) |

> [!NOTE]
> Model `User.php` menggunakan `$this->hasMany(Payment::class)` tanpa explicit `use` import — Eloquent resolves class via `App\Models` namespace. Setelah Payment dipindahkan, relasi **HARUS** diperbarui menjadi `use App\Features\Payments\Models\Payment` di header User.php.

---

### Tahap 4: Migrasi Grades & Reports

#### [MOVE] Assessment.php, Evaluation.php, Grade.php
- **Ke**: `app/Features/Grades/Models/`
- **Perubahan**: namespace per file

#### [MOVE+RENAME] GradeCalculationService.php → GradeService.php
- **Dari**: `app/Services/GradeCalculationService.php`
- **Ke**: `app/Features/Grades/Services/GradeService.php`
- **Class name**: `GradeCalculationService` → `GradeService`
- **Logic**: 3 method IDENTIK, tanpa perubahan

#### [MOVE] AssessmentReportController.php
- **Dari**: `app/Http/Controllers/AssessmentReportController.php`
- **Ke**: `app/Features/Reports/Controllers/AssessmentReportController.php`
- **Perubahan**: namespace, import Assessment, ClassGroup, Semester → namespace baru

#### [NEW] ReportService.php
- **Di**: `app/Features/Reports/Services/ReportService.php`
- **Konten**: Placeholder service class kosong

#### File yang perlu diupdate import-nya (Tahap 4):

| File | Import lama | Import baru |
|------|-------------|-------------|
| `Filament/Resources/AssessmentResource.php` | `App\Models\Assessment`, `App\Models\ClassGroup` | `App\Features\Grades\Models\Assessment`, `App\Features\Academic\Models\ClassGroup` |
| `Filament/Resources/EvaluationResource.php` | `App\Models\ClassGroup`, `App\Models\Evaluation` | `App\Features\Academic\Models\ClassGroup`, `App\Features\Grades\Models\Evaluation` |
| `Filament/Resources/EvaluationResource/Pages/CreateEvaluation.php` | `App\Models\Evaluation` | `App\Features\Grades\Models\Evaluation` |
| `Filament/Resources/GradeResource.php` | `App\Models\Grade` | `App\Features\Grades\Models\Grade` |
| `Filament/Resources/ReportResource.php` | `App\Models\ClassGroup` | `App\Features\Academic\Models\ClassGroup` |
| `tests/Unit/GradeCalculationTest.php` | `App\Services\GradeCalculationService` | `App\Features\Grades\Services\GradeService`, class name update |
| `app/Models/User.php` | Implicit `Grade`, `Assessment`, `Evaluation` | Explicit `use` statements ke namespace baru |

---

### Tahap 5: Migrasi Academic & Meetings

#### [MOVE] ClassGroup.php, Semester.php, Subject.php
- **Ke**: `app/Features/Academic/Models/`
- **Perubahan**: namespace, cross-feature imports (ClassGroup references Meeting, Assessment, Evaluation, Subject, Semester, User)

#### [NEW] AcademicController.php, AcademicService.php
- Placeholder classes

#### [MOVE] Meeting.php, Attendance.php
- **Ke**: `app/Features/Meetings/Models/`
- **Perubahan**: namespace, cross-feature imports (Meeting references ClassGroup, User, Attendance)

#### [NEW] MeetingController.php, MeetingService.php
- Placeholder classes

#### File yang perlu diupdate import-nya (Tahap 5):

| File | Perubahan |
|------|-----------|
| `Filament/Resources/ClassGroupResource.php` | `ClassGroup`, `Semester`, `Subject`, `User` |
| `Filament/Resources/MeetingResource.php` | `Meeting`, `ClassGroup` + inline `\App\Models\ClassGroup::find()` di line 52 |
| `Filament/Resources/SemesterResource.php` | `Semester` |
| `Filament/Resources/MeetingResource/RelationManagers/AttendancesRelationManager.php` | `User` tetap |
| `Filament/Resources/ClassGroupResource/RelationManagers/StudentsRelationManager.php` | Tidak ada model import selain `AssessmentResource` — aman |
| `Filament/Resources/SemesterResource/RelationManagers/PaymentsRelationManager.php` | `User` tetap |
| `routes/web.php` | `App\Models\ClassGroup` → `App\Features\Academic\Models\ClassGroup` (baris 124, route binding rapor-pdf) |
| `routes/web.php` | `App\Models\SiteSetting` → akan dihandle Tahap 6 |
| `tests/Feature/AttendanceTest.php` | `App\Models\ClassGroup`, `App\Models\Meeting`, `App\Models\Subject`, `App\Models\Semester` |
| `tests/Feature/MidtransWebhookTest.php` | `App\Models\Semester` |
| `tests/Feature/StudentDashboardPaymentTest.php` | `App\Models\Semester` |
| `app/Http/Controllers/StudentController.php` (sudah dipindahkan Tahap 8, tapi import-nya perlu direncanakan) | `Attendance`, `Grade`, `Payment`, `Semester`, `Meeting` |
| `app/Features/Payments/Models/Payment.php` | `belongsTo(Semester::class)` → perlu explicit `use App\Features\Academic\Models\Semester` |
| `app/Features/Grades/Models/Grade.php` | `belongsTo(Subject::class)`, `belongsTo(Semester::class)` → explicit imports |
| `app/Models/User.php` | `ClassGroup`, `Meeting`, `Attendance` → explicit imports |
| `database/seeders/SubjectSeeder.php` | `App\Models\Subject` → `App\Features\Academic\Models\Subject` |

---

### Tahap 6: Migrasi SiteSettings

#### [MOVE] SiteSetting.php
- **Ke**: `app/Features/SiteSettings/Models/SiteSetting.php`

#### [NEW] SiteSettingController.php, SiteSettingService.php
- Placeholder classes

#### File yang perlu diupdate (Tahap 6):

| File | Perubahan |
|------|-----------|
| `routes/web.php` | `App\Models\SiteSetting` → `App\Features\SiteSettings\Models\SiteSetting` |
| `Filament/Resources/SiteSettingResource.php` | `App\Models\SiteSetting` |
| `Filament/Resources/SiteSettingResource/Pages/ManageSPMBDeadline.php` | `App\Models\SiteSetting` |

---

### Tahap 7: Migrasi Permissions

#### [MOVE] RolePermission.php
- **Ke**: `app/Features/Permissions/Models/RolePermission.php`

#### [NEW] PermissionController.php, PermissionService.php
- Placeholder classes

#### File yang perlu diupdate (Tahap 7):

| File | Perubahan |
|------|-----------|
| `app/Models/User.php` | `use App\Models\RolePermission` → `use App\Features\Permissions\Models\RolePermission` (query di `hasAccess`, `hasAnyAccess`) |
| `app/Http/Middleware/EnsureUserHasPermission.php` | Tidak ada direct import RolePermission — uses `User::hasAnyAccess()` — **aman** |
| `app/Http/Middleware/RedirectUnauthorizedFilamentAccess.php` | Tidak ada direct import RolePermission — **aman** |
| `Filament/Resources/RolePermissionResource.php` | `App\Models\RolePermission` |
| `Filament/Resources/RolePermissionResource/Pages/ListRolePermissions.php` | `App\Models\RolePermission` |
| `database/seeders/RolePermissionSeeder.php` | `App\Models\RolePermission` → `App\Features\Permissions\Models\RolePermission` |
| `tests/Feature/RbacTest.php` | `App\Models\RolePermission` |
| `tests/Feature/AttendanceTest.php` | `App\Models\RolePermission` |
| `tests/Feature/StudentDashboardPaymentTest.php` | `App\Models\RolePermission` |

---

### Tahap 8: Migrasi Students

#### [MOVE] StudentController.php
- **Dari**: `app/Http/Controllers/StudentController.php`
- **Ke**: `app/Features/Students/Controllers/StudentController.php`
- **Perubahan**: namespace, semua import model ke namespace baru

#### [NEW] StudentService.php
- Placeholder class

#### File yang perlu diupdate (Tahap 8):

| File | Perubahan |
|------|-----------|
| `routes/web.php` | `App\Http\Controllers\StudentController` → `App\Features\Students\Controllers\StudentController` |

---

### Tahap 9: Final Sweep — Semua import yang tersisa

Setelah semua file dipindahkan, lakukan sweep terakhir:

1. **`app/Models/User.php`** — Update semua `use` statement untuk model yang telah dipindahkan:
   - `App\Features\Grades\Models\Grade`
   - `App\Features\Payments\Models\Payment`
   - `App\Features\Meetings\Models\Attendance`
   - `App\Features\Meetings\Models\Meeting`
   - `App\Features\Academic\Models\ClassGroup`
   - `App\Features\Grades\Models\Assessment`
   - `App\Features\Grades\Models\Evaluation`
   - `App\Features\Permissions\Models\RolePermission`

2. **Cross-feature model relations** — Semua model yang telah dipindahkan perlu explicit `use` untuk model dari feature lain:
   - `Payment` → `use App\Features\Academic\Models\Semester`
   - `Grade` → `use App\Features\Academic\Models\Subject`, `Semester`
   - `ClassGroup` → `use App\Features\Academic\Models\Subject`, `Semester`, `App\Features\Grades\Models\Assessment`, `Evaluation`, `App\Features\Meetings\Models\Meeting`
   - `Meeting` → `use App\Features\Academic\Models\ClassGroup`, `App\Features\Meetings\Models\Attendance`
   - `Attendance` → `use App\Features\Meetings\Models\Meeting`

3. **Hapus file sumber lama** — Setelah dikonfirmasi semua file baru berfungsi, hapus file sumber asli

---

### Tahap 10: Verifikasi

Jalankan secara berurutan:
```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan test
```

---

## Ringkasan File yang Dipindahkan

| # | File Lama | File Baru |
|---|-----------|-----------|
| 1 | `app/Models/Post.php` | `app/Features/Posts/Models/Post.php` |
| 2 | `app/Models/Payment.php` | `app/Features/Payments/Models/Payment.php` |
| 3 | `app/Models/Assessment.php` | `app/Features/Grades/Models/Assessment.php` |
| 4 | `app/Models/Evaluation.php` | `app/Features/Grades/Models/Evaluation.php` |
| 5 | `app/Models/Grade.php` | `app/Features/Grades/Models/Grade.php` |
| 6 | `app/Models/ClassGroup.php` | `app/Features/Academic/Models/ClassGroup.php` |
| 7 | `app/Models/Semester.php` | `app/Features/Academic/Models/Semester.php` |
| 8 | `app/Models/Subject.php` | `app/Features/Academic/Models/Subject.php` |
| 9 | `app/Models/Meeting.php` | `app/Features/Meetings/Models/Meeting.php` |
| 10 | `app/Models/Attendance.php` | `app/Features/Meetings/Models/Attendance.php` |
| 11 | `app/Models/SiteSetting.php` | `app/Features/SiteSettings/Models/SiteSetting.php` |
| 12 | `app/Models/RolePermission.php` | `app/Features/Permissions/Models/RolePermission.php` |
| 13 | `app/Http/Controllers/PostController.php` | `app/Features/Posts/Controllers/PostController.php` |
| 14 | `app/Http/Controllers/PaymentController.php` | `app/Features/Payments/Controllers/PaymentController.php` |
| 15 | `app/Http/Controllers/ContactController.php` | `app/Features/Contacts/Controllers/ContactController.php` |
| 16 | `app/Http/Controllers/AssessmentReportController.php` | `app/Features/Reports/Controllers/AssessmentReportController.php` |
| 17 | `app/Http/Controllers/StudentController.php` | `app/Features/Students/Controllers/StudentController.php` |
| 18 | `app/Services/GradeCalculationService.php` | `app/Features/Grades/Services/GradeService.php` |
| 19 | `app/Services/MidtransService.php` | `app/Features/Payments/Services/PaymentService.php` |

## Ringkasan File Baru (Placeholder Service)

| # | File | Keterangan |
|---|------|------------|
| 1 | `app/Features/Posts/Services/PostService.php` | Placeholder |
| 2 | `app/Features/Contacts/Services/ContactService.php` | Placeholder |
| 3 | `app/Features/Academic/Controllers/AcademicController.php` | Placeholder |
| 4 | `app/Features/Academic/Services/AcademicService.php` | Placeholder |
| 5 | `app/Features/Meetings/Controllers/MeetingController.php` | Placeholder |
| 6 | `app/Features/Meetings/Services/MeetingService.php` | Placeholder |
| 7 | `app/Features/Reports/Services/ReportService.php` | Placeholder |
| 8 | `app/Features/SiteSettings/Controllers/SiteSettingController.php` | Placeholder |
| 9 | `app/Features/SiteSettings/Services/SiteSettingService.php` | Placeholder |
| 10 | `app/Features/Permissions/Controllers/PermissionController.php` | Placeholder |
| 11 | `app/Features/Permissions/Services/PermissionService.php` | Placeholder |
| 12 | `app/Features/Students/Services/StudentService.php` | Placeholder |

## Ringkasan File yang HANYA Diupdate Import-nya (Tidak dipindahkan)

| # | File | Import yang berubah |
|---|------|---------------------|
| 1 | `app/Models/User.php` | 8 model imports ke namespace baru |
| 2 | `routes/web.php` | 5 controller + 2 model imports |
| 3 | `app/Filament/Resources/PostResource.php` | `Post` |
| 4 | `app/Filament/Resources/PaymentResource.php` | `Payment` |
| 5 | `app/Filament/Resources/AssessmentResource.php` | `Assessment`, `ClassGroup` |
| 6 | `app/Filament/Resources/EvaluationResource.php` | `ClassGroup`, `Evaluation` |
| 7 | `app/Filament/Resources/EvaluationResource/Pages/CreateEvaluation.php` | `Evaluation` |
| 8 | `app/Filament/Resources/GradeResource.php` | `Grade` |
| 9 | `app/Filament/Resources/ClassGroupResource.php` | `ClassGroup`, `Semester`, `Subject` |
| 10 | `app/Filament/Resources/MeetingResource.php` | `Meeting`, `ClassGroup` + inline FQN |
| 11 | `app/Filament/Resources/SemesterResource.php` | `Semester` |
| 12 | `app/Filament/Resources/ReportResource.php` | `ClassGroup` |
| 13 | `app/Filament/Resources/RolePermissionResource.php` | `RolePermission` |
| 14 | `app/Filament/Resources/RolePermissionResource/Pages/ListRolePermissions.php` | `RolePermission` |
| 15 | `app/Filament/Resources/SiteSettingResource.php` | `SiteSetting` |
| 16 | `app/Filament/Resources/SiteSettingResource/Pages/ManageSPMBDeadline.php` | `SiteSetting` |
| 17 | `database/seeders/RolePermissionSeeder.php` | `RolePermission` |
| 18 | `database/seeders/SubjectSeeder.php` | `Subject` |
| 19 | `tests/Feature/AttendanceTest.php` | `ClassGroup`, `Meeting`, `RolePermission`, `Subject`, `Semester` |
| 20 | `tests/Feature/MidtransWebhookTest.php` | `Payment`, `Semester` |
| 21 | `tests/Feature/RbacTest.php` | `RolePermission` |
| 22 | `tests/Feature/StudentDashboardPaymentTest.php` | `Payment`, `RolePermission`, `Semester` |
| 23 | `tests/Unit/GradeCalculationTest.php` | `GradeCalculationService` → `GradeService` |

## File yang TIDAK DISENTUH

- `app/Models/User.php` — Hanya update import, **TIDAK** dipindahkan
- `app/Http/Controllers/Controller.php` — Tetap di lokasi
- `app/Http/Controllers/ProfileController.php` — Tetap di lokasi
- `app/Http/Controllers/Auth/*` — Tetap di lokasi
- `app/Http/Middleware/*` — Tetap di lokasi (tidak ada import model yang perlu diubah)
- `app/Filament/*` — Tetap di lokasi (hanya update import)
- `app/Mail/*` — Tetap di lokasi, tidak ada import model
- `app/Providers/*` — Tetap di lokasi, tidak ada import model yang dipindahkan
- `app/View/*` — Tetap di lokasi
- `database/migrations/*` — Tetap di lokasi
- `database/factories/UserFactory.php` — Tetap, hanya punya `App\Models\User`
- `resources/views/*` — Tidak ada import PHP model
- Semua file konfigurasi — Tidak ada class reference yang berubah

## Verification Plan

### Automated Tests

Setelah **setiap tahap migrasi**, jalankan:
```bash
composer dump-autoload
php artisan optimize:clear
php artisan route:list 2>&1 | head -50
php artisan test
```

### Per-Stage Checks
- **Tahap 2**: Pastikan halaman berita (`/berita/{slug}`) dan form kontak (`/contact/send`) tetap berfungsi. Test `SpmbRegistrationTest` (home page) harus pass karena view masih mengambil `Post` dari route closure.
- **Tahap 3**: Webhook Midtrans (`/payment/webhook`) harus tetap menerima POST. Test `MidtransWebhookTest` dan `StudentDashboardPaymentTest` harus pass.
- **Tahap 4**: Test `GradeCalculationTest` harus pass setelah import diupdate.
- **Tahap 5**: Test `AttendanceTest` (Livewire CreateMeeting) harus pass.
- **Tahap 7**: Test `RbacTest` harus pass.
- **Tahap 10**: Full `php artisan test` — semua 0 failures.

### Manual Verification
- `php artisan route:list` — Semua URL tidak berubah
- Buka `/admin` — Panel Filament tetap berfungsi, semua menu muncul sesuai permission
