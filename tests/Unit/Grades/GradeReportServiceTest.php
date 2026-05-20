<?php

namespace Tests\Unit\Grades;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Grade;
use App\Features\Grades\Services\GradeReportService;
use App\Features\Permissions\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GradeReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_student_control_report_maps_assessments_to_control_table_rows(): void
    {
        [$student, $classGroup, $grade] = $this->makeAcademicData();

        Assessment::create([
            'user_id' => $student->id,
            'class_group_id' => $classGroup->id,
            'assessment_type' => 'ziyadah',
            'data' => [
                ['surah' => 'Al-Fatihah', 'ayat' => '1-7', 'nilai' => 'L'],
                ['surah' => 'Al-Ikhlas', 'ayat' => '1-4', 'nilai' => 'C'],
            ],
        ]);

        Assessment::create([
            'user_id' => $student->id,
            'class_group_id' => $classGroup->id,
            'assessment_type' => 'murojaah',
            'data' => [
                ['surah' => 'An-Naba', 'ayat' => '1-10', 'nilai_penyetoran' => 82],
            ],
        ]);

        Assessment::create([
            'user_id' => $student->id,
            'class_group_id' => $classGroup->id,
            'assessment_type' => 'tahsin',
            'data' => [
                ['surah' => 'Al-Baqarah', 'ayat' => '1-5', 'nilai' => 'C'],
            ],
        ]);

        $report = app(GradeReportService::class)->buildStudentControlReport($student, $grade);
        $row = $report['rows']->first();

        $this->assertTrue($report['student']->is($student));
        $this->assertTrue($report['classGroup']->is($classGroup));
        $this->assertSame('Al-Fatihah : 1-7; Al-Ikhlas : 1-4', $row['ziyadah']);
        $this->assertSame('C', $row['ziyadah_score']);
        $this->assertSame('An-Naba : 1-10', $row['murojaah']);
        $this->assertSame('B', $row['murojaah_score']);
        $this->assertSame('Al-Baqarah : 1-5', $row['tahsin']);
    }

    public function test_guru_can_access_only_students_in_their_class(): void
    {
        [$student, $classGroup, $grade, $teacher] = $this->makeAcademicData();
        $otherTeacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        RolePermission::create(['role' => 'guru', 'permission' => 'grades.view', 'is_allowed' => true]);

        $service = app(GradeReportService::class);

        $this->assertTrue($service->canAccessStudentReport($teacher, $student, $grade));
        $this->assertFalse($service->canAccessStudentReport($otherTeacher, $student, $grade));
        $this->assertTrue($classGroup->students()->whereKey($student->id)->exists());
    }

    public function test_superadmin_can_access_any_student_report_and_student_cannot(): void
    {
        [$student, , $grade] = $this->makeAcademicData();
        $superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);

        $service = app(GradeReportService::class);

        $this->assertTrue($service->canAccessStudentReport($superadmin, $student, $grade));
        $this->assertFalse($service->canAccessStudentReport($student, $student, $grade));
    }

    private function makeAcademicData(): array
    {
        $teacher = User::factory()->create(['role' => 'guru', 'is_active' => true]);
        $student = User::factory()->create(['role' => 'student', 'is_active' => true]);
        $semester = Semester::create([
            'name' => 'Ganjil 2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 750000,
        ]);
        $subject = Subject::create(['name' => 'Tahsin', 'slug' => 'tahsin']);
        $classGroup = ClassGroup::create([
            'name' => 'Tahsin A',
            'slug' => 'tahsin-a',
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'teacher_id' => $teacher->id,
        ]);

        $classGroup->students()->attach($student->id, ['joined_at' => now()]);

        $grade = Grade::create([
            'user_id' => $student->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'score' => 86,
        ]);

        return [$student, $classGroup, $grade, $teacher];
    }
}
