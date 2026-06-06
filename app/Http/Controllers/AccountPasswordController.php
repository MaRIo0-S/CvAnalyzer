<?php

namespace App\Http\Controllers;

use App\Mail\CandidatAlerteMail;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AccountPasswordController extends Controller
{
    public function edit()
    {
        return Inertia::render('Account/MotDePasse');
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => $validated['password'],
        ]);

        CandidatAlerteMail::envoyerMotDePasse($user->fresh());

        return back()->with('success', 'Votre mot de passe a été mis à jour.');
    }
}
