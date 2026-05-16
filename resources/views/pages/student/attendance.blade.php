@extends('root.app')

@section('title', 'Riwayat Absensi')

@section('content')
<div class="bg-gray-50 min-h-screen py-10">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <h1 class="text-2xl font-bold text-gray-900">Riwayat Kehadiran</h1>
                <a href="{{ route('dashboard') }}" class="text-green-600 hover:underline">← Kembali ke Dashboard</a>
            </div>
            
            <div class="mt-4 bg-white p-4 rounded-lg shadow-sm border border-gray-200">
                <div class="flex justify-between mb-1">
                    <span class="text-sm font-medium text-green-700">Persentase Kehadiran</span>
                    <span class="text-sm font-medium text-green-700">{{ $percentage }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2.5">
                    <div class="bg-green-600 h-2.5 rounded-full" style="width: {{ $percentage }}%"></div>
                </div>
            </div>
        </div>

        <div class="bg-white shadow overflow-hidden sm:rounded-lg fade-in-section">
            <ul role="list" class="divide-y divide-gray-200">
                @forelse($attendances as $attendance)
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-green-600 truncate">
                                    {{ $attendance->meeting->classGroup->subject->name }} - {{ $attendance->meeting->title }}
                                </p>
                                <div class="ml-2 flex-shrink-0 flex">
                                    @if($attendance->status == 'present')
                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Hadir</p>
                                    @elseif($attendance->status == 'sick')
                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Sakit</p>
                                    @elseif($attendance->status == 'permission')
                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Izin</p>
                                    @else
                                        <p class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Alpha</p>
                                    @endif
                                </div>
                            </div>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500">
                                    {{ $attendance->meeting->date->format('d M Y') }}
                                </p>
                            </div>
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-gray-500 text-sm">
                        Belum ada data absensi.
                    </li>
                @endforelse
            </ul>
            
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $attendances->links() }}
            </div>
        </div>

    </div>
</div>
@endsection
