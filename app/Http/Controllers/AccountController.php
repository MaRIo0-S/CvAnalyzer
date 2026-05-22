<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function edit(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::Candidat, Role::SousAdmin], true)) {
            return redirect()->route('account.password.edit');
        }

        return Inertia::render('Account/Profil', [
            'profil' => [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'entreprise' => $user->entreprise?->nom,
            ],
            'peutModifierEmail' => true,
            'peutModifierNom' => true,
        ]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        if (! in_array($user->role, [Role::Candidat, Role::SousAdmin], true)) {
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

        $user->update($validated);

        if ($user->role === Role::Candidat) {
            $cv = $user->cvs()->orderBy('date_depot', 'desc')->first();
            if ($cv && $cv->peutModifier()) {
                $cv->update([
                    'nom_candidat' => $validated['name'],
                    'email_candidat' => $validated['email'],
                ]);
            }
        }

        return back()->with('success', 'Profil mis à jour.');
    }
}
