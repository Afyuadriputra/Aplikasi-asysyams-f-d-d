<?php

namespace Tests\Feature\Reports;

use App\Models\User;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Academic\Models\ClassGroup;
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
}
