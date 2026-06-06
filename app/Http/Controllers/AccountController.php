<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Mail\CandidatAlerteMail;
use Illuminate\Http\Request;
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

        $changements = $validated['name'] !== $user->name
            || $validated['email'] !== $user->email;

        $user->update($validated);

        if ($user->role === Role::Candidat) {
            $cv = $user->cvs()->orderBy('date_depot', 'desc')->first();
            if ($cv && $cv->peutModifier()) {
                $cv->update([
                    'nom_candidat' => $validated['name'],
                    'email_candidat' => $validated['email'],
                ]);
            }

            if ($changements) {
                CandidatAlerteMail::envoyerProfil($user->fresh());
            }
        }

        return back()->with('success', 'Profil mis à jour.');
    }
}
