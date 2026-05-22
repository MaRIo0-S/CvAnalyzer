<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\RegisterCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class RegisterController extends Controller
{
    public function showRegister()
    {
        return Inertia::render('Auth/Register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('register_pending', [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'code' => $code,
            'expires' => now()->addMinutes(15)->timestamp,
        ]);

        Mail::to($validated['email'])->send(
            new RegisterCodeMail($code, $validated['name'])
        );

        return redirect()
            ->route('register.verify')
            ->with('success', 'Un code à 6 chiffres a été envoyé à votre adresse e-mail.');
    }

    public function showVerify(Request $request)
    {
        if (! $request->session()->has('register_pending')) {
            return redirect()->route('register');
        }

        return Inertia::render('Auth/RegisterVerify', [
            'email' => $request->session()->get('register_pending.email'),
        ]);
    }

    public function verify(Request $request)
    {
        $pending = $request->session()->get('register_pending');

        if (! $pending || now()->timestamp > ($pending['expires'] ?? 0)) {
            $request->session()->forget('register_pending');

            return redirect()
                ->route('register')
                ->withErrors(['code' => 'Le code a expiré. Recommencez l\'inscription.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validated['code'] !== $pending['code']) {
            return back()->withErrors(['code' => 'Code incorrect.']);
        }

        if (User::where('email', $pending['email'])->exists()) {
            $request->session()->forget('register_pending');

            return redirect()
                ->route('register')
                ->withErrors(['email' => 'Cet e-mail est déjà utilisé.']);
        }

        $user = User::create([
            'name' => $pending['name'],
            'email' => $pending['email'],
            'password' => $pending['password'],
            'role' => Role::Candidat,
        ]);

        $request->session()->forget('register_pending');
        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('candidat.statut')
            ->with('success', 'Compte créé et confirmé.');
    }
}
