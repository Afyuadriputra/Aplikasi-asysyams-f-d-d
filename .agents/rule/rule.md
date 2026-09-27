
# TASK: AUDIT CODEBASE + BUILD COMPREHENSIVE AUTOMATED TEST SUITE

Saya ingin kamu melakukan audit menyeluruh terhadap repository Laravel ini untuk mencari bug, mismatch schema, regression, authorization issue, validation issue, dan business-logic inconsistency.

Project ini adalah aplikasi Laravel modular untuk YPTQ / Rumah Qur'an Asy-Syams dengan fitur:

- Authentication
- SPMB / PPDB
- Student Dashboard
- Guru Dashboard
- Filament Admin
- Role & Permission / RBAC
- Class Group
- Semester
- Meeting
- Student Attendance
- Teacher Attendance
- Assessment
- Evaluation
- Grade / Report
- PDF Report
- Midtrans Payment
- Site Settings
- Posts

JANGAN langsung mengubah logic production hanya untuk membuat test menjadi PASS.

Tujuan utama fase ini:

1. Temukan apakah bug yang dicurigai benar-benar ada.
2. Tulis automated test yang mampu mereproduksi bug tersebut.
3. Pisahkan:
   - bug nyata,
   - test lama yang stale,
   - schema mismatch,
   - business-rule mismatch,
   - authorization/security issue.
4. Jalankan seluruh test suite.
5. Berikan laporan PASS / FAIL lengkap.
6. Jangan menyembunyikan failure dengan menghapus assertion atau melemahkan test.

---

# 1. INITIAL REPOSITORY AUDIT

Pelajari terlebih dahulu:

- `composer.json`
- `package.json`
- `routes/web.php`
- `routes/auth.php`
- `bootstrap/app.php`
- `app/Models/User.php`
- seluruh `app/Features/**`
- seluruh `app/Filament/**`
- seluruh `app/Http/Middleware/**`
- seluruh `database/migrations/**`
- seluruh `database/seeders/**`
- seluruh `tests/**`

Identifikasi relasi antara:

```text
User
 ├── ClassGroup
 ├── Payment
 ├── Grade
 ├── Attendance
 ├── Assessment
 ├── Evaluation
 └── TeacherAttendance

Semester
 ├── ClassGroup
 ├── Grade
 └── Payment

ClassGroup
 ├── Students
 ├── Teacher
 ├── Subject
 ├── Semester
 ├── Meeting
 ├── Assessment
 └── Evaluation
```

Sebelum membuat test, buat peta singkat mengenai:

- production flow,
- schema database,
- authorization,
- business rule.

---

# 2. SUSPECTED BUGS YANG WAJIB DIVERIFIKASI

Jangan asumsikan bug berikut benar.

Buktikan melalui code inspection dan automated test.

## A. Assessment type mismatch

Cek apakah migration `assessments` hanya menerima:

```text
ziyadah
murojaah
tahsin
tilawah
```

sementara `AssessmentResource` menawarkan:

```text
tahsin
tahfidz
tajwid
```

Buat test yang membuktikan apakah:

```text
assessment_type = tahfidz
```

dan:

```text
assessment_type = tajwid
```

dapat disimpan atau justru gagal pada database.

Target:

```text
tests/Feature/Grades/AssessmentSchemaContractTest.php
```

---

## B. Assessment month/year mismatch

Periksa apakah:

```text
AssessmentResource
Assessment model
```

menggunakan:

```text
month
year
```

sementara migration `assessments` tidak mempunyai kolom tersebut.

Buat test yang memverifikasi schema:

```php
Schema::hasColumn('assessments', 'month')
Schema::hasColumn('assessments', 'year')
```

Jika form production mengirim field tersebut sementara DB tidak memilikinya, test harus mampu menunjukkan mismatch tersebut.

Target:

```text
tests/Feature/Grades/AssessmentDatabaseContractTest.php
```

---

## C. Active Semester invariant

Business rule:

```text
hanya satu semester boleh aktif
```

Periksa apakah aplikasi benar-benar mencegah:

```text
Semester A => is_active = true
Semester B => is_active = true
```

Buat test:

```text
test_only_one_semester_can_be_active()
```

Jika dua semester aktif masih bisa tersimpan, test harus FAIL dan laporkan sebagai bug.

Target:

```text
tests/Feature/Academic/ActiveSemesterTest.php
```

---

## D. Payment status inconsistency

Cari penggunaan status:

```text
pending
paid
success
failed
expired
cancelled
```

Periksa seluruh:

- PaymentController
- MidtransService
- PaymentResource
- Semester payment relation manager
- Student dashboard
- tests

Buat contract test yang memastikan satu status sukses canonical digunakan secara konsisten.

Verifikasi khusus:

```text
Midtrans webhook -> paid
```

sementara Filament mungkin hanya mengenal:

```text
success
```

Buat test yang mampu menemukan discrepancy tersebut.

Target:

```text
tests/Feature/Payments/PaymentStatusContractTest.php
```

---

## E. Semester PaymentsRelationManager

Periksa file:

```text
app/Filament/Resources/SemesterResource/RelationManagers/PaymentsRelationManager.php
```

lalu cek apakah benar didaftarkan di:

```php
SemesterResource::getRelations()
```

Buat test yang memastikan relation manager yang sudah dibuat benar-benar registered.

Target:

```text
tests/Feature/Filament/SemesterPaymentRelationTest.php
```

---

## F. Pending student dapat masuk kelas

Saat attach siswa ke `ClassGroup`, cek apakah query hanya memfilter:

```text
role = student
```

atau juga:

```text
is_active = true
```

Business rule yang diharapkan:

```text
student yang belum diapprove
tidak boleh dimasukkan ke kelas aktif.
```

Buat test yang membuktikan behavior sekarang.

Target:

```text
tests/Feature/Academic/ClassGroupStudentEligibilityTest.php
```

---

## G. SPMB deadline

Setting:

```text
spmb_deadline
```

sudah digunakan untuk countdown.

Sekarang verifikasi apakah deadline tersebut juga menutup:

```text
GET /register
POST /register
```

Buat test:

```text
registration_is_open_before_deadline
registration_is_blocked_after_deadline
```

Jangan menambahkan logic production terlebih dahulu.

Biarkan test menunjukkan apakah business rule itu sudah ada atau belum.

Target:

```text
tests/Feature/Auth/SpmbDeadlineRegistrationTest.php
```

---

## H. Grade calculation pipeline

Ada service:

```text
GradeCalculationService
```

formula:

```text
40% assessment average
60% evaluation average
```

Tetapi periksa apakah hasil tersebut benar-benar digunakan untuk menghasilkan:

```text
Grade.score
```

atau `Grade.score` masih input manual.

Buat test yang mendokumentasikan behavior aktual.

Misalnya:

```text
assessment_and_evaluation_do_not_automatically_create_grade
```

atau jika ternyata ada automation:

```text
grade_is_generated_from_assessment_and_evaluation
```

Target:

```text
tests/Feature/Grades/GradePipelineTest.php
```

---

# 3. UNIT TEST — SERVICE / BUSINESS LOGIC

Buat atau lengkapi Unit Test untuk service-layer berikut.

## GradeCalculationService

Test minimal:

```text
assessment L -> 100
assessment C -> 75
assessment TL -> 50

case insensitive:
l
c
tl

whitespace:
" L "
" C "
" TL "

numeric assessment values

invalid assessment data

empty assessment

evaluation numeric score

evaluation decimal score

invalid evaluation data

empty evaluation

40/60 weighted calculation

zero division handling
```

Tambahkan edge case:

```text
score < 0
score > 100
null score
empty string
unexpected type
```

Jangan ubah service jika test gagal.

Laporkan dulu.

Target:

```text
tests/Unit/Grades/GradeCalculationServiceTest.php
```

---

## GradeReportService

Test:

```text
student report uses correct class

teacher only accesses students from own class

teacher cannot access unrelated student

superadmin can access all reports

student cannot access admin report

assessment mapped correctly

evaluation summary mapped correctly

attendance summary correct

attendance percentage correct

no attendance => percentage 0

legacy evaluation without surah/song still works

notes correctly aggregated
```

---

## TeacherAttendanceService

Test:

```text
only guru can check in

student cannot check in

superadmin cannot use normal guru check-in

one check-in per day

cannot checkout before checkin

cannot checkout twice

status present before late threshold

status late after threshold

configured late threshold overrides default

permission/sick/alpha cannot checkout

superadmin can create manual attendance

guru cannot create manual admin attendance
```

Target:

```text
tests/Unit/TeacherAttendances/TeacherAttendanceServiceTest.php
```

---

## MidtransService

Test:

```text
valid SHA512 signature accepted

invalid signature rejected

unknown order rejected

settlement -> paid

capture -> paid

pending -> pending

deny -> failed

expire -> failed

cancel -> failed

failure -> failed

paid cannot downgrade to pending

paid cannot downgrade to failed

payment_type stored

payment_detail stored
```

Target:

```text
tests/Unit/Payments/MidtransServiceTest.php
```

---

# 4. FEATURE TEST — ENDPOINT / APPLICATION FLOW

Walaupun project mayoritas menggunakan `routes/web.php`, treat setiap externally accessed route sebagai endpoint.

Test minimal:

## Public

```text
GET /
GET /register
GET /login
```

Expected:

```text
200
```

---

## Guest protection

Guest tidak boleh membuka:

```text
/dashboard
/transcript
/attendance
/admin
/payment/checkout
```

Expected:

```text
redirect login / admin login / forbidden sesuai design
```

---

## Registration

Test full registration payload:

```text
valid registration
duplicate email
duplicate NISN
invalid gender
invalid email
missing required field
invalid phone
invalid NISN
password confirmation mismatch
```

Valid registration harus menghasilkan:

```text
role = student
is_active = false
```

dan redirect menuju flow approval.

---

## Student Dashboard

Student aktif:

```text
GET /dashboard => 200
```

Student tidak aktif:

```text
GET /dashboard
=> redirect approval.notice
```

Verifikasi student hanya melihat:

```text
payment sendiri
attendance sendiri
grade sendiri
```

---

## Midtrans checkout endpoint

Test:

```text
no active semester
tuition_fee = 0
missing Midtrans config
existing pending payment reused
paid payment tidak membuat transaksi baru
payment belongs to logged-in student
```

Jika perlu mock external Midtrans SDK.

Jangan panggil jaringan external pada test suite.

---

## Midtrans webhook

Test full endpoint:

```text
POST /payment/webhook
```

dengan signed payload.

Verifikasi:

```text
200 untuk valid webhook
403 untuk invalid signature
database state berubah benar
```

---

# 5. AUTH & PERMISSION TEST

Test semua role utama:

```text
superadmin
guru
student active
student inactive
guest
```

## Superadmin

Harus bisa:

```text
/admin
/admin/users
/admin/payments
/admin/meetings
/admin/grades
```

tanpa record `role_permissions`.

---

## Guru

Dengan permission:

```text
meetings.manage
```

boleh membuka meeting.

Tanpa permission:

```text
tidak boleh membuka direct URL meeting/payment/user
```

Jangan hanya test menu visibility.

Test direct URL authorization.

---

## Student

Student tidak boleh masuk:

```text
/admin/**
```

meskipun mencoba direct URL.

Expected:

```text
redirect dashboard
```

---

## Row-level authorization

Ini sangat penting.

Buat:

```text
Guru A
Class A
Student A

Guru B
Class B
Student B
```

Test:

```text
Guru A boleh mengakses Student A report
Guru A tidak boleh mengakses Student B report
```

Lalu audit apakah hal yang sama berlaku untuk:

```text
Meeting
Assessment
Evaluation
Grade
ClassGroup
```

Jika resource-level permission lolos tetapi record guru lain masih terlihat, buat failing regression test dan laporkan sebagai:

```text
ROW_LEVEL_AUTHORIZATION_GAP
```

---

# 6. VALIDATION TEST

Buat validation tests untuk semua input kritis.

## Registration

```text
name required
email required/valid/unique
NISN required/numeric/unique
birth_date required/date
mother_name required
school_origin required
address required
phone required
gender only L/P
password required/confirmed
```

---

## Semester

```text
name required
start_date required
end_date required
end_date >= start_date
tuition_fee >= 0
one active semester
```

Jika rule tidak ada, test harus menunjukkan failure.

---

## ClassGroup

Test:

```text
valid class type

invalid class type rejected

Tahsin cannot have letter

Baca Tulis cannot have letter

Murottal must have letter

Tilawah must have letter

letter only A-Z

duplicate:
semester + class_type + class_letter
must fail
```

---

## Assessment

Test:

```text
class_group required
student required
student belongs to selected class
assessment_type valid
JSON payload valid

score L/C/TL valid

unsupported type rejected
```

---

## Evaluation

Test:

```text
class_group required
student required
evaluation_number only supported value
score between 0-100
```

---

## Payment

Test:

```text
amount > 0
valid semester
valid student
unique order_id
valid canonical status
```

---

# 7. DATABASE TEST — MIGRATION / SEEDER / RELATIONSHIP

Buat test suite khusus database.

Target:

```text
tests/Feature/Database/
```

## Migration contract

Verifikasi tabel:

```text
users
semesters
subjects
class_groups
class_group_student
meetings
attendances
assessments
evaluations
grades
payments
site_settings
role_permissions
teacher_attendances
```

Gunakan:

```php
Schema::hasTable()
Schema::hasColumn()
```

---

## Foreign key / relation test

Verifikasi:

```text
User -> Payments
User -> Grades
User -> Attendances

ClassGroup -> Semester
ClassGroup -> Subject
ClassGroup -> Teacher

ClassGroup <-> Students

Meeting -> ClassGroup
Meeting -> Teacher
Meeting -> Attendances

Assessment -> Student
Assessment -> ClassGroup

Evaluation -> Student
Evaluation -> ClassGroup

Payment -> Student
Payment -> Semester

Grade -> Student
Grade -> Subject
Grade -> Semester
```

---

## Unique constraints

Test:

```text
users.email unique
users.nisn unique

class_group_student:
class_group_id + user_id unique

assessment:
class_group_id + user_id + assessment_type unique

evaluation:
class_group_id + user_id + evaluation_number unique

teacher_attendances:
user_id + date unique

payments.order_id unique
```

---

# 8. SEEDER TEST

Test:

```text
RolePermissionSeeder
SubjectSeeder
MockupDataSeeder
DatabaseSeeder
```

Jalankan:

```bash
php artisan migrate:fresh --seed
```

dan jika diperlukan:

```bash
php artisan db:seed --class=MockupDataSeeder
```

Verifikasi tidak ada:

```text
SQLSTATE
unknown column
enum violation
duplicate unique key
foreign key violation
mass assignment error
```

MockupDataSeeder minimal harus menghasilkan:

```text
1 superadmin
>= 1 guru
>= 1 active student
>= 1 inactive candidate
1 active semester
subjects
class groups
meetings
attendances
assessments
evaluations
grades
payments
site settings
```

Verifikasi hanya:

```text
1 active semester
```

setelah seeder selesai.

---

# 9. REPOSITORY / QUERY BEHAVIOR TEST

Project menggunakan Eloquent langsung dan tidak mempunyai Repository Pattern penuh.

Jadi jangan membuat repository abstraction baru hanya demi test.

Test query behavior yang berperan seperti repository.

Contoh:

```text
CandidateResource:
hanya student inactive

student dashboard:
payment query hanya milik authenticated user

teacher dashboard:
guru hanya mengambil meeting yang user_id = guru

superadmin dashboard:
boleh mengambil seluruh meeting

ClassGroup students:
hanya pivot deleted_at IS NULL

GradeReportService:
class selection sesuai grade/semester/subject
```

---

# 10. REGRESSION TEST

Setiap bug yang ditemukan harus mendapat regression test.

Naming contoh:

```text
test_inactive_student_cannot_access_dashboard
test_student_cannot_access_filament
test_paid_payment_cannot_be_downgraded
test_teacher_cannot_view_other_teacher_student_report
test_assessment_resource_type_matches_database_enum
test_only_one_semester_is_active
```

Jangan memperbaiki bug tanpa menambahkan regression test terlebih dahulu.

Flow:

```text
RED
 ↓
reproduce bug
 ↓
document root cause
 ↓
fix
 ↓
GREEN
```

Tetapi pada task ini utamakan terlebih dahulu:

```text
AUDIT + TEST CREATION
```

Jika bug ditemukan, tampilkan dahulu sebelum melakukan production fix.

---

# 11. TEST EXECUTION

Setelah test dibuat, jalankan:

```bash
php artisan optimize:clear
php artisan migrate:fresh --env=testing
php artisan test
```

Jika suite besar, juga jalankan kategori:

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

Dan selective:

```bash
php artisan test tests/Feature/Grades
php artisan test tests/Feature/Payments
php artisan test tests/Feature/Permissions
php artisan test tests/Feature/Security
php artisan test tests/Feature/TeacherAttendances
```

Jangan hanya berkata:

```text
tests should pass
```

Saya ingin test benar-benar dijalankan.

---

# 12. JANGAN LAKUKAN

Jangan:

- menghapus test lama hanya karena gagal,
- mengubah assertion menjadi terlalu longgar,
- menggunakan `withoutExceptionHandling()` untuk menyembunyikan bug,
- melewati permission middleware,
- mematikan foreign key,
- mengubah migration historis sembarangan,
- mengubah enum hanya untuk membuat test hijau sebelum root cause jelas,
- membuat production workaround palsu,
- mengakses internet di test,
- menggunakan Midtrans production,
- menggunakan database production,
- menghapus record user/payment agar test lewat.

---

# 13. OUTPUT YANG SAYA INGINKAN

Setelah audit selesai, berikan laporan dengan format:

```text
# TEST AUDIT RESULT

TOTAL TESTS:
PASSED:
FAILED:
SKIPPED:

STATUS:
PASS / PARTIAL PASS / FAIL
```

Kemudian:

```text
# CONFIRMED BUGS

BUG-001
Title:
Severity:
Affected Files:
Reproduction:
Expected:
Actual:
Test:
Root Cause:
Recommended Fix:
```

Lanjut:

```text
# SCHEMA MISMATCH

SCHEMA-001
...
```

Lanjut:

```text
# AUTHORIZATION FINDINGS
```

Lanjut:

```text
# VALIDATION GAPS
```

Lanjut:

```text
# STALE TESTS
```

Jika test lama sudah tidak sesuai implementasi terbaru, jangan langsung menghapus.

Tandai:

```text
STALE_TEST
```

dan jelaskan alasannya.

---

# 14. FINAL TEST MATRIX

Buat tabel akhir:

| Area               | Unit | Feature | Auth | Validation | Database | Status    |
| ------------------ | ---- | ------- | ---- | ---------- | -------- | --------- |
| Registration       | —   | ✅      | ✅   | ✅         | ✅       | PASS/FAIL |
| ClassGroup         | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Meeting            | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Attendance         | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Teacher Attendance | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Assessment         | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Evaluation         | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Grade              | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| Report             | ✅   | ✅      | ✅   | —         | ✅       |           |
| Payment            | ✅   | ✅      | ✅   | ✅         | ✅       |           |
| RBAC               | —   | ✅      | ✅   | —         | ✅       |           |
| SiteSettings       | ✅   | ✅      | ✅   | ✅         | ✅       |           |

---

# 15. PRIORITY

Audit dengan urutan:

```text
P0
Assessment schema contract
Payment correctness
Authentication
RBAC / security

P1
Semester active invariant
Class ownership / row-level authorization
SPMB deadline
Seeder integrity

P2
Grade automation
UI/domain naming mismatch
legacy/stale tests
```

Kerjakan sampai kita mempunyai bukti berbasis test mengenai kondisi codebase saat ini.

Jangan menebak.

Gunakan source code, migration, database contract, dan hasil test aktual sebagai sumber kebenaran.
