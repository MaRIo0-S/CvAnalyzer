<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class PortalLoginController extends Controller
{
    public function showSuperAdminLogin()
    {
        return Inertia::render('Auth/LoginPortal', [
            'portal' => 'super_admin',
            'title' => 'Connexion super administrateur',
            'subtitle' => 'Administrateur global de la plateforme CvAnalyzer (gère les gérants).',
            'action' => route('login.super-admin.store'),
        ]);
    }

    public function showGerantLogin()
    {
        return Inertia::render('Auth/LoginPortal', [
            'portal' => 'admin',
            'title' => 'Connexion gérant entreprise',
            'subtitle' => 'Gérant de votre entreprise (supervision, équipe RH).',
            'action' => route('login.gerant.store'),
        ]);
    }

    public function showStaffLogin()
    {
        return Inertia::render('Auth/Login', [
            'staffOnly' => true,
        ]);
    }

    public function loginSuperAdmin(Request $request)
    {
        return $this->attempt($request, [Role::SuperAdmin], 'admin.backoffice');
    }

    public function loginGerant(Request $request)
    {
        return $this->attempt($request, [Role::Admin], 'gerant.dashboard');
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
            Role::SuperAdmin => route('admin.backoffice'),
            Role::Admin => route('gerant.dashboard'),
            Role::SousAdmin => route('rh.dashboard'),
            Role::Candidat => route('candidat.statut'),
        };
    }
}
