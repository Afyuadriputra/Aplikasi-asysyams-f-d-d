@php
    use App\Features\SiteSettings\Models\SiteSetting;

    try {
        $footerBg = SiteSetting::where('key', 'bg_footer')->value('value');
    } catch (\Exception $e) {
        $footerBg = null;
    }

    $bgUrl = $footerBg
        ? asset('storage/' . $footerBg)
        : 'https://img.freepik.com/free-photo/aerial-view-large-building-with-green-grass_1127-3367.jpg';
@endphp

<!-- BAGIAN CTA (Call To Action) -->
<section class="relative overflow-hidden bg-green-800">

    <!-- MOBILE CTA -->
    <div class="block md:hidden relative h-[260px]">
        <img
            src="{{ $bgUrl }}"
            alt="Footer Background"
            class="w-full h-full object-cover object-top"
        >

        <!-- Button Mobile di atas gambar, bawah -->
        <div class="absolute inset-x-0 bottom-4 z-20 flex justify-center px-4">
            <a
                href="{{ route('register') }}"
                class="inline-flex items-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-2 px-5 rounded shadow-lg transition transform hover:scale-105 text-xs"
            >
                Daftar Sekarang
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>

    <!-- DESKTOP CTA -->
    <div class="hidden md:block relative h-[500px]">
        <img
            src="{{ $bgUrl }}"
            alt="Footer Background"
            class="w-full h-full object-cover object-center"
        >

        <!-- Button Desktop di atas gambar, bawah -->
        <div class="absolute inset-x-0 bottom-10 lg:bottom-12 z-20 flex justify-center px-4">
            <a
                href="{{ route('register') }}"
                class="inline-flex items-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-black font-bold py-3 px-8 rounded shadow-lg transition transform hover:scale-105 text-base"
            >
                Daftar Sekarang
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"></path>
                </svg>
            </a>
        </div>
    </div>

</section>

<!-- BAGIAN BAWAH (Hijau Solid) -->
<section class="bg-green-800 py-6 md:py-8 border-t-4 border-yellow-500">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col items-center text-center">

        <!-- Logo Center -->
        <div class="flex flex-col sm:flex-row items-center gap-3 mb-4">
            <img
                src="{{ asset('images/logo.PNG') }}"
                alt="Logo RQ Asy-Syams"
                class="w-12 h-12 object-contain shadow-lg rounded-full"
            >

            <div class="text-center text-white">
                <h4 class="font-bold text-xl md:text-2xl tracking-widest font-serif uppercase">
                    YPTQ ASY-SYAMS
                </h4>
                <p class="text-[10px] md:text-xs text-green-100 tracking-[0.2em] md:tracking-[0.3em] uppercase">
                    Tilawah Center Pekanbaru
                </p>
            </div>
        </div>

        <!-- Copyright -->
        <p class="text-white text-xs md:text-sm opacity-80 mt-2 md:mt-4 leading-relaxed">
            &copy; {{ date('Y') }} Yayasan Pendidikan Tilawah Qur'an Asy-Syams Pekanbaru. All rights reserved.
        </p>

    </div>
</section>