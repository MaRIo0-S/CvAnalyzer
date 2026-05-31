<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\MessageContact;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        if ($user = $request->user()) {
            return match ($user->role) {
                Role::Admin => redirect()->route('admin.subadmins'),
                Role::SousAdmin => redirect()->route('rh.dashboard'),
                Role::Candidat => redirect()->route('candidat.statut'),
            };
        }

        return Inertia::render('Home', [
            'landingStats' => [
                ['value' => '12 400+', 'label' => 'CV reçus', 'tone' => 'cyan'],
                ['value' => '380+', 'label' => 'Postes publiés', 'tone' => 'indigo'],
                ['value' => '48', 'label' => 'Entreprises actives', 'tone' => 'emerald'],
                ['value' => '96 %', 'label' => 'Satisfaction RH', 'tone' => 'indigo'],
            ],
        ]);
    }

    public function contact(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nom' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'entreprise' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        MessageContact::create($validated);

        return redirect()
            ->route('home')
            ->with('success', 'Merci ! Votre message a bien été envoyé. Nous vous répondrons sous 48 h ouvrées.');
    }
}
