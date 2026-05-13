Saya memiliki project Laravel + Filament bernama Asy-Syams.

Saya ingin kamu melakukan MIGRASI STRUKTUR BACKEND ke arsitektur Feature-Driven MVC + Service Layer, tetapi dengan aturan paling penting:

JANGAN MENGUBAH ALUR KERJA SISTEM YANG SUDAH ADA.
JANGAN MENGUBAH FITUR.
JANGAN MENGUBAH ROUTE URL.
JANGAN MENGUBAH NAMA TABEL DATABASE.
JANGAN MENGUBAH MIGRATION.
JANGAN MENGUBAH STRUKTUR DATA.
JANGAN MENGUBAH TAMPILAN.
JANGAN MENGUBAH BEHAVIOR FILAMENT.
JANGAN MENGUBAH LOGIC BISNIS TANPA ALASAN.
JANGAN MENGHAPUS FITUR.
JANGAN REFACTOR BERLEBIHAN.

Tugas kamu hanya memindahkan dan merapikan lokasi file backend agar menjadi feature-driven, serta menyesuaikan namespace, use/import, route, Filament Resource, service injection, model relation, factory, seeder, dan unit test yang terdampak.

Project lama memiliki struktur utama seperti ini:

app/
├── Filament/
├── Http/
├── Mail/
├── Models/
├── Providers/
├── Services/
├── View/
└── dokumentasi/

Target arsitektur baru:

app/
├── Features/
│   ├── Academic/
│   │   ├── Models/
│   │   │   ├── ClassGroup.php
│   │   │   ├── Semester.php
│   │   │   └── Subject.php
│   │   ├── Controllers/
│   │   │   └── AcademicController.php
│   │   └── Services/
│   │       └── AcademicService.php
│   │
│   ├── Contacts/
│   │   ├── Controllers/
│   │   │   └── ContactController.php
│   │   └── Services/
│   │       └── ContactService.php
│   │
│   ├── Grades/
│   │   ├── Models/
│   │   │   ├── Assessment.php
│   │   │   ├── Evaluation.php
│   │   │   └── Grade.php
│   │   ├── Controllers/
│   │   │   └── GradeController.php
│   │   └── Services/
│   │       └── GradeService.php
│   │
│   ├── Meetings/
│   │   ├── Models/
│   │   │   ├── Attendance.php
│   │   │   └── Meeting.php
│   │   ├── Controllers/
│   │   │   └── MeetingController.php
│   │   └── Services/
│   │       └── MeetingService.php
│   │
│   ├── Payments/
│   │   ├── Models/
│   │   │   └── Payment.php
│   │   ├── Controllers/
│   │   │   └── PaymentController.php
│   │   └── Services/
│   │       └── PaymentService.php
│   │
│   ├── Permissions/
│   │   ├── Models/
│   │   │   └── RolePermission.php
│   │   ├── Controllers/
│   │   │   └── PermissionController.php
│   │   └── Services/
│   │       └── PermissionService.php
│   │
│   ├── Posts/
│   │   ├── Models/
│   │   │   └── Post.php
│   │   ├── Controllers/
│   │   │   └── PostController.php
│   │   └── Services/
│   │       └── PostService.php
│   │
│   ├── Reports/
│   │   ├── Controllers/
│   │   │   └── AssessmentReportController.php
│   │   └── Services/
│   │       └── ReportService.php
│   │
│   ├── SiteSettings/
│   │   ├── Models/
│   │   │   └── SiteSetting.php
│   │   ├── Controllers/
│   │   │   └── SiteSettingController.php
│   │   └── Services/
│   │       └── SiteSettingService.php
│   │
│   └── Students/
│       ├── Controllers/
│       │   └── StudentController.php
│       └── Services/
│           └── StudentService.php
│
├── Filament/
│   ├── Concerns/
│   └── Resources/
│
├── Http/
│   ├── Controllers/
│   │   ├── Controller.php
│   │   ├── ProfileController.php
│   │   └── Auth/
│   ├── Middleware/
│   └── Requests/
│
├── Mail/
├── Models/
│   └── User.php
│
├── Providers/
├── View/
└── dokumentasi/

Catatan penting:

- app/Filament tetap berada di app/Filament.
- app/Http/Middleware tetap berada di app/Http/Middleware.
- app/Http/Controllers/Controller.php tetap berada di app/Http/Controllers.
- app/Http/Controllers/Auth tetap berada di app/Http/Controllers/Auth.
- app/Http/Controllers/ProfileController.php tetap berada di app/Http/Controllers.
- app/Http/Requests tetap berada di app/Http/Requests.
- app/Mail tetap berada di app/Mail.
- app/Providers tetap berada di app/Providers.
- app/View tetap berada di app/View.
- app/dokumentasi tetap berada di app/dokumentasi.
- app/Models/User.php tetap berada di app/Models/User.php dan JANGAN dipindahkan dulu.
- database/migrations, database/seeders, dan database/factories tetap berada di folder database dan JANGAN dipindahkan.

File yang perlu dipindahkan:

1. Academic
   Pindahkan:

- app/Models/ClassGroup.php
- app/Models/Semester.php
- app/Models/Subject.php

Ke:

- app/Features/Academic/Models/ClassGroup.php
- app/Features/Academic/Models/Semester.php
- app/Features/Academic/Models/Subject.php

Buat jika diperlukan:

- app/Features/Academic/Controllers/AcademicController.php
- app/Features/Academic/Services/AcademicService.php

2. Grades
   Pindahkan:

- app/Models/Assessment.php
- app/Models/Evaluation.php
- app/Models/Grade.php
- app/Services/GradeCalculationService.php

Ke:

- app/Features/Grades/Models/Assessment.php
- app/Features/Grades/Models/Evaluation.php
- app/Features/Grades/Models/Grade.php
- app/Features/Grades/Services/GradeService.php

Catatan:

- Gabungkan/migrasikan isi GradeCalculationService ke GradeService tanpa mengubah logic perhitungan nilai.
- Jika terlalu berisiko, boleh pertahankan nama GradeCalculationService untuk sementara di dalam app/Features/Grades/Services, tetapi target akhir tetap 1 service utama yaitu GradeService.

3. Meetings
   Pindahkan:

- app/Models/Meeting.php
- app/Models/Attendance.php

Ke:

- app/Features/Meetings/Models/Meeting.php
- app/Features/Meetings/Models/Attendance.php

Buat jika diperlukan:

- app/Features/Meetings/Controllers/MeetingController.php
- app/Features/Meetings/Services/MeetingService.php

4. Payments
   Pindahkan:

- app/Models/Payment.php
- app/Http/Controllers/PaymentController.php
- app/Services/MidtransService.php

Ke:

- app/Features/Payments/Models/Payment.php
- app/Features/Payments/Controllers/PaymentController.php
- app/Features/Payments/Services/PaymentService.php

Catatan:

- Migrasikan logic MidtransService ke PaymentService tanpa mengubah logic webhook, validasi signature, status pembayaran, dan flow transaksi.
- Jangan mengubah URL route pembayaran.
- Jangan mengubah nama route jika sudah ada.
- Jangan mengubah request/response secara sengaja.
- Pastikan webhook Midtrans tetap berjalan seperti sebelumnya.

5. Permissions
   Pindahkan:

- app/Models/RolePermission.php

Ke:

- app/Features/Permissions/Models/RolePermission.php

Buat jika diperlukan:

- app/Features/Permissions/Controllers/PermissionController.php
- app/Features/Permissions/Services/PermissionService.php

Catatan:

- Jangan ubah logic permission.
- Jangan ubah behavior superadmin bypass.
- Jangan ubah behavior guru/student.
- Jangan ubah menu Filament.
- Jangan ubah aturan direct URL protection.
- Update semua import RolePermission dari App\Models\RolePermission menjadi App\Features\Permissions\Models\RolePermission.

6. Posts
   Pindahkan:

- app/Models/Post.php
- app/Http/Controllers/PostController.php

Ke:

- app/Features/Posts/Models/Post.php
- app/Features/Posts/Controllers/PostController.php

Buat:

- app/Features/Posts/Services/PostService.php

Catatan:

- Jangan ubah tampilan artikel.
- Jangan ubah route public post.
- Jangan ubah query kecuali hanya dipindahkan ke service dengan hasil yang sama.

7. SiteSettings
   Pindahkan:

- app/Models/SiteSetting.php

Ke:

- app/Features/SiteSettings/Models/SiteSetting.php

Buat jika diperlukan:

- app/Features/SiteSettings/Controllers/SiteSettingController.php
- app/Features/SiteSettings/Services/SiteSettingService.php

Catatan:

- Jangan ubah logic setting website.
- Jangan ubah asset/image path.
- Jangan ubah logic SPMB deadline.

8. Contacts
   Pindahkan:

- app/Http/Controllers/ContactController.php

Ke:

- app/Features/Contacts/Controllers/ContactController.php

Buat:

- app/Features/Contacts/Services/ContactService.php

Catatan:

- app/Mail/ContactFormMail.php tetap di app/Mail.
- Jangan ubah flow kirim email/contact form.

9. Reports
   Pindahkan:

- app/Http/Controllers/AssessmentReportController.php

Ke:

- app/Features/Reports/Controllers/AssessmentReportController.php

Buat:

- app/Features/Reports/Services/ReportService.php

Catatan:

- Jangan ubah hasil PDF/rapor.
- Jangan ubah route report.
- Jangan ubah query hasil nilai kecuali hanya dipindahkan ke service dengan output sama.

10. Students
    Pindahkan:

- app/Http/Controllers/StudentController.php

Ke:

- app/Features/Students/Controllers/StudentController.php

Buat:

- app/Features/Students/Services/StudentService.php

Catatan:

- Jangan ubah dashboard siswa.
- Jangan ubah flow login student.
- Jangan ubah tampilan student.

Aturan namespace:

Model:

- namespace App\Features\NamaFitur\Models;

Controller:

- namespace App\Features\NamaFitur\Controllers;

Service:

- namespace App\Features\NamaFitur\Services;

Contoh:

- App\Features\Payments\Models\Payment
- App\Features\Payments\Controllers\PaymentController
- App\Features\Payments\Services\PaymentService

Controller fitur tetap harus extend:
use App\Http\Controllers\Controller;

Contoh:
class PaymentController extends Controller

Hal yang wajib disesuaikan setelah file dipindahkan:

1. Namespace di setiap file yang dipindahkan.
2. Semua use/import class:
   - App\Models\Payment
   - App\Models\Post
   - App\Models\Grade
   - App\Models\Assessment
   - App\Models\Evaluation
   - App\Models\ClassGroup
   - App\Models\Subject
   - App\Models\Semester
   - App\Models\Meeting
   - App\Models\Attendance
   - App\Models\RolePermission
   - App\Models\SiteSetting
3. Route di routes/web.php dan file routes lain jika ada.
4. Filament Resource:
   - AssessmentResource
   - CandidateResource
   - ClassGroupResource
   - EvaluationResource
   - GradeResource
   - MeetingResource
   - PaymentResource
   - PostResource
   - ReportResource
   - RolePermissionResource
   - SemesterResource
   - SiteSettingResource
   - SubjectResource
   - UserResource
5. Filament Resource Pages.
6. Filament RelationManagers.
7. Middleware yang memakai RolePermission atau model lain.
8. User.php relasi ke model lain.
9. Model relation antar fitur.
10. Services yang memakai model.
11. Controllers yang memakai model/service.
12. Mail jika memakai model.
13. Blade views jika ada import class atau route binding.
14. Factory dan seeder jika memakai model.
15. Unit test dan feature test.
16. PHPUnit setup jika ada class reference.
17. config jika ada class reference.
18. provider jika ada binding service/model.

Aturan khusus untuk Filament:

- Jangan pindahkan app/Filament.
- Hanya update import model di Resource, Page, dan RelationManager.
- Pastikan protected static ?string $model tetap mengarah ke model baru.
- Pastikan form, table, action, relation manager, navigation, dan permission tetap sama.
- Jangan ubah nama resource, label, navigation group, atau route Filament kecuali benar-benar diperlukan akibat namespace model.

Aturan khusus untuk route:

- Jangan ubah URL.
- Jangan ubah nama route.
- Jangan ubah middleware route.
- Hanya ubah namespace controller/import controller agar mengarah ke lokasi baru.

Contoh:
Dari:
use App\Http\Controllers\PaymentController;

Menjadi:
use App\Features\Payments\Controllers\PaymentController;

Aturan khusus untuk service:

- Setiap fitur cukup memiliki 1 service utama.
- Jangan membuat banyak service kecil kecuali sangat diperlukan.
- Payment cukup PaymentService.
- Grade cukup GradeService.
- Report cukup ReportService.
- Permission cukup PermissionService.
- Prinsip yang dipakai:
  - SRP: setiap class punya tanggung jawab jelas.
  - KISS: jangan membuat struktur rumit.
  - DRY: hindari duplikasi logic.
  - YAGNI: jangan membuat abstraction/interface/repository kalau belum perlu.
  - Dependency Inversion diterapkan secara pragmatis hanya jika memang dibutuhkan.

Aturan khusus untuk unit test:

- Jangan hapus test.
- Jangan menonaktifkan test.
- Update namespace import pada test yang terdampak.
- Test yang mengakses URL harus tetap memakai URL lama.
- Test yang memakai model/service harus diarahkan ke namespace baru.
- Jalankan test setelah setiap tahap migrasi.
- Jika test gagal, perbaiki penyebabnya tanpa mengubah behavior sistem.

Perintah verifikasi yang wajib dijalankan setelah migrasi setiap fitur:

composer dump-autoload
php artisan optimize:clear
php artisan route:list
php artisan test

Jika ada error, perbaiki sampai test berjalan.

Urutan migrasi yang wajib dilakukan bertahap:

Tahap 1: Buat folder app/Features dan semua subfolder target.
Tahap 2: Migrasi Posts dan Contacts terlebih dahulu karena risikonya relatif kecil.
Tahap 3: Migrasi Payments.
Tahap 4: Migrasi Grades dan Reports.
Tahap 5: Migrasi Academic dan Meetings.
Tahap 6: Migrasi SiteSettings.
Tahap 7: Migrasi Permissions.
Tahap 8: Migrasi Students.
Tahap 9: Update seluruh import yang tersisa.
Tahap 10: Jalankan full test dan route check.

Jangan migrasi User.php di tahap ini. User.php tetap di app/Models/User.php.

Setelah selesai, berikan laporan dengan format:

1. Ringkasan migrasi yang dilakukan.
2. Daftar file yang dipindahkan.
3. Daftar namespace yang diubah.
4. Daftar route yang import controller-nya diperbarui.
5. Daftar Filament Resource yang diperbarui.
6. Daftar test yang diperbarui.
7. Hasil composer dump-autoload.
8. Hasil php artisan optimize:clear.
9. Hasil php artisan route:list.
10. Hasil php artisan test.
11. Catatan error yang ditemukan dan cara memperbaikinya.
12. Konfirmasi bahwa alur kerja sistem, URL, database, Filament, dan behavior tidak berubah.

Ingat:
Fokus tugas ini adalah MIGRASI STRUKTUR, bukan rewrite logic.
Jika menemukan bug atau improvement, catat saja di laporan, jangan langsung ubah kecuali diperlukan agar migrasi berhasil.
