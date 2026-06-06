<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\CandidatAlerteMail;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        if ($user->role === Role::SousAdmin) {
            return redirect()
                ->route('rh.dashboard')
                ->with('info', 'Vos coordonnées sont gérées par le gérant de votre entreprise.');
        }

        if ($user->role !== Role::Candidat) {
            return redirect()->route('account.password.edit');
        }

        return Inertia::render('Account/Profil', [
            'profil' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'entreprise' => null,
            ],
            'peutModifierEmail' => true,
            'peutModifierNom' => true,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if ($user->role === Role::SousAdmin) {
            abort(403, 'Contactez votre gérant pour modifier vos informations.');
        }

        if ($user->role !== Role::Candidat) {
            abort(403, 'Modification du profil non disponible pour ce type de compte.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
        ], [
            'name.required' => 'Le nom est obligatoire.',
            'email.required' => 'L\'e-mail est obligatoire.',
            'email.email' => 'L\'e-mail n\'est pas valide.',
            'email.unique' => 'Cet e-mail est déjà utilisé.',
        ]);

        if ($validated['email'] !== $user->email) {
            AccountEmailVerifyController::demarrerChangement(
                $request,
                $validated['name'],
                $validated['email']
            );

            return redirect()
                ->route('account.email.verify')
                ->with('success', 'Un code à 6 chiffres a été envoyé à votre nouvelle adresse e-mail.');
        }

        $nomChange = $validated['name'] !== $user->name;
        $user->update(['name' => $validated['name']]);

        $cv = $user->cvs()->orderByDesc('date_depot')->first();
        if ($cv && $cv->peutModifier()) {
            $cv->update(['nom_candidat' => $validated['name']]);
        }

        if ($nomChange) {
            CandidatAlerteMail::envoyerProfil($user->fresh());
        }

        return back()->with('success', 'Profil mis à jour.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->role !== Role::Candidat) {
            abort(403);
        }

        $request->validate([
            'confirmation' => ['required', 'in:SUPPRIMER'],
        ], [
            'confirmation.in' => 'Saisissez SUPPRIMER pour confirmer la suppression définitive.',
        ]);

        $cvs = $user->cvs()->get();
        foreach ($cvs as $cv) {
            if ($cv->fichier_url) {
                Storage::disk('public')->delete($cv->fichier_url);
            }
            $cv->resultatAnalyse()?->delete();
            $cv->delete();
        }

        Notification::where('user_id', $user->id)->delete();

        CandidatAlerteMail::envoyerSuppressionCompte($user);

        $user->delete();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Votre compte a été supprimé définitivement.');
    }
}
