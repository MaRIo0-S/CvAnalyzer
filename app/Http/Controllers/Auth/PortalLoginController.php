<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PortalLoginController extends Controller
{
    public function showAdminLogin()
    {
        return Inertia::render('Auth/LoginPortal', [
            'portal' => 'admin',
            'title' => 'Connexion administrateur plateforme',
            'subtitle' => 'Réservé aux administrateurs CvAnalyzer.',
            'action' => route('login.admin.store'),
        ]);
    }

    public function showSuperAdminLogin()
    {
        return Inertia::render('Auth/LoginPortal', [
            'portal' => 'super_admin',
            'title' => 'Connexion gérant entreprise',
            'subtitle' => 'Responsable RH / super-admin de votre société.',
            'action' => route('login.super-admin.store'),
        ]);
    }

    public function showStaffLogin()
    {
        return Inertia::render('Auth/Login', [
            'staffOnly' => true,
        ]);
    }

    public function loginAdmin(Request $request)
    {
        return $this->attempt($request, [Role::Admin], 'admin.backoffice');
    }

    public function loginSuperAdmin(Request $request)
    {
        return $this->attempt($request, [Role::SuperAdmin], 'super-admin.dashboard');
    }

    public function loginStaff(Request $request)
    {
        return $this->attempt($request, [Role::SousAdmin, Role::Candidat], null);
    }

    private function attempt(Request $request, array $roles, ?string $route)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors(['email' => 'Identifiants incorrects.']);
        }

        $user = Auth::user();

        if (! in_array($user->role, $roles, true)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Identifiants incorrects.']);
        }

        if (! $user->est_actif) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'Identifiants incorrects.']);
        }

        $request->session()->regenerate();

        if ($route) {
            return redirect()->route($route);
        }

        return redirect()->to($this->homeForRole($user->role));
    }

    private function homeForRole(Role $role): string
    {
        return match ($role) {
            Role::Admin => route('admin.backoffice'),
            Role::SuperAdmin => route('super-admin.dashboard'),
            Role::SousAdmin => route('rh.dashboard'),
            Role::Candidat => route('candidat.statut'),
        };
    }
}
