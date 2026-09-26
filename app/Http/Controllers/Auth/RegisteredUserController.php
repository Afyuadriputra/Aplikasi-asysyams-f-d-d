<?php

namespace App\Http\Controllers\Auth;

use App\Features\Auth\Services\SpmbRegistrationService;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(SpmbRegistrationService $spmbService): View|Response
    {
        if (! $spmbService->isOpen()) {
            return response()->view('auth.spmb-closed', [
                'deadline' => $spmbService->getDeadline(),
            ], 403);
        }

        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     */
    public function store(Request $request, SpmbRegistrationService $spmbService): RedirectResponse|Response
    {
        if (! $spmbService->isOpen()) {
            return response()->view('auth.spmb-closed', [
                'deadline' => $spmbService->getDeadline(),
            ], 403);
        }
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            
            // Validasi Tambahan PPDB
            'nisn' => ['required', 'numeric', 'digits_between:8,12', 'unique:'.User::class],
            'grade_level' => ['required', 'string'],
            'birth_date' => ['required', 'date'],
            'mother_name' => ['required', 'string', 'max:255'],
            'school_origin' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'phone' => ['required', 'numeric'],
            'gender' => ['required', 'in:L,P'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'student',    // Otomatis jadi Siswa
            'is_active' => false,   // Otomatis Pending (Menunggu Approve Ustad)
            
            // Simpan Data Tambahan
            'nisn' => $request->nisn,
            'grade_level' => $request->grade_level,
            'birth_date' => $request->birth_date,
            'mother_name' => $request->mother_name,
            'school_origin' => $request->school_origin,
            'address' => $request->address,
            'phone' => $request->phone,
            'gender' => $request->gender,
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}