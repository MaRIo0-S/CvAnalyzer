<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\RegisterCodeMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;

class AccountEmailVerifyController extends Controller
{
    public function show(Request $request)
    {
        if (! $request->session()->has('email_change_pending')) {
            return redirect()->route('account.edit');
        }

        return Inertia::render('Account/VerifyEmail', [
            'email' => $request->session()->get('email_change_pending.email'),
        ]);
    }

    public function verify(Request $request)
    {
        $pending = $request->session()->get('email_change_pending');
        $user = $request->user();

        if (! $pending || ! $user || $user->role !== Role::Candidat) {
            return redirect()->route('account.edit');
        }

        if (now()->timestamp > ($pending['expires'] ?? 0)) {
            $request->session()->forget('email_change_pending');

            return redirect()
                ->route('account.edit')
                ->withErrors(['email' => 'Le code a expiré. Recommencez la modification.']);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validated['code'] !== ($pending['code'] ?? '')) {
            return back()->withErrors(['code' => 'Code incorrect.']);
        }

        $user->update([
            'name' => $pending['name'],
            'email' => $pending['email'],
        ]);

        $cv = $user->cvs()->orderByDesc('date_depot')->first();
        if ($cv && $cv->peutModifier()) {
            $cv->update(['nom_candidat' => $pending['name']]);
        }

        $request->session()->forget('email_change_pending');

        return redirect()
            ->route('account.edit')
            ->with('success', 'Profil mis à jour. Votre nouvelle adresse e-mail est active.');
    }

    public static function demarrerChangement(Request $request, string $name, string $email): void
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $request->session()->put('email_change_pending', [
            'name' => $name,
            'email' => $email,
            'code' => $code,
            'expires' => now()->addMinutes(15)->timestamp,
        ]);

        Mail::to($email)->send(new RegisterCodeMail($code, $name));
    }
}
