<?php

namespace Database\Seeders;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Features\Meetings\Models\Meeting;
use App\Features\Payments\Models\Payment;
use App\Features\Permissions\Models\RolePermission;
use App\Features\Posts\Models\Post;
use App\Features\SiteSettings\Models\SiteSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MockupDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolePermissionSeeder::class);
        $this->call(SubjectSeeder::class);

        $superadmin = $this->user([
            'name' => 'Super Admin Asy-Syams',
            'email' => 'superadmin@asy-syams.test',
            'role' => 'superadmin',
            'is_active' => true,
            'phone' => '081200000001',
            'gender' => 'L',
            'address' => 'Kantor Yayasan Asy-Syams',
        ]);

        $guruTahsin = $this->user([
            'name' => 'Ustadz Ahmad Fauzi',
            'email' => 'guru.tahsin@asy-syams.test',
            'role' => 'guru',
            'is_active' => true,
            'phone' => '081200000002',
            'gender' => 'L',
            'address' => 'Komplek Pondok Asy-Syams',
        ]);

        $guruTahfidz = $this->user([
            'name' => 'Ustadzah Siti Aminah',
            'email' => 'guru.tahfidz@asy-syams.test',
            'role' => 'guru',
            'is_active' => true,
            'phone' => '081200000003',
            'gender' => 'P',
            'address' => 'Komplek Pondok Asy-Syams',
        ]);

        $students = collect([
            [
                'name' => 'Ahmad Zaidan',
                'email' => 'siswa.ahmad@asy-syams.test',
                'nisn' => '1000000001',
                'gender' => 'L',
                'grade_level' => 'SMPIT',
                'birth_date' => '2011-03-12',
                'mother_name' => 'Nur Hasanah',
                'school_origin' => 'SDIT Al-Falah',
                'phone' => '081300000001',
                'address' => 'Jl. Melati No. 10',
            ],
            [
                'name' => 'Fatimah Az-Zahra',
                'email' => 'siswa.fatimah@asy-syams.test',
                'nisn' => '1000000002',
                'gender' => 'P',
                'grade_level' => 'SMPIT',
                'birth_date' => '2011-07-21',
                'mother_name' => 'Aisyah Rahma',
                'school_origin' => 'SD Negeri 04',
                'phone' => '081300000002',
                'address' => 'Jl. Kenanga No. 7',
            ],
            [
                'name' => 'Muhammad Ilyas',
                'email' => 'siswa.ilyas@asy-syams.test',
                'nisn' => '1000000003',
                'gender' => 'L',
                'grade_level' => 'SMAIT',
                'birth_date' => '2008-11-02',
                'mother_name' => 'Maryam Putri',
                'school_origin' => 'SMPIT Asy-Syams',
                'phone' => '081300000003',
                'address' => 'Jl. Cendana No. 3',
            ],
            [
                'name' => 'Khadijah Nuraini',
                'email' => 'siswa.khadijah@asy-syams.test',
                'nisn' => '1000000004',
                'gender' => 'P',
                'grade_level' => 'SDIT',
                'birth_date' => '2015-01-18',
                'mother_name' => 'Halimah Saadiyah',
                'school_origin' => 'TK Qurani Asy-Syams',
                'phone' => '081300000004',
                'address' => 'Jl. Anggrek No. 12',
            ],
        ])->map(fn (array $data) => $this->user([
            ...$data,
            'role' => 'student',
            'is_active' => true,
        ]));

        $candidate = $this->user([
            'name' => 'Calon Santri Fathan',
            'email' => 'calon.fathan@asy-syams.test',
            'role' => 'student',
            'is_active' => false,
            'nisn' => '1000000099',
            'gender' => 'L',
            'grade_level' => 'SMPIT',
            'birth_date' => '2012-05-05',
            'mother_name' => 'Ruqayyah',
            'school_origin' => 'SD Negeri 02',
            'phone' => '081399999999',
            'address' => 'Jl. Pendaftar Baru No. 1',
        ]);

        $semesterAktif = Semester::updateOrCreate(
            ['name' => 'Ganjil 2026/2027'],
            [
                'start_date' => '2026-07-01',
                'end_date' => '2026-12-20',
                'is_active' => true,
                'tuition_fee' => 750000,
            ],
        );

        Semester::whereKeyNot($semesterAktif->id)->update(['is_active' => false]);

        $semesterLalu = Semester::updateOrCreate(
            ['name' => 'Genap 2025/2026'],
            [
                'start_date' => '2026-01-01',
                'end_date' => '2026-06-20',
                'is_active' => false,
                'tuition_fee' => 700000,
            ],
        );

        $subjects = collect([
            ['name' => 'Tahsin', 'slug' => 'tahsin'],
            ['name' => 'Tahfidz', 'slug' => 'tahfidz'],
            ['name' => 'Tajwid', 'slug' => 'tajwid'],
            ['name' => 'Baca & Tulis', 'slug' => 'baca-tulis'],
        ])->mapWithKeys(fn (array $data) => [
            $data['slug'] => Subject::updateOrCreate(['slug' => $data['slug']], $data),
        ]);

        $kelasTahsin = $this->classGroup('Tahsin A', $subjects['tahsin'], $semesterAktif, $guruTahsin);
        $kelasTahfidz = $this->classGroup('Tahfidz A', $subjects['tahfidz'], $semesterAktif, $guruTahfidz);
        $kelasTajwid = $this->classGroup('Tajwid Lanjutan', $subjects['tajwid'], $semesterLalu, $guruTahsin);

        $kelasTahsin->students()->syncWithoutDetaching($students->take(3)->mapWithKeys(fn (User $student) => [
            $student->id => ['joined_at' => now()],
        ])->all());

        $kelasTahfidz->students()->syncWithoutDetaching($students->slice(1, 3)->mapWithKeys(fn (User $student) => [
            $student->id => ['joined_at' => now()],
        ])->all());

        $kelasTajwid->students()->syncWithoutDetaching($students->mapWithKeys(fn (User $student) => [
            $student->id => ['joined_at' => now()->subMonths(4)],
        ])->all());

        $meetings = collect([
            $this->meeting($kelasTahsin, $guruTahsin, 'Makharijul Huruf dan Sifat Huruf', '2026-07-08'),
            $this->meeting($kelasTahsin, $guruTahsin, "Latihan Mad Thabi'i dan Mad Wajib", '2026-07-15'),
            $this->meeting($kelasTahfidz, $guruTahfidz, 'Setoran Hafalan Juz 30', '2026-07-09'),
            $this->meeting($kelasTahfidz, $guruTahfidz, 'Murojaah Surat An-Naba', '2026-07-16'),
        ]);

        $statuses = ['present', 'present', 'sick', 'permission', 'alpha'];
        foreach ($meetings as $meeting) {
            foreach ($meeting->classGroup->students as $index => $student) {
                $meeting->attendances()->updateOrCreate(
                    ['user_id' => $student->id],
                    ['status' => $statuses[$index % count($statuses)]],
                );
            }
        }

        foreach ([$kelasTahsin, $kelasTahfidz] as $classGroup) {
            foreach ($classGroup->students as $student) {
                foreach (['ziyadah', 'murojaah', 'tahsin'] as $type) {
                    $payload = [
                        'class_group_id' => $classGroup->id,
                        'user_id' => $student->id,
                        'assessment_type' => $type,
                    ];

                    if (Schema::hasColumn('assessments', 'month')) {
                        $payload['month'] = 7;
                    }

                    if (Schema::hasColumn('assessments', 'year')) {
                        $payload['year'] = 2026;
                    }

                    Assessment::updateOrCreate(
                        [
                            'class_group_id' => $classGroup->id,
                            'user_id' => $student->id,
                            'assessment_type' => $type,
                        ],
                        [
                            ...$payload,
                            'data' => $this->assessmentItems($student, $type),
                        ],
                    );
                }

                foreach ($this->evaluationItems() as $evaluationNumber => $items) {
                    Evaluation::updateOrCreate(
                        [
                            'class_group_id' => $classGroup->id,
                            'user_id' => $student->id,
                            'evaluation_number' => $evaluationNumber,
                        ],
                        [
                            'items' => $items,
                        ],
                    );
                }

                Grade::updateOrCreate(
                    [
                        'user_id' => $student->id,
                        'subject_id' => $classGroup->subject_id,
                        'semester_id' => $classGroup->semester_id,
                    ],
                    [
                        'score' => 86,
                        'notes' => 'Perkembangan baik. Perlu menjaga konsistensi murojaah harian.',
                    ],
                );
            }
        }

        foreach ($students as $index => $student) {
            Payment::updateOrCreate(
                [
                    'user_id' => $student->id,
                    'semester_id' => $semesterAktif->id,
                ],
                [
                    'order_id' => 'MOCK-SPP-' . $semesterAktif->id . '-' . $student->id,
                    'amount' => $semesterAktif->tuition_fee,
                    'status' => $index < 2 ? 'paid' : 'pending',
                    'payment_type' => $index < 2 ? 'bank_transfer' : null,
                    'payment_detail' => $index < 2 ? [
                        'source' => 'mockup',
                        'transaction_status' => 'settlement',
                    ] : null,
                ],
            );
        }

        $this->siteSettings();
        $this->posts($superadmin, $guruTahsin, $guruTahfidz);

        $this->command?->info('Mockup data berhasil dibuat.');
        $this->command?->line('Akun demo password: Password123!');
        $this->command?->line('superadmin@asy-syams.test, guru.tahsin@asy-syams.test, guru.tahfidz@asy-syams.test, siswa.ahmad@asy-syams.test');
    }

    private function user(array $data): User
    {
        return User::updateOrCreate(
            ['email' => $data['email']],
            [
                ...$data,
                'password' => Hash::make('Password123!'),
                'email_verified_at' => now(),
            ],
        );
    }

    private function classGroup(string $name, Subject $subject, Semester $semester, User $teacher): ClassGroup
    {
        return ClassGroup::updateOrCreate(
            ['slug' => Str::slug($name . '-' . $semester->name)],
            [
                'name' => $name,
                'subject_id' => $subject->id,
                'semester_id' => $semester->id,
                'teacher_id' => $teacher->id,
                'description' => 'Data kelas mockup untuk demo operasional akademik Asy-Syams.',
            ],
        );
    }

    private function meeting(ClassGroup $classGroup, User $teacher, string $title, string $date): Meeting
    {
        return Meeting::updateOrCreate(
            [
                'class_group_id' => $classGroup->id,
                'title' => $title,
                'date' => $date,
            ],
            [
                'user_id' => $teacher->id,
            ],
        );
    }

    private function assessmentItems(User $student, string $type): array
    {
        return match ($type) {
            'ziyadah' => [
                [
                    'nama' => $student->name,
                    'nilai_penyetoran' => 88,
                    'surah' => 'Al-Fatihah',
                    'ayat' => '1-7',
                    'nilai' => 'L',
                    'catatan' => 'Setoran lancar. Panjang pendek bacaan sudah stabil.',
                ],
                [
                    'nama' => $student->name,
                    'nilai_penyetoran' => 80,
                    'surah' => 'Al-Ikhlas',
                    'ayat' => '1-4',
                    'nilai' => 'C',
                    'catatan' => 'Perlu mengulang ayat terakhir agar lebih mantap.',
                ],
            ],
            'murojaah' => [
                [
                    'nama' => $student->name,
                    'nilai_penyetoran' => 84,
                    'surah' => 'An-Naba',
                    'ayat' => '1-10',
                    'nilai' => 'L',
                    'catatan' => 'Murojaah baik, hafalan masih terjaga.',
                ],
                [
                    'nama' => $student->name,
                    'nilai_penyetoran' => 72,
                    'surah' => 'An-Naziat',
                    'ayat' => '1-8',
                    'nilai' => 'C',
                    'catatan' => 'Masih perlu murojaah mandiri sebelum masuk kelas.',
                ],
            ],
            default => [
                [
                    'nama' => $student->name,
                    'nilai_penyetoran' => 86,
                    'surah' => 'Al-Baqarah',
                    'ayat' => '1-5',
                    'nilai' => 'L',
                    'catatan' => 'Tahsin membaik. Fokus berikutnya pada makharijul huruf.',
                ],
                [
                    'nama' => $student->name,
                    'nilai_penyetoran' => 76,
                    'surah' => 'Al-Mulk',
                    'ayat' => '1-6',
                    'nilai' => 'C',
                    'catatan' => 'Perhatikan hukum mad dan dengung.',
                ],
            ],
        };
    }

    private function evaluationItems(): array
    {
        return [
            1 => [
                ['name' => 'Kelancaran bacaan', 'checked' => true, 'score' => 88],
                ['name' => 'Ketepatan tajwid', 'checked' => true, 'score' => 84],
                ['name' => 'Adab saat setoran', 'checked' => true, 'score' => 92],
            ],
            2 => [
                ['name' => 'Konsistensi murojaah', 'checked' => true, 'score' => 82],
                ['name' => 'Kerapian hafalan baru', 'checked' => false, 'score' => 74],
                ['name' => 'Kepercayaan diri membaca', 'checked' => true, 'score' => 86],
            ],
        ];
    }

    private function siteSettings(): void
    {
        $settings = [
            'hero_title' => 'Membangun Generasi Qurani Berakhlak Mulia',
            'hero_subtitle' => 'YPTQ Asy-Syams membina santri melalui tilawah, tahsin, tahfidz, adab, dan pembelajaran terarah.',
            'spmb_deadline' => '2026-07-31 23:59:00',
            'contact_address' => 'Jl. Pendidikan Qurani No. 23, Indonesia',
            'contact_phone' => '0812-0000-2323',
            'contact_email' => 'info@asy-syams.test',
            'teacher_attendance_late_after' => '08:00',
        ];

        foreach ($settings as $key => $value) {
            SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    private function posts(User $superadmin, User $guruTahsin, User $guruTahfidz): void
    {
        $posts = [
            [
                'user_id' => $superadmin->id,
                'title' => 'SPMB Asy-Syams Tahun Ajaran 2026/2027 Dibuka',
                'category' => 'Pendidikan',
                'content' => '<p>Pendaftaran santri baru tahun ajaran 2026/2027 telah dibuka. Calon santri dapat mengisi formulir online dan menunggu proses verifikasi dari admin.</p>',
                'published_at' => now()->subDays(8),
            ],
            [
                'user_id' => $guruTahsin->id,
                'title' => 'Program Tahsin Intensif Untuk Santri Baru',
                'category' => 'Dakwah',
                'content' => '<p>Program tahsin intensif membantu santri memperbaiki makharijul huruf, sifat huruf, dan kelancaran membaca Al-Quran.</p>',
                'published_at' => now()->subDays(5),
            ],
            [
                'user_id' => $guruTahfidz->id,
                'title' => 'Murojaah Pekanan Kelas Tahfidz',
                'category' => 'Prestasi',
                'content' => '<p>Kegiatan murojaah pekanan menjadi sarana menjaga hafalan dan melatih kedisiplinan santri dalam menyetorkan hafalan.</p>',
                'published_at' => now()->subDays(2),
            ],
        ];

        foreach ($posts as $post) {
            Post::updateOrCreate(
                ['slug' => Str::slug($post['title'])],
                [
                    ...$post,
                    'slug' => Str::slug($post['title']),
                    'image_caption' => 'Dokumentasi kegiatan Asy-Syams',
                    'is_published' => true,
                    'views' => 25,
                ],
            );
        }
    }
}
