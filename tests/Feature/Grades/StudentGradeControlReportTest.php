<?php

namespace Tests\Feature\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Features\Meetings\Models\Meeting;
use App\Features\Permissions\Models\RolePermission;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StudentGradeControlReportTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_superadmin_can_view_student_control_table(): void
    {
        [$student, , $grade] = $this->makeReportData();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $response = $this
            ->actingAs($superadmin)
            ->get(route('admin.grades.student-control', [
                'user' => $student->id,
                'grade' => $grade->id,
            ]));

        $response->assertOk();
        $response->assertSee('RUMAH QUR', false);
        $response->assertSee($student->name);
        $response->assertSee('ZIYADAH');
        $response->assertSee('Al-Fatihah : 1-7');
        $response->assertSee('CATATAN PEMBELAJARAN');
        $response->assertSee('Bacaan sudah lancar, jaga konsistensi tempo.');
        $response->assertSeeInOrder(['EVALUASI', 'REKAP ABSENSI']);
        $response->assertSee('Kelancaran bacaan');
        $response->assertSee('Persentase');
    }

    public function test_guru_can_download_pdf_for_student_in_their_class(): void
    {
        [$student, , $grade, $teacher] = $this->makeReportData();
        RolePermission::create(['role' => 'guru', 'permission' => 'grades.view', 'is_allowed' => true]);

        $pdfMock = Mockery::mock('Barryvdh\DomPDF\PDF');
        $pdfMock->shouldReceive('setPaper')->once()->with('a4', 'portrait')->andReturnSelf();
        $pdfMock->shouldReceive('download')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));

        $capturedData = [];
        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use (&$capturedData, $student): bool {
                $capturedData = $data;

                return $view === 'pdf.student-grade-control'
                    && $data['student']->is($student)
                    && $data['rows']->isNotEmpty();
            })
            ->andReturn($pdfMock);

        $response = $this
            ->actingAs($teacher)
            ->get(route('admin.grades.student-control.pdf', [
                'user' => $student->id,
                'grade' => $grade->id,
            ]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame('Tahsin A', $capturedData['classGroup']->name);
    }

    public function test_guru_cannot_access_student_control_table_outside_their_class(): void
    {
        [$student, , $grade] = $this->makeReportData();
        $otherTeacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        RolePermission::create(['role' => 'guru', 'permission' => 'grades.view', 'is_allowed' => true]);

        $response = $this
            ->actingAs($otherTeacher)
            ->get(route('admin.grades.student-control', [
                'user' => $student->id,
                'grade' => $grade->id,
            ]));

        $this->assertTrue(
            $response->isForbidden() || $response->isRedirect(),
            'Guru outside the class must not receive a successful response.'
        );
    }

    public function test_student_cannot_access_admin_grade_control_pdf(): void
    {
        [$student, , $grade] = $this->makeReportData();
        RolePermission::create(['role' => 'student', 'permission' => 'grades.view', 'is_allowed' => true]);

        $response = $this
            ->actingAs($student)
            ->get(route('admin.grades.student-control.pdf', [
                'user' => $student->id,
                'grade' => $grade->id,
            ]));

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_grade_query_parameter_must_belong_to_selected_student(): void
    {
        [$student] = $this->makeReportData();
        [$otherStudent, , $otherGrade] = $this->makeReportData('other');
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $response = $this
            ->actingAs($superadmin)
            ->get(route('admin.grades.student-control', [
                'user' => $student->id,
                'grade' => $otherGrade->id,
            ]));

        $response->assertNotFound();
        $this->assertFalse($otherStudent->is($student));
    }

    private function makeReportData(string $suffix = 'main'): array
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $student = User::factory()->create([
            'name' => 'Fatimah Az-Zahra ' . $suffix,
            'role' => 'student',
            'is_active' => true,
        ]);
        $semester = Semester::create([
            'name' => 'Ganjil 2026/2027 ' . $suffix,
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 750000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin ' . $suffix, 'slug' => 'tahsin-' . $suffix]);
        $classGroup = ClassGroup::create([
            'name' => 'Tahsin A',
            'slug' => 'tahsin-a-' . $suffix,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
        ]);

        $classGroup->students()->attach($student->id, ['joined_at' => now()]);

        $grade = Grade::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 90,
        ]);

        foreach (['ziyadah', 'murojaah', 'tahsin'] as $type) {
            Assessment::create([
                'user_id' => $student->id,
                'class_group_id' => $classGroup->id,
                'assessment_type' => $type,
                'data' => [
                    [
                        'surah' => 'Al-Fatihah',
                        'ayat' => '1-7',
                        'nilai' => 'L',
                        'catatan' => $type === 'ziyadah' ? 'Bacaan sudah lancar, jaga konsistensi tempo.' : null,
                    ],
                ],
            ]);
        }

        Evaluation::create([
            'user_id' => $student->id,
            'class_group_id' => $classGroup->id,
            'evaluation_number' => 1,
            'items' => [
                ['name' => 'Kelancaran bacaan', 'checked' => true, 'score' => 88],
            ],
        ]);

        $meeting = Meeting::create([
            'class_group_id' => $classGroup->id,
            'user_id' => $teacher->id,
            'title' => 'Pertemuan ' . $suffix,
            'date' => now(),
        ]);

        foreach (['present', 'sick', 'permission', 'alpha'] as $status) {
            $meeting->attendances()->create([
                'user_id' => $student->id,
                'status' => $status,
            ]);
        }

        return [$student, $classGroup, $grade, $teacher];
    }
}
