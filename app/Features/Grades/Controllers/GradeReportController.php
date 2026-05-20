<?php

namespace App\Features\Grades\Controllers;

use App\Features\Grades\Models\Grade;
use App\Features\Grades\Services\GradeReportService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class GradeReportController extends Controller
{
    public function show(Request $request, User $user, GradeReportService $service): Response
    {
        $grade = $this->resolveGrade($request, $user);

        abort_unless(
            $service->canAccessStudentReport($request->user(), $user, $grade),
            403
        );

        return response()->view('grades.student-control-report', $service->buildStudentControlReport($user, $grade));
    }

    public function pdf(Request $request, User $user, GradeReportService $service): Response
    {
        $grade = $this->resolveGrade($request, $user);

        abort_unless(
            $service->canAccessStudentReport($request->user(), $user, $grade),
            403
        );

        $data = $service->buildStudentControlReport($user, $grade);
        $fileName = 'tabel-nilai-' . Str::slug($user->name) . '.pdf';

        return Pdf::loadView('pdf.student-grade-control', $data)
            ->setPaper('a4', 'portrait')
            ->download($fileName);
    }

    private function resolveGrade(Request $request, User $user): ?Grade
    {
        $gradeId = $request->integer('grade');

        if (! $gradeId) {
            return null;
        }

        return Grade::query()
            ->where('user_id', $user->id)
            ->findOrFail($gradeId);
    }
}
