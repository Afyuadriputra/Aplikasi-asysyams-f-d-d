@extends('root.app')

@section('title', 'Dashboard - YPTQ Asy-Syams')

@section('content')

{{-- ========================================== --}}
{{--              TAMPILAN USTAD                --}}
{{-- ========================================== --}}
@if(Auth::user()->role === 'guru' || Auth::user()->role === 'superadmin')
    
    <div class="bg-gray-50 min-h-screen pb-12">
        <!-- Header Ustad -->
        <div class="bg-green-800 pb-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <h1 class="text-3xl font-bold text-white">
                    Ahlan Wa Sahlan, Ustadz {{ Auth::user()->name }}
                </h1>
                <p class="text-green-200 mt-2">
                    Selamat beraktivitas! Kelola kegiatan belajar mengajar Anda di sini.
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16">
            
            <!-- Quick Actions Grid -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                
                <!-- Card 1: Shortcut ke Admin Panel -->
                <a href="{{ url('/admin') }}" target="_blank" class="bg-white rounded-lg shadow-lg p-6 hover:shadow-xl transition border-l-4 border-green-600 group">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-green-100 text-green-700 rounded-full group-hover:bg-green-600 group-hover:text-white transition">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Panel Admin</h3>
                            <p class="text-sm text-gray-500">Masuk ke menu lengkap</p>
                        </div>
                    </div>
                </a>

                <!-- Card 2: Total Pertemuan -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Total Mengajar</h3>
                            <p class="text-sm text-gray-500">{{ $totalMeetings ?? 0 }} Pertemuan</p>
                        </div>
                    </div>
                </div>

                <!-- Card 3: Rekap Absensi -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-amber-500">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-amber-100 text-amber-700 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6h6v6m2 4H7a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2 2 2h3a2 2 0 012 2v12a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Absensi</h3>
                            <p class="text-sm text-gray-500">{{ $attendanceSummary['total'] ?? 0 }} Data</p>
                        </div>
                    </div>
                </div>

                <!-- Card 4: Logout -->
                <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-red-500 cursor-pointer" onclick="document.getElementById('logout-form').submit()">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-red-100 text-red-600 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Keluar</h3>
                            <p class="text-sm text-gray-500">Logout Sistem</p>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
                        </div>
                    </div>
                </div>

            </div>

            @if(Auth::user()->role === 'guru')
                @php
                    $teacherStatusLabels = [
                        'present' => 'Hadir',
                        'late' => 'Terlambat',
                        'permission' => 'Izin',
                        'sick' => 'Sakit',
                        'alpha' => 'Alpha',
                    ];
                    $teacherStatusColors = [
                        'present' => 'bg-green-100 text-green-800',
                        'late' => 'bg-yellow-100 text-yellow-800',
                        'permission' => 'bg-blue-100 text-blue-800',
                        'sick' => 'bg-gray-100 text-gray-800',
                        'alpha' => 'bg-red-100 text-red-800',
                    ];
                    $teacherAttendanceStatus = $teacherAttendanceToday?->status;
                @endphp

                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800">Absensi Saya Hari Ini</h2>
                        <p class="text-sm text-gray-500">Check-in dan check-out hanya untuk akun guru yang sedang login.</p>
                    </div>

                    @if(session('status'))
                        <div class="mx-6 mt-4 rounded-lg bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if($errors->has('attendance'))
                        <div class="mx-6 mt-4 rounded-lg bg-red-50 px-4 py-3 text-sm font-medium text-red-800">
                            {{ $errors->first('attendance') }}
                        </div>
                    @endif

                    <div class="p-6 grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-1 border border-gray-200 rounded-lg p-5">
                            <div class="text-sm font-semibold text-gray-500 uppercase">Status Hari Ini</div>
                            <div class="mt-3">
                                @if($teacherAttendanceToday)
                                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-bold {{ $teacherStatusColors[$teacherAttendanceStatus] ?? 'bg-gray-100 text-gray-800' }}">
                                        {{ $teacherStatusLabels[$teacherAttendanceStatus] ?? 'Belum Absen' }}
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-bold bg-gray-100 text-gray-800">
                                        Belum Absen
                                    </span>
                                @endif
                            </div>

                            <div class="mt-4 text-sm text-gray-600 space-y-1">
                                <div>Check In: <span class="font-semibold">{{ $teacherAttendanceToday?->check_in_at?->format('H:i') ?? '-' }}</span></div>
                                <div>Check Out: <span class="font-semibold">{{ $teacherAttendanceToday?->check_out_at?->format('H:i') ?? '-' }}</span></div>
                            </div>

                            <div class="mt-5 flex flex-col sm:flex-row gap-3">
                                @if(! $teacherAttendanceToday)
                                    <form method="POST" action="{{ route('teacher-attendances.check-in') }}">
                                        @csrf
                                        <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-green-700 text-white font-semibold hover:bg-green-800">
                                            Check In
                                        </button>
                                    </form>
                                @elseif($teacherAttendanceToday->check_in_at && ! $teacherAttendanceToday->check_out_at && ! in_array($teacherAttendanceToday->status, ['permission', 'sick', 'alpha'], true))
                                    <form method="POST" action="{{ route('teacher-attendances.check-out') }}">
                                        @csrf
                                        <button type="submit" class="w-full sm:w-auto px-4 py-2 rounded-lg bg-blue-700 text-white font-semibold hover:bg-blue-800">
                                            Check Out
                                        </button>
                                    </form>
                                @else
                                    <div class="text-sm font-semibold text-gray-600">Absensi hari ini selesai.</div>
                                @endif
                            </div>
                        </div>

                        <div class="lg:col-span-2 overflow-x-auto border border-gray-200 rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Tanggal</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Status</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Check In</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Check Out</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-100">
                                    @forelse($teacherAttendanceRecent as $attendance)
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $attendance->date?->format('d M Y') }}</td>
                                            <td class="px-4 py-3 text-sm font-semibold">{{ $teacherStatusLabels[$attendance->status] ?? $attendance->status }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $attendance->check_out_at?->format('H:i') ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada riwayat absensi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            @if(Auth::user()->role === 'superadmin')
                <div class="bg-white rounded-xl shadow-lg overflow-hidden mb-8">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h2 class="text-lg font-bold text-gray-800">Rekap Absensi Ustad Hari Ini</h2>
                    </div>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-px bg-gray-200">
                        <div class="bg-white p-4 text-center"><div class="text-xs font-semibold text-gray-500 uppercase">Hadir</div><div class="mt-1 text-2xl font-bold text-green-700">{{ $teacherAttendanceSummary['present'] ?? 0 }}</div></div>
                        <div class="bg-white p-4 text-center"><div class="text-xs font-semibold text-gray-500 uppercase">Terlambat</div><div class="mt-1 text-2xl font-bold text-yellow-700">{{ $teacherAttendanceSummary['late'] ?? 0 }}</div></div>
                        <div class="bg-white p-4 text-center"><div class="text-xs font-semibold text-gray-500 uppercase">Izin/Sakit</div><div class="mt-1 text-2xl font-bold text-blue-700">{{ $teacherAttendanceSummary['permission_or_sick'] ?? 0 }}</div></div>
                        <div class="bg-white p-4 text-center"><div class="text-xs font-semibold text-gray-500 uppercase">Alpha</div><div class="mt-1 text-2xl font-bold text-red-700">{{ $teacherAttendanceSummary['alpha'] ?? 0 }}</div></div>
                        <div class="bg-white p-4 text-center col-span-2 md:col-span-1"><div class="text-xs font-semibold text-gray-500 uppercase">Belum Absen</div><div class="mt-1 text-2xl font-bold text-gray-900">{{ $teacherAttendanceSummary['not_checked_in'] ?? 0 }}</div></div>
                    </div>
                    <div class="overflow-x-auto border-t border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Nama Guru</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Status</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Check In</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Check Out</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-600 uppercase">Catatan</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @forelse($teacherAttendanceTodayRows as $attendance)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900">{{ $attendance->user?->name ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ \App\Features\TeacherAttendances\Models\TeacherAttendance::STATUSES[$attendance->status] ?? $attendance->status }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $attendance->check_in_at?->format('H:i') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $attendance->check_out_at?->format('H:i') ?? '-' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $attendance->note ?: '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">Belum ada data absensi ustad hari ini.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- JADWAL & ABSENSI -->
            <div class="bg-white rounded-xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-800">Jadwal & Absensi</h2>
                        <p class="text-sm text-gray-500">Data otomatis dari input pertemuan dan absensi di admin panel.</p>
                    </div>
                    <a href="{{ url('/admin/meetings/create') }}" class="text-sm text-white bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        + Buat Pertemuan Baru
                    </a>
                </div>

                <div class="grid grid-cols-2 md:grid-cols-5 gap-px bg-gray-200 border-b border-gray-200">
                    <div class="bg-white p-4 text-center">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Hadir</div>
                        <div class="mt-1 text-2xl font-bold text-green-700">{{ $attendanceSummary['present'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-4 text-center">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Sakit</div>
                        <div class="mt-1 text-2xl font-bold text-yellow-700">{{ $attendanceSummary['sick'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-4 text-center">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Izin</div>
                        <div class="mt-1 text-2xl font-bold text-blue-700">{{ $attendanceSummary['permission'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-4 text-center">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Alpha</div>
                        <div class="mt-1 text-2xl font-bold text-red-700">{{ $attendanceSummary['alpha'] ?? 0 }}</div>
                    </div>
                    <div class="bg-white p-4 text-center col-span-2 md:col-span-1">
                        <div class="text-xs font-semibold text-gray-500 uppercase">Hari Ini</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900">{{ isset($todayClasses) ? $todayClasses->count() : 0 }}</div>
                    </div>
                </div>

                @if(isset($scheduleMeetings) && $scheduleMeetings->count() > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Tanggal</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Kelas</th>
                                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Materi</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">H</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">S</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">I</th>
                                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-600 uppercase tracking-wider">A</th>
                                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100">
                                @foreach($scheduleMeetings as $meeting)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                                            {{ $meeting->date?->format('d M Y') ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">
                                            <div class="font-semibold text-green-700">{{ $meeting->classGroup?->name ?? '-' }}</div>
                                            <div class="text-xs text-gray-500">{{ $meeting->classGroup?->subject?->name ?? '-' }} / {{ $meeting->classGroup?->semester?->name ?? '-' }}</div>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-700">{{ $meeting->title }}</td>
                                        <td class="px-6 py-4 text-center text-sm font-bold text-green-700">{{ $meeting->present_count }}</td>
                                        <td class="px-6 py-4 text-center text-sm font-bold text-yellow-700">{{ $meeting->sick_count }}</td>
                                        <td class="px-6 py-4 text-center text-sm font-bold text-blue-700">{{ $meeting->permission_count }}</td>
                                        <td class="px-6 py-4 text-center text-sm font-bold text-red-700">{{ $meeting->alpha_count }}</td>
                                        <td class="px-6 py-4 text-right">
                                            <a href="{{ url('/admin/meetings/'.$meeting->id.'/edit') }}"
                                               target="_blank"
                                               class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                                                Isi Absensi
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="p-10 text-center">
                        <div class="inline-block p-4 rounded-full bg-gray-100 text-gray-400 mb-3">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Belum ada jadwal mendatang</h3>
                        <p class="text-gray-500">Buat pertemuan di admin panel agar jadwal dan absensi tampil otomatis di sini.</p>
                    </div>
                @endif
            </div>

            <!-- JADWAL HARI INI & ABSENSI LAMA -->
            <div class="hidden">
                <div class="px-6 py-4 bg-gray-50 border-b border-gray-200 flex justify-between items-center">
                    <h2 class="text-lg font-bold text-gray-800">📅 Jadwal Mengajar Hari Ini ({{ date('d M Y') }})</h2>
                    <a href="{{ url('/admin/meetings/create') }}" class="text-sm text-white bg-green-600 px-4 py-2 rounded-lg hover:bg-green-700 transition">
                        + Buat Pertemuan Baru
                    </a>
                </div>
                
                @if(isset($todayClasses) && count($todayClasses) > 0)
                    <div class="divide-y divide-gray-100">
                        @foreach($todayClasses as $class)
                        <div class="p-6 flex items-center justify-between hover:bg-gray-50 transition">
                            <div>
                                <h3 class="text-lg font-bold text-green-700">{{ $class->classGroup?->subject?->name ?? '-' }}</h3>
                                <p class="text-gray-600">{{ $class->title }}</p>
                                <span class="text-xs bg-gray-200 text-gray-600 px-2 py-1 rounded mt-1 inline-block">
                                    {{ $class->created_at->format('H:i') }} WIB
                                </span>
                            </div>
                            
                            <a href="{{ url('/admin/meetings/'.$class->id.'/edit') }}" 
                               target="_blank"
                               class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition shadow">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                                Isi Absensi
                            </a>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="p-10 text-center">
                        <div class="inline-block p-4 rounded-full bg-gray-100 text-gray-400 mb-3">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900">Tidak ada jadwal hari ini</h3>
                        <p class="text-gray-500">Silakan buat pertemuan baru jika ingin mengajar.</p>
                    </div>
                @endif
            </div>

        </div>
    </div>


{{-- ========================================== --}}
{{--              TAMPILAN SISWA                --}}
{{-- ========================================== --}}
@else
    @php
        $canCheckoutPayment = Auth::user()->hasAccess('payments.checkout');
        $hasUnpaidBill = isset($activeSemester) && $activeSemester && ! in_array($paymentStatus, ['success', 'paid']);
        $midtransSnapUrl = config('services.midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    @endphp

    <!-- SCRIPT MIDTRANS (Wajib Ada - Hanya load utk Siswa) -->
    @if($canCheckoutPayment && $hasUnpaidBill)
        <script src="{{ $midtransSnapUrl }}" data-client-key="{{ config('services.midtrans.client_key') }}"></script>
    @endif

    <div class="bg-gray-50 min-h-screen pb-12">
        <!-- Header Welcome -->
        <div class="bg-green-700 pb-24">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                <h1 class="text-3xl font-bold text-white">
                    Ahlan Wa Sahlan, {{ Auth::user()->name }}
                </h1>
                <p class="text-green-100 mt-2">
                    NISN: {{ Auth::user()->nisn ?? '-' }} | Semangat menuntut ilmu hari ini!
                </p>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 -mt-16">
            
            <!-- ALERT STATUS PEMBAYARAN -->
            @if(isset($activeSemester) && $activeSemester)
                <div class="bg-white rounded-lg shadow-lg p-6 mb-8 border-l-4 {{ in_array($paymentStatus, ['success', 'paid']) ? 'border-green-500' : 'border-red-500' }} fade-in-section">
                    <div class="flex items-center justify-between flex-col md:flex-row gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-800">Tagihan Semester: {{ $activeSemester->name }}</h2>
                            <p class="text-gray-600">Total Tagihan: <span class="font-bold">Rp {{ number_format($billAmount, 0, ',', '.') }}</span></p>
                            
                            <div class="mt-2">
                                Status: 
                                @if(in_array($paymentStatus, ['success', 'paid']))
                                    <span class="px-2 py-1 text-xs font-bold rounded bg-green-100 text-green-800">LUNAS</span>
                                @elseif($paymentStatus == 'pending')
                                    <span class="px-2 py-1 text-xs font-bold rounded bg-yellow-100 text-yellow-800">MENUNGGU PEMBAYARAN</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-bold rounded bg-red-100 text-red-800">BELUM DIBAYAR</span>
                                @endif
                            </div>
                        </div>

                        @if($hasUnpaidBill && $canCheckoutPayment)
                            <button id="pay-button" class="w-full md:w-auto px-6 py-3 bg-red-600 text-white font-bold rounded-lg hover:bg-red-700 transition shadow-lg animate-pulse">
                                Bayar Sekarang
                            </button>
                        @elseif($hasUnpaidBill)
                            <button disabled class="w-full md:w-auto px-6 py-3 bg-gray-100 text-gray-400 font-bold rounded-lg cursor-not-allowed">
                                Pembayaran Tidak Tersedia
                            </button>
                        @else
                             <button disabled class="w-full md:w-auto px-6 py-3 bg-gray-100 text-gray-400 font-bold rounded-lg cursor-not-allowed">
                                Sudah Lunas
                            </button>
                        @endif
                    </div>
                </div>
            @else
                <div class="bg-white rounded-lg shadow p-6 mb-8 border-l-4 border-gray-400">
                    <p class="text-gray-600">Tidak ada semester aktif saat ini.</p>
                </div>
            @endif

            <!-- MENU GRID -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 fade-in-section">
                
                <!-- Card Akademik -->
                <a href="{{ route('student.transcript') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-all border border-gray-100 group">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-blue-100 text-blue-600 rounded-full group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Transkrip Nilai</h3>
                            <p class="text-sm text-gray-500">Lihat Nilai</p>
                        </div>
                    </div>
                </a>

                <!-- Card Absensi -->
                <a href="{{ route('student.attendance') }}" class="bg-white rounded-lg shadow p-6 hover:shadow-lg transition-all border border-gray-100 group">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-purple-100 text-purple-600 rounded-full group-hover:scale-110 transition-transform">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Riwayat Absensi</h3>
                            <p class="text-sm text-gray-500">Cek Kehadiran</p>
                        </div>
                    </div>
                </a>

                <!-- Card Profil -->
                <div class="bg-white rounded-lg shadow p-6 border border-gray-100">
                    <div class="flex items-center gap-4">
                        <div class="p-3 bg-gray-100 text-gray-600 rounded-full">
                            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-900">Profil Saya</h3>
                            <form method="POST" action="{{ route('logout') }}" class="mt-1">
                                @csrf
                                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">
                                    Keluar (Logout)
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- LOGIKA PEMBAYARAN (Khusus Siswa) -->
    <script type="text/javascript">
        var payButton = document.getElementById('pay-button');
        if(payButton) {
            payButton.onclick = function () {
                // Ubah tombol jadi Loading
                payButton.innerHTML = 'Memproses...';
                payButton.disabled = true;

                // Panggil API Backend kita
                fetch('{{ route("payment.checkout") }}', {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                })
                    .then(async response => {
                        const data = await response.json().catch(() => ({}));

                        if (! response.ok) {
                            throw new Error(data.message || 'Checkout pembayaran gagal.');
                        }

                        return data;
                    })
                    .then(data => {
                        if (!window.snap) {
                            throw new Error('Script Midtrans Snap belum termuat. Silakan reload halaman.');
                        }

                        if (!data.snap_token) {
                            throw new Error('Token pembayaran tidak tersedia.');
                        }

                        // Munculkan Popup Midtrans
                        window.snap.pay(data.snap_token, {
                            onSuccess: function(result){
                                const params = new URLSearchParams({
                                    order_id: result.order_id || '',
                                    transaction_status: result.transaction_status || 'settlement',
                                    status_code: result.status_code || '',
                                    payment_type: result.payment_type || '',
                                });

                                window.location.href = "{{ route('payment.success') }}?" + params.toString();
                            },
                            onPending: function(result){
                                alert("Menunggu pembayaran!");
                                location.reload();
                            },
                            onError: function(result){
                                alert("Pembayaran gagal!");
                                location.reload();
                            },
                            onClose: function(){
                                alert('Anda menutup popup tanpa menyelesaikan pembayaran');
                                location.reload();
                            }
                        });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert(error.message || 'Terjadi kesalahan sistem.');
                        payButton.innerHTML = 'Bayar Sekarang';
                        payButton.disabled = false;
                    });
            };
        }
    </script>

@endif

@endsection
