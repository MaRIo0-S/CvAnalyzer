<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Email;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function showLogin()
{
        return Inertia::render('Auth/Login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Identifiants incorrects.']);
        }

        $request->session()->regenerate();
        $user = Auth::user();

        return match ($user->role) {
            Role::Admin => redirect()->route('admin.subadmins'),
            Role::SousAdmin => redirect()->route('rh.dashboard'),
            Role::Candidat => redirect()->route('candidat.statut'),
        };
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
