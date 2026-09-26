<x-guest-layout>
    <div class="text-center py-6">
        <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 mb-4">
            <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
        </div>

        <h2 class="text-2xl font-bold text-gray-900 mb-2">Pendaftaran SPMB Ditutup</h2>
        <p class="text-gray-600 text-sm mb-4">
            Mohon maaf, periode pendaftaran santri baru (SPMB) telah berakhir.
        </p>

        @if(isset($deadline) && $deadline)
            <div class="bg-gray-50 rounded-lg p-4 mb-6 border border-gray-200 inline-block text-left text-xs text-gray-700">
                <span class="font-semibold text-gray-800">Batas Waktu Pendaftaran:</span>
                <span class="block mt-1 font-mono text-red-600">{{ $deadline->translatedFormat('d F Y, H:i') }} WIB</span>
            </div>
        @endif

        <div class="mt-6 flex flex-col sm:flex-row gap-3 justify-center">
            <a href="{{ url('/') }}" class="inline-flex justify-center items-center px-4 py-2 bg-green-700 hover:bg-green-800 text-white font-medium rounded-lg text-sm transition">
                Kembali ke Beranda
            </a>
            <a href="{{ route('login') }}" class="inline-flex justify-center items-center px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium rounded-lg text-sm transition">
                Login Santri
            </a>
        </div>
    </div>
</x-guest-layout>
