<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Academic\Models\ClassGroup;
use App\Features\Grades\Models\Grade;
use App\Features\Permissions\Models\RolePermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Barryvdh\DomPDF\Facade\Pdf;
use Mockery;

class ReportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_rapor_pdf_route_is_available_and_restricted()
    {
        $student = User::factory()->create(['role' => 'student']);
        $guru = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        // No permission yet
        $this->actingAs($guru);
        $response = $this->get(route('rapor.pdf', ['class_group' => $classGroup->id, 'user' => $student->id]));
        $response->assertRedirect('/access-denied');

        // Grant permission
        RolePermission::create([
            'role' => 'guru',
            'permission' => 'reports.download',
            'is_allowed' => true
        ]);

        // Mock PDF
        $pdfMock = Mockery::mock('Barryvdh\DomPDF\PDF');
        $pdfMock->shouldReceive('stream')->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($pdfMock);

        $response = $this->get(route('rapor.pdf', ['class_group' => $classGroup->id, 'user' => $student->id]));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rapor_pdf_does_not_fatal_error_when_data_is_empty()
    {
        $student = User::factory()->create(['role' => 'student']);
        $admin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        // Mock PDF
        $pdfMock = Mockery::mock('Barryvdh\DomPDF\PDF');
        $pdfMock->shouldReceive('stream')->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));

        Pdf::shouldReceive('loadView')
            ->once()
            ->andReturn($pdfMock);

        $this->actingAs($admin);
        $response = $this->get(route('rapor.pdf', ['class_group' => $classGroup->id, 'user' => $student->id]));
        
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_rapor_pdf_uses_feature_class_group_binding_and_selected_student_data()
    {
        $student = User::factory()->create(['role' => 'student', 'name' => 'Selected Student']);
        $otherStudent = User::factory()->create(['role' => 'student', 'name' => 'Other Student']);
        $admin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        Grade::create(['user_id' => $student->id, 'semester_id' => $semester->id, 'subject_id' => $subject->id, 'score' => 88]);
        Grade::create(['user_id' => $otherStudent->id, 'semester_id' => $semester->id, 'subject_id' => $subject->id, 'score' => 11]);

        $pdfMock = Mockery::mock('Barryvdh\DomPDF\PDF');
        $pdfMock->shouldReceive('stream')->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
        $capturedData = [];

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) use (&$capturedData): bool {
                $capturedData = $data;

                return $view === 'filament.report.rapor-pdf';
            })
            ->andReturn($pdfMock);

        $this->actingAs($admin);
        $response = $this->get(route('rapor.pdf', ['class_group' => $classGroup->id, 'user' => $student->id]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertTrue($capturedData['student']->is($student));
        $this->assertTrue($capturedData['classGroup']->is($classGroup));
        $this->assertInstanceOf(ClassGroup::class, $capturedData['classGroup']);
        $this->assertSame(88.0, (float) $capturedData['student']->grades()->where('semester_id', $classGroup->semester_id)->value('score'));
    }

    public function test_student_without_report_download_permission_cannot_open_other_student_report()
    {
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $otherStudent = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create(['name' => 'S1', 'year' => '2024', 'start_date' => now(), 'end_date' => now(), 'is_active' => true]);
        $subject = Subject::create(['name' => 'Sub1', 'slug' => 'sub1']);
        $classGroup = ClassGroup::create(['name' => 'C', 'slug' => 'c', 'subject_id' => $subject->id, 'semester_id' => $semester->id]);

        $this->actingAs($student);

        $response = $this->get(route('rapor.pdf', ['class_group' => $classGroup->id, 'user' => $otherStudent->id]));

        $response->assertRedirect(route('dashboard', absolute: false));
    }
}
