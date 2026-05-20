# Dokumentasi Project Asy-Syams

## 1. Ringkasan Project

**Asy-Syams** adalah aplikasi web berbasis **Laravel 12** dan **Filament 3.3** untuk mendukung operasional Rumah Qur'an / Yayasan Pendidikan Tilawah Qur'an. Sistem mencakup halaman publik, SPMB/PPDB, dashboard santri, dashboard guru, dashboard superadmin, pengelolaan akademik, jadwal pertemuan, absensi santri, absensi ustad, assessment, evaluasi, nilai, tabel kontrol bacaan/hafalan, PDF laporan, pembayaran Midtrans, berita, kontak, pengaturan situs, dan RBAC.

Arsitektur utama memakai **Feature-Driven MVC + Service Layer**. Kode domain dikelompokkan di `app/Features/{NamaFitur}`, sedangkan resource Filament tetap di `app/Filament/Resources`.

Role aktual project:
- `superadmin`: bypass semua permission dan dapat mengakses semua panel.
- `guru`: akses fitur sesuai permission, termasuk check-in/check-out absensi ustad.
- `student`: akses dashboard santri dan data milik sendiri.

Status pengujian terbaru setelah update dokumentasi:
- Route aktif: 86 routes.
- Test target fitur baru: lulus.
- Full test: 127 passed, 3416 assertions.
- Coverage belum tersedia karena Xdebug/PCOV belum terpasang.

---

## 2. Tech Stack

- Backend: Laravel 12.
- Admin panel: Filament 3.3.
- Auth: Laravel Breeze.
- Frontend: Blade, Tailwind, Livewire/Filament.
- PDF: `barryvdh/laravel-dompdf`.
- Payment gateway: Midtrans Snap dan webhook.
- Database: MySQL/MariaDB di lokal, SQLite in-memory untuk test.
- Testing: PHPUnit 11 dan Laravel test utilities.
- Arsitektur: Feature-Driven MVC + Service Layer.

---

## 3. Struktur Folder Utama

```text
app/
  Features/
    Academic/
    Contacts/
    Grades/
    Meetings/
    Payments/
    Permissions/
    Posts/
    Reports/
    SiteSettings/
    Students/
    TeacherAttendances/
  Filament/
    Concerns/
    Resources/
  Http/
    Controllers/
    Middleware/
  Models/
    User.php
database/
  migrations/
  seeders/
resources/
  views/
routes/
tests/
app/dokumentasi/
```

Fungsi folder penting:
- `app/Features`: domain bisnis per fitur.
- `app/Filament/Resources`: CRUD/admin panel Filament.
- `app/Filament/Concerns/ChecksResourcePermission.php`: permission guard resource.
- `app/Http/Middleware`: middleware user aktif, permission, dan redirect akses admin.
- `app/Models/User.php`: model global untuk auth, role, permission helper, relasi lintas fitur.
- `routes/web.php`: route publik, dashboard, payment, PDF, dan action absensi ustad.
- `resources/views`: Blade untuk halaman publik, dashboard, laporan, PDF, dan modal.
- `tests`: regression, feature, unit, security, dashboard, payment, report, dan teacher attendance tests.

---

## 4. Modul/Fitur Utama

### Academic

Model:
- `ClassGroup`
- `Semester`
- `Subject`

Fungsi:
- Mengelola semester, mata pelajaran, kelas, guru pengampu, dan relasi santri ke kelas.
- `ClassGroup` menjadi pusat relasi untuk meeting, assessment, evaluation, dan report.

Relasi utama:
- `ClassGroup` belongs to `Subject`.
- `ClassGroup` belongs to `Semester`.
- `ClassGroup` belongs to teacher `User`.
- `ClassGroup` belongs to many student `User`.
- `ClassGroup` has many `Meeting`, `Assessment`, dan `Evaluation`.

### Contacts

Fungsi:
- Mengelola form kontak publik.
- Mengirim email/form message melalui service.
- Test memakai fake mail agar tidak mengirim email sungguhan.

### Grades

Model:
- `Assessment`
- `Evaluation`
- `Grade`

Service:
- `GradeCalculationService`
- `GradeReportService`

Fungsi:
- Mengelola assessment harian, evaluasi, nilai akhir, dan tabel kontrol santri.
- Assessment menyimpan detail bacaan/hafalan dalam JSON `data`.
- Evaluation menyimpan item evaluasi dalam JSON `items`.
- Grade menyimpan nilai akhir per santri, subject, dan semester.

Pembaruan terbaru:
- Assessment dapat menyimpan `catatan` opsional per item.
- Catatan tampil otomatis di tabel kontrol santri bagian **CATATAN PEMBELAJARAN**.
- Tabel kontrol santri menampilkan:
  - Header lembaga dan logo.
  - Nama dan kelas.
  - Kolom `NO`, `TANGGAL`, `ZIYADAH`, `L`, `C`, `TL`, `MUROJAAH`, `B`, `K`, `TAHSIN`, `TTD`.
  - Keterangan status.
  - Catatan pembelajaran.
  - Evaluasi.
  - Rekap absensi.
- PDF tabel kontrol santri tersedia dari halaman grades.

Route terkait:
- `GET /admin/grades/student/{user}/control`
- `GET /admin/grades/student/{user}/pdf`

View terkait:
- `resources/views/grades/student-control-table.blade.php`
- `resources/views/grades/student-control-modal.blade.php`
- `resources/views/grades/student-control-report.blade.php`
- `resources/views/pdf/student-grade-control.blade.php`

### Meetings

Model:
- `Meeting`
- `Attendance`

Fungsi:
- Mengelola jadwal pertemuan guru dengan class group.
- Mengelola absensi santri per meeting.
- Status santri: `present`, `sick`, `permission`, `alpha`.

Catatan:
- Tabel `attendances` khusus untuk absensi santri.
- Fitur absensi ustad tidak memakai tabel ini.

### Payments

Model:
- `Payment`

Controller/Service:
- `PaymentController`
- `MidtransService`

Fungsi:
- Checkout pembayaran santri via Midtrans Snap.
- Webhook Midtrans untuk update status pembayaran.
- Validasi signature webhook.
- Mencegah status pembayaran yang sudah lunas downgrade akibat webhook ulang.

Status penting:
- `pending`
- `paid`
- `success`
- `failed`

Mapping webhook:
- `settlement` dan `capture` menjadi paid/lunas.
- `pending` tetap pending.
- `deny`, `expire`, `cancel`, dan `failure` menjadi failed.

### Permissions

Model:
- `RolePermission`

Fungsi:
- Menyimpan permission per role.
- `superadmin` bypass permission.
- `guru` dan `student` mengikuti data `role_permissions`.

Permission baru untuk absensi ustad:
- `teacher-attendances.view`
- `teacher-attendances.create`
- `teacher-attendances.update`
- `teacher-attendances.delete`
- `teacher-attendances.manage`
- `teacher-attendances.check-in`
- `teacher-attendances.report`

Default seeder:
- `guru`: mendapat `teacher-attendances.check-in`.
- `student`: tidak mendapat permission absensi ustad.
- `superadmin`: bypass.

### Posts

Fungsi:
- Mengelola berita/artikel publik.
- Post memiliki slug, author, publish state, dan views.
- Halaman detail berita memakai `/berita/{slug}`.

### Reports

Fungsi:
- Mengelola laporan/rapor PDF.
- Rapor PDF memakai class group dan santri yang dipilih.
- DomPDF dipakai sebagai generator PDF.

Route:
- `GET /rapor-pdf/{class_group}/{user}`

### SiteSettings

Model:
- `SiteSetting`

Fungsi:
- Menyimpan pengaturan global aplikasi.
- Pengaturan halaman depan, kontak, deadline SPMB, dan jadwal absensi ustad.

Pembaruan terbaru:
- Superadmin dapat mengatur batas terlambat absensi ustad dari Filament.
- Key setting: `teacher_attendance_late_after`.
- Default: `08:00`.
- Halaman setting: `/admin/site-settings/teacher-attendance-schedule`.
- Jika batas diset `14:00`, check-in jam `13:25` menjadi `Hadir`, bukan `Terlambat`.

### Students

Controller/Service:
- `StudentController`
- `StudentService`

Fungsi:
- Dashboard hybrid untuk student, guru, dan superadmin.
- Student melihat status pembayaran, nilai, dan absensi milik sendiri.
- Guru melihat jadwal mengajar, rekap absensi santri dari meeting miliknya, dan absensi ustad milik sendiri.
- Superadmin melihat rekap absensi ustad hari ini.

Pembaruan terbaru:
- Dashboard guru otomatis menampilkan **Jadwal & Absensi** dari data `meetings` dan `attendances`.
- Guru hanya melihat meeting miliknya.
- Superadmin dapat melihat ringkasan absensi ustad hari ini.

### TeacherAttendances

Modul baru:
- `app/Features/TeacherAttendances/Models/TeacherAttendance.php`
- `app/Features/TeacherAttendances/Services/TeacherAttendanceService.php`
- `app/Features/TeacherAttendances/Controllers/TeacherAttendanceController.php`

Tabel:
- `teacher_attendances`

Kolom:
- `id`
- `user_id`
- `date`
- `check_in_at`
- `check_out_at`
- `status`
- `note`
- `created_by`
- `created_at`
- `updated_at`

Constraint:
- Unique `user_id + date`, agar satu guru hanya punya satu record per tanggal.

Status:
- `present`: hadir.
- `late`: terlambat.
- `permission`: izin.
- `sick`: sakit.
- `alpha`: alpha.

Rules:
- Guru hanya bisa check-in/check-out untuk dirinya sendiri.
- Student tidak boleh check-in.
- Check-in hanya sekali per tanggal.
- Check-out hanya bisa setelah check-in.
- Check-out tidak bisa dua kali.
- Jika status manual `permission`, `sick`, atau `alpha`, guru tidak perlu check-in/check-out.
- Batas terlambat tidak hardcode; dibaca dari `site_settings.teacher_attendance_late_after`.

Route:
- `POST /teacher-attendances/check-in`
- `POST /teacher-attendances/check-out`

Filament Resource:
- `app/Filament/Resources/TeacherAttendanceResource.php`
- URL: `/admin/teacher-attendances`

Fitur resource:
- List absensi ustad.
- Filter tanggal.
- Filter guru.
- Filter status.
- Create manual oleh superadmin.
- Edit status dan catatan.
- View detail.
- Badge status berwarna.
- Default sort tanggal terbaru.
- Permission memakai `ChecksResourcePermission`.

Dashboard guru:
- Section **Absensi Saya Hari Ini**.
- Status hari ini: Belum Absen, Hadir, Terlambat, Izin, Sakit, Alpha.
- Tombol Check In.
- Tombol Check Out.
- Riwayat 7 hari terakhir.

Dashboard superadmin:
- Ringkasan guru hadir hari ini.
- Guru terlambat hari ini.
- Guru izin/sakit hari ini.
- Guru alpha hari ini.
- Guru belum absen hari ini.
- Tabel absensi ustad hari ini.

---

## 5. Alur Bisnis Utama

### Pendaftaran/SPMB

1. Calon santri mengakses halaman publik/register.
2. Data user dibuat sebagai `student`.
3. User baru dapat berada pada status belum aktif sampai diverifikasi.
4. Admin/superadmin mengelola data calon santri dari Filament.
5. Deadline SPMB diatur melalui SiteSettings.

### Manajemen Siswa

1. User student disimpan di `users`.
2. Student dapat dimasukkan ke class group.
3. Dashboard student hanya mengambil data milik user login.
4. Payment, attendance, grades, dan report dibatasi per student.

### Manajemen Kelas

1. Superadmin/guru dengan permission membuat semester dan subject.
2. Class group dibuat dengan subject, semester, dan teacher.
3. Student dihubungkan ke class group melalui pivot.
4. Meeting, assessment, evaluation, dan report membaca class group.

### Pertemuan dan Absensi Santri

1. Guru membuat meeting untuk class group.
2. Sistem menampilkan daftar santri dari class group.
3. Guru mengisi status santri: present, sick, permission, alpha.
4. Data tersimpan di tabel `attendances`.
5. Dashboard guru menampilkan rekap dari meeting miliknya.
6. Laporan santri mengambil rekap absensi dari meeting/class group terkait.

### Absensi Ustad

1. Guru login ke `/dashboard`.
2. Guru melihat section **Absensi Saya Hari Ini**.
3. Jika belum ada record hari ini, tombol **Check In** muncul.
4. Check-in membuat record di `teacher_attendances`.
5. Status otomatis:
   - sebelum/sama batas terlambat: `present`.
   - setelah batas terlambat: `late`.
6. Batas terlambat diatur superadmin di `/admin/site-settings/teacher-attendance-schedule`.
7. Setelah check-in, tombol **Check Out** muncul.
8. Check-out mengisi `check_out_at`.
9. Superadmin dapat membuat record manual untuk permission, sick, atau alpha.
10. Superadmin melihat rekap dan tabel absensi ustad hari ini.

### Penilaian

1. Guru/admin membuka `/admin/assessments`.
2. Guru memilih kelas, santri, jenis penilaian, dan detail bacaan/hafalan.
3. Data detail disimpan dalam JSON `data`.
4. Catatan performa dapat diisi opsional per item.
5. Service report membaca assessment dan mengelompokkan data untuk tabel kontrol santri.
6. Grade akhir disimpan di `grades`.

### Pembayaran

1. Student melihat status tagihan semester aktif di dashboard.
2. Jika belum lunas, tombol bayar muncul.
3. Checkout menghasilkan Snap token.
4. Midtrans mengirim webhook.
5. Webhook valid mengubah status payment.
6. Payment yang sudah lunas tidak di-downgrade oleh webhook ulang.

### Laporan/Rapor

1. Guru/admin melihat data grade.
2. Tabel kontrol santri dapat dilihat dari modal/action di GradeResource.
3. PDF tabel kontrol santri dapat diunduh.
4. Rapor PDF tetap tersedia via route `/rapor-pdf/{class_group}/{user}`.

---

## 6. Filament Admin Panel

Resource penting yang tersedia:
- Candidate/SPMB Resource.
- User Resource.
- Semester Resource.
- ClassGroup Resource.
- Meeting Resource.
- Assessment Resource.
- Evaluation Resource.
- Grade Resource.
- Report Resource.
- Payment Resource.
- Post Resource.
- SiteSetting Resource.
- RolePermission Resource.
- TeacherAttendance Resource.

Permission guard:
- Resource mengikuti trait `ChecksResourcePermission` jika sudah memakai pola permission.
- `superadmin` bypass.
- `guru` harus punya permission yang sesuai.
- `student` tidak boleh masuk admin panel.

Resource baru:
- `TeacherAttendanceResource`
- Navigation group: Akademik (Ustad)
- Label: Absensi Ustad
- Permission base: `teacher-attendances`

SiteSetting page baru:
- `ManageTeacherAttendanceSchedule`
- URL: `/admin/site-settings/teacher-attendance-schedule`
- Fungsi: mengatur batas terlambat absensi ustad.

---

## 7. Role dan Permission

Role aktual:
- `superadmin`
- `guru`
- `student`

Catatan:
- `RolePermission::ROLES` menyimpan role yang dikelola permission eksplisit: `guru` dan `student`.
- `superadmin` tidak butuh record permission karena bypass.

Helper:
- `User::hasAccess($permission)`
- `User::hasAnyAccess($permissions)`
- `User::canAccessPanel($panel)`
- `User::getFirstAllowedFilamentRoute()`

Middleware:
- `EnsureUserHasPermission`
- `EnsureUserIsActive`
- `RedirectUnauthorizedFilamentAccess`

Permission teacher attendance:
- `teacher-attendances.view`: lihat absensi ustad.
- `teacher-attendances.create`: tambah absensi ustad.
- `teacher-attendances.update`: edit absensi ustad.
- `teacher-attendances.delete`: hapus absensi ustad.
- `teacher-attendances.manage`: kelola semua absensi ustad.
- `teacher-attendances.check-in`: check-in/check-out dari dashboard guru.
- `teacher-attendances.report`: laporan absensi ustad.

---

## 8. Database dan Relasi

Tabel utama:
- `users`
- `role_permissions`
- `semesters`
- `subjects`
- `class_groups`
- `class_group_student`
- `meetings`
- `attendances`
- `assessments`
- `evaluations`
- `grades`
- `payments`
- `posts`
- `site_settings`
- `teacher_attendances`

Relasi utama:
- `users` has many payments.
- `users` has many grades.
- `users` has many assessments.
- `users` has many evaluations.
- `users` has many attendances sebagai santri.
- `users` has many meetings sebagai guru.
- `users` has many teacher attendances sebagai guru.
- `users` has many created teacher attendances sebagai pembuat manual.
- `class_groups` belongs to subject, semester, teacher.
- `class_groups` belongs to many students.
- `meetings` belongs to class group and teacher.
- `attendances` belongs to meeting and student.
- `assessments` belongs to class group and student.
- `evaluations` belongs to class group and student.
- `grades` belongs to student, subject, semester.
- `teacher_attendances` belongs to user and creator.

---

## 9. Testing

Test yang relevan:
- Auth tests.
- RBAC tests.
- Permission tests.
- Filament resource tests.
- Academic flow tests.
- Meetings/attendance tests.
- Grade service tests.
- Student grade control report tests.
- Teacher attendance tests.
- Payment/Midtrans webhook tests.
- Security regression tests.
- Student dashboard tests.
- Report PDF tests.
- Site settings tests.

Teacher attendance test mencakup:
- Guru bisa check-in sendiri.
- Check-in lewat batas menjadi late.
- Batas late dapat dikonfigurasi via SiteSetting.
- Guru tidak bisa double check-in.
- Guru bisa check-out setelah check-in.
- Guru tidak bisa check-out sebelum check-in.
- Student tidak boleh check-in.
- Superadmin bisa membuat manual attendance via service.
- Unique `user_id + date` bekerja.
- Dashboard guru menampilkan status absensi hari ini.
- Filament resource terlindungi permission.

Command penting:

```bash
php artisan route:list
php artisan test
php artisan test tests/Feature/TeacherAttendances/TeacherAttendanceTest.php
```

Catatan testing:
- Full test terbaru menghasilkan `127 passed, 3416 assertions`.
- Regression namespace sudah lulus setelah dokumentasi lama dibersihkan dari referensi legacy.

---

## 10. Dokumentasi Perubahan Terbaru

### Tabel Kontrol Santri / Grade Control

Fitur:
- Action di GradeResource untuk melihat tabel santri.
- Action download PDF.
- Data kelas otomatis dari relasi class group.
- Logo dan header lembaga.
- Tabel garis-garis rapi seperti buku kontrol.
- Catatan pembelajaran dari assessment.
- Evaluasi dan rekap absensi di bawah tabel.

Sumber data:
- `Assessment.data` untuk ziyadah, murojaah, tahsin, nilai, dan catatan.
- `Evaluation.items` untuk evaluasi.
- `Attendance` untuk rekap absensi santri.
- `SiteSetting` untuk kontak/alamat lembaga.

### Catatan Assessment

Fitur:
- Field `catatan` opsional di repeater AssessmentResource.
- Catatan masuk ke JSON `data`.
- GradeReportService membaca catatan dan menampilkan di laporan.
- Tidak perlu migration karena memakai kolom JSON yang sudah ada.

### Dashboard Guru

Fitur:
- Jadwal & Absensi otomatis dari meeting dan attendance.
- Guru hanya melihat jadwal miliknya.
- Rekap hadir/sakit/izin/alpha santri tampil otomatis.
- Tombol edit meeting untuk isi absensi.
- Section absensi ustad untuk check-in/check-out.

### Absensi Ustad

Fitur:
- Modul baru `TeacherAttendances`.
- Tabel baru `teacher_attendances`.
- Dashboard guru untuk check-in/check-out.
- Filament resource untuk superadmin.
- Jadwal/batas terlambat diatur via SiteSetting.
- Tidak mencampur data dengan absensi santri.

### Mockup Data

Seeder `MockupDataSeeder` menyediakan:
- User superadmin.
- User guru.
- User student.
- Semester aktif/lalu.
- Subject.
- Class group.
- Meeting.
- Attendance santri.
- Assessment dengan catatan.
- Evaluation.
- Grade.
- Payment.
- Site settings.
- Posts.
- Default setting `teacher_attendance_late_after`.

Password akun demo:
- `Password123!`

---

## 11. Security Hardening

Keamanan penting:
- Student tidak bisa akses admin panel.
- Guru hanya bisa akses resource admin jika punya permission.
- Superadmin bypass semua permission.
- Data dashboard student dibatasi ke user login.
- Data absensi guru dari dashboard hanya untuk guru login.
- Filament TeacherAttendanceResource membatasi query untuk non-superadmin jika diberi akses view.
- Webhook Midtrans validasi signature.
- Debug route hanya aktif pada environment local.

---

## 12. Known Issue dan Catatan Teknis

Known issue:
- `SubjectResource.php` masih kosong.
- Coverage belum bisa dijalankan tanpa Xdebug/PCOV.
- PDF logo memakai fallback jika extension GD tidak tersedia.
- Full test sebelumnya gagal karena dokumentasi lama, bukan logic aplikasi.

Catatan:
- Jangan memakai tabel `attendances` untuk absensi ustad.
- Jangan menambah role `admin` karena role aktual hanya `superadmin`, `guru`, `student`.
- Jangan mengubah route publik lama tanpa test regression.
- Permission baru harus diseed dengan:

```bash
php artisan db:seed --class=RolePermissionSeeder
php artisan optimize:clear
```

---

## 13. Rekomendasi Lanjutan

Rekomendasi aman:
- Tambahkan export Excel/PDF untuk absensi ustad.
- Tambahkan auto-generate `alpha` untuk guru yang belum check-in setelah jam tertentu.
- Tambahkan pengaturan jam pulang minimal jika dibutuhkan.
- Tambahkan audit log untuk perubahan manual absensi ustad.
- Tambahkan validasi agar manual attendance hanya memilih user role `guru`.
- Lengkapi `SubjectResource.php` jika subject perlu dikelola dari Filament.
- Pasang PCOV/Xdebug untuk coverage.
- Perbarui README agar role tertulis `superadmin`, `guru`, `student`, bukan role lama.

---

## 14. Kesimpulan

Project Asy-Syams sekarang memiliki alur akademik dan administrasi yang lebih lengkap:
- Santri memiliki dashboard, pembayaran, absensi, nilai, dan laporan.
- Guru memiliki dashboard jadwal, absensi santri, input assessment, dan absensi ustad.
- Superadmin mengelola resource Filament, permission, jadwal absensi ustad, dan laporan.
- Fitur absensi ustad dibuat sebagai modul terpisah sesuai arsitektur Feature-Driven MVC + Service Layer.
- Fitur baru bersifat additive dan tidak merusak absensi santri atau fitur lama.
