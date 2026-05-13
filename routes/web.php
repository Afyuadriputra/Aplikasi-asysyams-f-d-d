<?php

use App\Features\Payments\Controllers\PaymentController;
use App\Features\Posts\Controllers\PostController;
use App\Http\Controllers\ProfileController;
use App\Features\Students\Controllers\StudentController;
use App\Http\Middleware\EnsureUserHasPermission;
use App\Features\Posts\Models\Post; // <--- Pastikan Model ini ada
use App\Features\SiteSettings\Models\SiteSetting; // <--- Pastikan Model ini ada
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use App\Features\Contacts\Controllers\ContactController;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. HALAMAN DEPAN (LANDING PAGE)
Route::get('/', function () {
    
    // A. Ambil Gambar Hero (Default null jika error)
    $heroBg = null;
    try {
        if (class_exists(SiteSetting::class)) {
            $heroBg = SiteSetting::where('key', 'hero_bg')->value('value');
        }
    } catch (\Throwable $e) { 
        // Biarkan null
    }
    $bgUrl = $heroBg ? asset('storage/' . $heroBg) : null;
    
    
    // B. AMBIL BERITA TERBARU (Fix Error Foreach)
    // Kita set default jadi Collection kosong dulu
    $posts = collect(); 
    
    try {
        // Cek apakah class Post ada dan tabelnya aman
        if (class_exists(Post::class)) {
            $posts = Post::where('is_published', true)
                ->with('author')
                ->latest('published_at')
                ->take(3)
                ->get();
        }
    } catch (\Throwable $e) {
        // Jika error database/tabel belum ada, $posts tetap collection kosong (tidak crash)
    }


    // C. AMBIL DEADLINE COUNTDOWN
    $deadline = now()->endOfYear()->format('Y-m-d H:i:s'); // Default
    try {
        if (class_exists(SiteSetting::class)) {
            $setting = SiteSetting::where('key', 'spmb_deadline')->value('value');
            if ($setting) {
                $deadline = $setting;
            }
        }
    } catch (\Throwable $e) {
        // Pakai default
    }
    
    // Convert ke format ISO 8601 untuk JavaScript (YYYY-MM-DDTHH:mm:ss)
    $deadlineISO = \Carbon\Carbon::parse($deadline)->format('Y-m-d\TH:i:s');

    // Kirim semua variabel ke view
    return view('pages.home', compact('bgUrl', 'posts', 'deadline', 'deadlineISO'));
});

// 2. HALAMAN DETAIL BERITA (PUBLIC)
Route::get('/berita/{slug}', [PostController::class, 'show'])->name('post.show');

// 3. HALAMAN MENUNGGU VERIFIKASI
Route::get('/approval', function () {
    return view('pages.approval');
})->middleware(['auth'])->name('approval.notice');

Route::get('/access-denied', function () {
    return view('pages.access-denied');
})->middleware(['auth'])->name('access.denied');

// 4. HALAMAN KHUSUS USER LOGIN (SISWA/USTAD)
Route::middleware(['auth', 'verified', 'is_active'])->group(function () {
    
    // Dashboard Hybrid (Siswa/Ustad)
    Route::get('/dashboard', [StudentController::class, 'dashboard'])->name('dashboard');

    // Fitur Siswa
    Route::get('/transcript', [StudentController::class, 'transcript'])
        ->middleware(EnsureUserHasPermission::class . ':reports.view')
        ->name('student.transcript');

    Route::get('/attendance', [StudentController::class, 'attendance'])
        ->middleware(EnsureUserHasPermission::class . ':dashboard.view')
        ->name('student.attendance');

    // Payment Midtrans
    Route::get('/payment/checkout', [PaymentController::class, 'checkout'])
        ->middleware(EnsureUserHasPermission::class . ':payments.checkout')
        ->name('payment.checkout');

    Route::get('/payment/success', [PaymentController::class, 'success'])->name('payment.success');

    // Profile Bawaan
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Route Webhook Midtrans (Bypass CSRF)
Route::post('/payment/webhook', [PaymentController::class, 'webhook'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('payment.webhook');

// Route Kirim Pesan
Route::post('/contact/send', [ContactController::class, 'send'])->name('contact.send');

// Route Cetak Rapor Santri PDF
Route::get('/rapor-pdf/{class_group}/{user}', function(ClassGroup $class_group, App\Models\User $user) {
    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('filament.report.rapor-pdf', [
        'student' => $user,
        'classGroup' => $class_group
    ]);
    return $pdf->stream('Rapor-' . Str::slug($user->name) . '.pdf');
})->middleware(['auth', EnsureUserHasPermission::class . ':reports.download'])->name('rapor.pdf');

// ===== TEMPORARY: CHECK DATABASE (HAPUS SETELAH DIGUNAKAN) =====
Route::get('/check-db', function () {
    try {
        // Test database connection
        DB::connection()->getPdo();
        
        // Check if users table exists
        $usersCount = DB::table('users')->count();
        
        // Check migration status
        $migrations = DB::table('migrations')->count();
        
        return response()->json([
            'status' => 'Database connected!',
            'users_count' => $usersCount,
            'migrations_count' => $migrations,
            'database_name' => config('database.connections.mysql.database'),
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'error' => $e->getMessage(),
            'database_name' => config('database.connections.mysql.database'),
        ], 500);
    }
});
// =================================================================

// Route::get('/program', function () {
//     return view('pages.program');
// })->name('program');
// Rute untuk membersihkan semua cache
Route::get('/clear-cache-sekarang', function () {
    Artisan::call('optimize:clear');
    Artisan::call('cache:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('config:clear');
    Artisan::call('filament:clear-cached-components');
    
    return 'Mantap! Semua cache Laravel dan Filament berhasil dibersihkan. Silakan buka /admin sekarang.';
});

// Rute untuk mengecek apakah rute admin Filament terbaca oleh server
Route::get('/cek-rute', function () {
    Artisan::call('route:list', ['--path' => 'admin']);
    return '<pre>' . Artisan::output() . '</pre>';
});
Route::get('/cek-pintu', function () {
    try {
        $url = route('filament.admin.auth.login');
        return "<h3>Sistem Laravel mendeteksi halaman login ada di link ini:</h3>
                <a href='{$url}' style='font-size:20px; font-weight:bold; color:blue;'>KLIK DI SINI UNTUK LOGIN</a>
                <br><br>
                <p>Kalau diklik masih 404, berarti ada yang memblokir dari luar Laravel.</p>";
    } catch (\Exception $e) {
        return "Terjadi error: " . $e->getMessage();
    }
});

require __DIR__.'/auth.php';
