<?php

namespace App\Features\Students\Controllers;

use App\Features\Meetings\Models\Attendance;
use App\Features\Grades\Models\Grade;
use App\Features\Payments\Models\Payment;
use App\Features\Academic\Models\Semester;
use App\Features\Meetings\Models\Meeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class StudentController extends Controller
{
    /**
     * Halaman Utama Dashboard Santri
     * Menampilkan Status SPP & Ringkasan
     */
    public function dashboard()
    {
        $user = Auth::user();

        // --- LOGIC KHUSUS GURU ---
        if ($user->role === 'guru' || $user->role === 'superadmin') {
            // Ambil data ringkasan untuk Ustad
            $totalMeetings = Meeting::where('user_id', $user->id)->count();
            
            // Jadwal Hari Ini
            $todayClasses = Meeting::with('classGroup.subject')
                ->where('user_id', $user->id)
                ->whereDate('date', now())
                ->get();

            // Return view yang sama, tapi datanya beda
            return view('pages.student.dashboard', compact('user', 'totalMeetings', 'todayClasses'));
        }

        // --- LOGIC KHUSUS SISWA (KODE LAMA) ---
        $activeSemester = Semester::where('is_active', true)->first();
        $paymentStatus = 'no_bill';
        $billAmount = 0;
        
        if ($activeSemester) {
            $billAmount = $activeSemester->tuition_fee;
            $payment = Payment::where('user_id', $user->id)
                ->where('semester_id', $activeSemester->id)
                ->latest()
                ->first();  
            if ($payment) {
                $paymentStatus = $payment->status;
            } else {
                $paymentStatus = 'unpaid';
            }
        }

        $presentCount = Attendance::where('user_id', $user->id)->where('status', 'present')->count();
        $averageScore = Grade::where('user_id', $user->id)->avg('score') ?? 0;

        return view('pages.student.dashboard', compact(
            'user', 
            'activeSemester', 
            'paymentStatus', 
            'billAmount',
            'presentCount',
            'averageScore'
        ));
    }

    /**
     * Halaman Transkrip Nilai
     */
    public function transcript()
    {
        $user = Auth::user();
        
        // Ambil nilai, dikelompokkan per Semester
        // Kita eager load 'subject' dan 'semester' biar query ringan
        $gradesGrouped = Grade::with(['subject', 'semester'])
            ->where('user_id', $user->id)
            ->get()
            ->groupBy('semester.name'); // Hasil: ['Ganjil 2024' => [Nilai A, Nilai B], ...]

        return view('pages.student.transcript', compact('user', 'gradesGrouped'));
    }

    /**
     * Halaman Riwayat Absensi
     */
    public function attendance()
    {
        $user = Auth::user();

        // Ambil data absensi beserta detail pertemuannya
        $attendances = Attendance::with(['meeting.classGroup.subject'])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10); // Pakai pagination biar ga kepanjangan

        // Hitung Persentase Kehadiran
        $totalMeeting = Attendance::where('user_id', $user->id)->count();
        $totalPresent = Attendance::where('user_id', $user->id)->where('status', 'present')->count();
        
        $percentage = $totalMeeting > 0 ? round(($totalPresent / $totalMeeting) * 100) : 0;

        return view('pages.student.attendance', compact('user', 'attendances', 'percentage'));
    }

    // public function transcript() { return view('pages.student.transcript', ['user' => Auth::user(), 'gradesGrouped' => []]); } 
    // public function attendance() { return view('pages.student.attendance', ['user' => Auth::user(), 'attendances' => [], 'percentage' => 0]); }
}
