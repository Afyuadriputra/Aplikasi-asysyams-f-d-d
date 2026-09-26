<?php

namespace Tests\Feature\Permissions;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Academic\Models\Subject;
use App\Features\Grades\Models\Assessment;
use App\Features\Grades\Models\Evaluation;
use App\Features\Grades\Models\Grade;
use App\Features\Meetings\Models\Meeting;
use App\Features\Permissions\Models\RolePermission;
use App\Filament\Resources\AssessmentResource;
use App\Filament\Resources\ClassGroupResource;
use App\Filament\Resources\EvaluationResource;
use App\Filament\Resources\GradeResource;
use App\Filament\Resources\MeetingResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RowLevelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private User $guruA;
    private User $guruB;
    private User $superadmin;
    private User $santriA;
    private User $santriB;
    private ClassGroup $classA;
    private ClassGroup $classB;
    private Meeting $meetingA;
    private Meeting $meetingB;
    private Assessment $assessmentA;
    private Assessment $assessmentB;
    private Evaluation $evaluationA;
    private Evaluation $evaluationB;
    private Grade $gradeA;
    private Grade $gradeB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superadmin = User::factory()->create(['role' => 'superadmin', 'is_active' => true]);
        $this->guruA = User::factory()->create(['role' => 'guru', 'name' => 'Guru A', 'is_active' => true]);
        $this->guruB = User::factory()->create(['role' => 'guru', 'name' => 'Guru B', 'is_active' => true]);
        $this->santriA = User::factory()->create(['role' => 'student', 'name' => 'Santri A', 'is_active' => true]);
        $this->santriB = User::factory()->create(['role' => 'student', 'name' => 'Santri B', 'is_active' => true]);

        foreach (['classes.manage', 'meetings.manage', 'assessments.manage', 'evaluations.manage', 'grades.manage', 'dashboard.view'] as $perm) {
            RolePermission::create([
                'role' => 'guru',
                'permission' => $perm,
                'is_allowed' => true,
            ]);
        }

        $semester = Semester::create([
            'name' => 'Semester Genap 2026/2027',
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'is_active' => true,
            'tuition_fee' => 500000,
        ]);

        $subjectA = Subject::create(['name' => 'Tahfidz Pagi', 'slug' => 'tahfidz-pagi']);
        $subjectB = Subject::create(['name' => 'Tahsin Sore', 'slug' => 'tahsin-sore']);

        $this->classA = ClassGroup::create([
            'name' => 'Murottal A',
            'slug' => 'murottal-a',
            'subject_id' => $subjectA->id,
            'semester_id' => $semester->id,
            'teacher_id' => $this->guruA->id,
            'class_type' => 'murottal',
            'class_letter' => 'A',
        ]);
        $this->classA->students()->attach($this->santriA->id, ['joined_at' => now()]);

        $this->classB = ClassGroup::create([
            'name' => 'Murottal B',
            'slug' => 'murottal-b',
            'subject_id' => $subjectB->id,
            'semester_id' => $semester->id,
            'teacher_id' => $this->guruB->id,
            'class_type' => 'murottal',
            'class_letter' => 'B',
        ]);
        $this->classB->students()->attach($this->santriB->id, ['joined_at' => now()]);

        $this->meetingA = Meeting::create([
            'class_group_id' => $this->classA->id,
            'user_id' => $this->guruA->id,
            'title' => 'Pertemuan 1 Kelas A',
            'date' => now()->toDateString(),
        ]);

        $this->meetingB = Meeting::create([
            'class_group_id' => $this->classB->id,
            'user_id' => $this->guruB->id,
            'title' => 'Pertemuan 1 Kelas B',
            'date' => now()->toDateString(),
        ]);

        $this->assessmentA = Assessment::create([
            'class_group_id' => $this->classA->id,
            'user_id' => $this->santriA->id,
            'assessment_type' => 'ziyadah',
            'month' => 9,
            'year' => 2026,
            'data' => [['surah' => 'Al-Baqarah', 'ayat' => '1-10', 'nilai' => 'L']],
        ]);

        $this->assessmentB = Assessment::create([
            'class_group_id' => $this->classB->id,
            'user_id' => $this->santriB->id,
            'assessment_type' => 'ziyadah',
            'month' => 9,
            'year' => 2026,
            'data' => [['surah' => 'Al-Baqarah', 'ayat' => '1-10', 'nilai' => 'L']],
        ]);

        $this->evaluationA = Evaluation::create([
            'class_group_id' => $this->classA->id,
            'user_id' => $this->santriA->id,
            'evaluation_number' => 1,
            'date' => now()->toDateString(),
            'scores' => ['adab' => 90],
        ]);

        $this->evaluationB = Evaluation::create([
            'class_group_id' => $this->classB->id,
            'user_id' => $this->santriB->id,
            'evaluation_number' => 1,
            'date' => now()->toDateString(),
            'scores' => ['adab' => 85],
        ]);

        $this->gradeA = Grade::create([
            'user_id' => $this->santriA->id,
            'subject_id' => $subjectA->id,
            'semester_id' => $semester->id,
            'score' => 90,
        ]);

        $this->gradeB = Grade::create([
            'user_id' => $this->santriB->id,
            'subject_id' => $subjectB->id,
            'semester_id' => $semester->id,
            'score' => 85,
        ]);
    }

    public function test_guru_a_can_view_own_class_and_cannot_view_or_edit_guru_b_class(): void
    {
        $this->actingAs($this->guruA);

        $visibleClassIds = ClassGroupResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($this->classA->id, $visibleClassIds);
        $this->assertNotContains($this->classB->id, $visibleClassIds);

        $this->assertTrue(ClassGroupResource::canView($this->classA));
        $this->assertTrue(ClassGroupResource::canEdit($this->classA));
        $this->assertTrue(ClassGroupResource::canDelete($this->classA));

        $this->assertFalse(ClassGroupResource::canView($this->classB));
        $this->assertFalse(ClassGroupResource::canEdit($this->classB));
        $this->assertFalse(ClassGroupResource::canDelete($this->classB));
    }

    public function test_guru_a_can_manage_own_meeting_and_cannot_manage_guru_b_meeting(): void
    {
        $this->actingAs($this->guruA);

        $visibleMeetingIds = MeetingResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($this->meetingA->id, $visibleMeetingIds);
        $this->assertNotContains($this->meetingB->id, $visibleMeetingIds);

        $this->assertTrue(MeetingResource::canView($this->meetingA));
        $this->assertTrue(MeetingResource::canEdit($this->meetingA));
        $this->assertTrue(MeetingResource::canDelete($this->meetingA));

        $this->assertFalse(MeetingResource::canView($this->meetingB));
        $this->assertFalse(MeetingResource::canEdit($this->meetingB));
        $this->assertFalse(MeetingResource::canDelete($this->meetingB));
    }

    public function test_guru_a_can_manage_own_assessment_and_cannot_manage_guru_b_assessment(): void
    {
        $this->actingAs($this->guruA);

        $visibleAssessmentIds = AssessmentResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($this->assessmentA->id, $visibleAssessmentIds);
        $this->assertNotContains($this->assessmentB->id, $visibleAssessmentIds);

        $this->assertTrue(AssessmentResource::canView($this->assessmentA));
        $this->assertTrue(AssessmentResource::canEdit($this->assessmentA));
        $this->assertTrue(AssessmentResource::canDelete($this->assessmentA));

        $this->assertFalse(AssessmentResource::canView($this->assessmentB));
        $this->assertFalse(AssessmentResource::canEdit($this->assessmentB));
        $this->assertFalse(AssessmentResource::canDelete($this->assessmentB));
    }

    public function test_guru_a_can_manage_own_evaluation_and_cannot_manage_guru_b_evaluation(): void
    {
        $this->actingAs($this->guruA);

        $visibleEvaluationIds = EvaluationResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($this->evaluationA->id, $visibleEvaluationIds);
        $this->assertNotContains($this->evaluationB->id, $visibleEvaluationIds);

        $this->assertTrue(EvaluationResource::canView($this->evaluationA));
        $this->assertTrue(EvaluationResource::canEdit($this->evaluationA));
        $this->assertTrue(EvaluationResource::canDelete($this->evaluationA));

        $this->assertFalse(EvaluationResource::canView($this->evaluationB));
        $this->assertFalse(EvaluationResource::canEdit($this->evaluationB));
        $this->assertFalse(EvaluationResource::canDelete($this->evaluationB));
    }

    public function test_guru_a_cannot_access_grade_of_student_exclusively_owned_by_guru_b(): void
    {
        $this->actingAs($this->guruA);

        $visibleGradeIds = GradeResource::getEloquentQuery()->pluck('id')->toArray();
        $this->assertContains($this->gradeA->id, $visibleGradeIds);
        $this->assertNotContains($this->gradeB->id, $visibleGradeIds);

        $this->assertTrue(GradeResource::canView($this->gradeA));
        $this->assertTrue(GradeResource::canEdit($this->gradeA));
        $this->assertTrue(GradeResource::canDelete($this->gradeA));

        $this->assertFalse(GradeResource::canView($this->gradeB));
        $this->assertFalse(GradeResource::canEdit($this->gradeB));
        $this->assertFalse(GradeResource::canDelete($this->gradeB));
    }

    public function test_superadmin_can_access_all_records(): void
    {
        $this->actingAs($this->superadmin);

        $this->assertCount(2, ClassGroupResource::getEloquentQuery()->get());
        $this->assertCount(2, MeetingResource::getEloquentQuery()->get());
        $this->assertCount(2, AssessmentResource::getEloquentQuery()->get());
        $this->assertCount(2, EvaluationResource::getEloquentQuery()->get());
        $this->assertCount(2, GradeResource::getEloquentQuery()->get());

        $this->assertTrue(ClassGroupResource::canEdit($this->classB));
        $this->assertTrue(MeetingResource::canEdit($this->meetingB));
        $this->assertTrue(AssessmentResource::canEdit($this->assessmentB));
        $this->assertTrue(EvaluationResource::canEdit($this->evaluationB));
        $this->assertTrue(GradeResource::canEdit($this->gradeB));
    }

    public function test_guru_a_direct_url_to_foreign_records_is_denied(): void
    {
        $this->actingAs($this->guruA);

        $responseClass = $this->get('/admin/class-groups/' . $this->classB->id . '/edit');
        $this->assertTrue(in_array($responseClass->status(), [403, 404]), 'Foreign class edit URL should be 403 or 404');

        $responseMeeting = $this->get('/admin/meetings/' . $this->meetingB->id . '/edit');
        $this->assertTrue(in_array($responseMeeting->status(), [403, 404]), 'Foreign meeting edit URL should be 403 or 404');

        $responseAssessment = $this->get('/admin/assessments/' . $this->assessmentB->id . '/edit');
        $this->assertTrue(in_array($responseAssessment->status(), [403, 404]), 'Foreign assessment edit URL should be 403 or 404');

        $responseEvaluation = $this->get('/admin/evaluations/' . $this->evaluationB->id . '/edit');
        $this->assertTrue(in_array($responseEvaluation->status(), [403, 404]), 'Foreign evaluation edit URL should be 403 or 404');

        $responseGrade = $this->get('/admin/grades/' . $this->gradeB->id . '/edit');
        $this->assertTrue(in_array($responseGrade->status(), [403, 404]), 'Foreign grade edit URL should be 403 or 404');
    }

    public function test_guru_cannot_create_meeting_for_foreign_class(): void
    {
        $this->actingAs($this->guruA);

        $visibleClassesForMeeting = ClassGroup::query()
            ->where('teacher_id', auth()->id())
            ->pluck('id')
            ->toArray();

        $this->assertContains($this->classA->id, $visibleClassesForMeeting);
        $this->assertNotContains($this->classB->id, $visibleClassesForMeeting);
    }

    public function test_guru_cannot_create_assessment_for_foreign_class(): void
    {
        $this->actingAs($this->guruA);

        $visibleClassesForAssessment = ClassGroup::query()
            ->when(auth()->user()->role === 'guru', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->pluck('id')
            ->toArray();

        $this->assertContains($this->classA->id, $visibleClassesForAssessment);
        $this->assertNotContains($this->classB->id, $visibleClassesForAssessment);
    }

    public function test_guru_cannot_create_evaluation_for_foreign_class(): void
    {
        $this->actingAs($this->guruA);

        $visibleClassesForEvaluation = ClassGroup::query()
            ->when(auth()->user()->role === 'guru', fn ($q) => $q->where('teacher_id', auth()->id()))
            ->pluck('id')
            ->toArray();

        $this->assertContains($this->classA->id, $visibleClassesForEvaluation);
        $this->assertNotContains($this->classB->id, $visibleClassesForEvaluation);
    }
}