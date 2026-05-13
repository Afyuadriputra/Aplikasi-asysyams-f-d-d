<?php

namespace App\Features\Reports\Controllers;

use App\Features\Academic\Models\ClassGroup;
use App\Features\Academic\Models\Semester;
use App\Features\Grades\Models\Assessment;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class AssessmentReportController extends Controller
{
    /**
     * Tampilkan rekapan penilaian bulanan
     */
    public function monthlyReport(Request $request)
    {
        $user = Auth::user();
        
        // Jika bukan guru/superadmin, redirect
        if (! $user->hasAccess('reports.view')) {
            return redirect()->route('access.denied');
        }

        // Default: bulan dan tahun saat ini
        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $classGroupId = $request->query('class_group_id');

        // Query assessments
        $query = Assessment::with(['student', 'classGroup'])
            ->where('month', $month)
            ->where('year', $year);

        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        } else {
            // Jika guru, hanya lihat kelas mereka
            if ($user->role === 'guru') {
                $query->whereHas('classGroup', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                });
            }
        }

        $assessments = $query->groupBy('assessment_type')
            ->get()
            ->groupBy('assessment_type');

        // Hitung statistik
        $stats = [];
        foreach ($assessments as $type => $items) {
            $total_l = $items->filter(fn($item) => $item->data && collect($item->data)->where('nilai', 'L')->count() > 0)->count();
            $total_c = $items->filter(fn($item) => $item->data && collect($item->data)->where('nilai', 'C')->count() > 0)->count();
            $total_tl = $items->filter(fn($item) => $item->data && collect($item->data)->where('nilai', 'TL')->count() > 0)->count();
            
            $stats[$type] = [
                'total' => $items->count(),
                'lancar' => $total_l,
                'cukup' => $total_c,
                'tidak_lancar' => $total_tl,
            ];
        }

        $classGroups = ClassGroup::all();

        return view('assessments.monthly-report', compact(
            'assessments',
            'stats',
            'month',
            'year',
            'classGroups',
            'classGroupId'
        ));
    }

    /**
     * Export rekapan bulanan ke PDF
     */
    public function exportMonthlyPDF(Request $request)
    {
        $user = Auth::user();
        
        if (! $user->hasAccess('reports.download')) {
            return redirect()->route('access.denied');
        }

        $month = $request->query('month', now()->month);
        $year = $request->query('year', now()->year);
        $classGroupId = $request->query('class_group_id');

        $query = Assessment::with(['student', 'classGroup'])
            ->where('month', $month)
            ->where('year', $year);

        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        } else {
            if ($user->role === 'guru') {
                $query->whereHas('classGroup', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                });
            }
        }

        $assessments = $query->get();
        $classGroup = $classGroupId ? ClassGroup::find($classGroupId) : null;
        $monthName = Carbon::create($year, $month)->translatedFormat('F');

        $data = compact('assessments', 'classGroup', 'month', 'year', 'monthName', 'user');

        $pdf = Pdf::loadView('assessments.monthly-report-pdf', $data);
        return $pdf->download("Rekapan-Penilaian-{$monthName}-{$year}.pdf");
    }

    /**
     * Tampilkan rapor semester
     */
    public function semesterReport(Request $request)
    {
        $user = Auth::user();
        
        if (! $user->hasAccess('reports.view')) {
            return redirect()->route('access.denied');
        }

        $semesterId = $request->query('semester_id');
        $classGroupId = $request->query('class_group_id');

        $semester = Semester::find($semesterId);

        if (!$semester) {
            return redirect()->back()->withErrors('Semester tidak ditemukan');
        }

        // Tentukan bulan untuk semester (1-6 = Semester 1, 7-12 = Semester 2)
        $semesterStart = $semester->is_active ? now()->month : 1; // Simplify logic
        $startMonth = $semester->name === 'Ganjil' ? 7 : 1;
        $endMonth = $semester->name === 'Ganjil' ? 12 : 6;

        // Query assessments dalam range bulan semester
        $query = Assessment::with(['student', 'classGroup'])
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where('year', now()->year);

        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        } else {
            if ($user->role === 'guru') {
                $query->whereHas('classGroup', function ($q) use ($user) {
                    $q->where('teacher_id', $user->id);
                });
            }
        }

        $assessments = $query->get();
        $classGroups = ClassGroup::all();
        $semesters = Semester::all();

        return view('assessments.semester-report', compact(
            'assessments',
            'semester',
            'semesters',
            'classGroups',
            'classGroupId'
        ));
    }

    /**
     * Export rapor semester ke PDF
     */
    public function exportSemesterPDF(Request $request)
    {
        $user = Auth::user();
        
        if (! $user->hasAccess('reports.download')) {
            return redirect()->route('access.denied');
        }

        $semesterId = $request->query('semester_id');
        $classGroupId = $request->query('class_group_id');

        $semester = Semester::find($semesterId);

        if (!$semester) {
            abort(404);
        }

        $startMonth = $semester->name === 'Ganjil' ? 7 : 1;
        $endMonth = $semester->name === 'Ganjil' ? 12 : 6;

        $query = Assessment::with(['student', 'classGroup'])
            ->whereBetween('month', [$startMonth, $endMonth])
            ->where('year', now()->year);

        if ($classGroupId) {
            $query->where('class_group_id', $classGroupId);
        }

        $assessments = $query->get();
        $classGroup = $classGroupId ? ClassGroup::find($classGroupId) : null;

        $data = compact('assessments', 'semester', 'classGroup', 'user');

        $pdf = Pdf::loadView('assessments.semester-report-pdf', $data);
        return $pdf->download("Rapor-{$semester->name}-{$classGroup?->name}.pdf");
    }
}
