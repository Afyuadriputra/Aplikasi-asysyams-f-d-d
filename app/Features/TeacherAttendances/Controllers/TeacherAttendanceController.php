<?php

namespace App\Features\TeacherAttendances\Controllers;

use App\Features\TeacherAttendances\Services\TeacherAttendanceService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TeacherAttendanceController extends Controller
{
    public function checkIn(Request $request, TeacherAttendanceService $service): RedirectResponse
    {
        $service->checkIn($request->user());

        return back()->with('status', 'Check-in berhasil disimpan.');
    }

    public function checkOut(Request $request, TeacherAttendanceService $service): RedirectResponse
    {
        $service->checkOut($request->user());

        return back()->with('status', 'Check-out berhasil disimpan.');
    }
}
