<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntrepriseController extends Controller
{
    public function edit(Request $request)
    {
        $entreprise = $request->user()->entreprise;

        return Inertia::render('SuperAdmin/Entreprise', [
            'entreprise' => $entreprise ? [
                'nom' => $entreprise->nom,
                'description' => $entreprise->description,
            ] : null,
        ]);
    }

    public function update(Request $request)
    {
        $entreprise = $request->user()->entreprise;
        if (! $entreprise) {
            return back()->withErrors(['description' => 'Aucune entreprise rattachée.']);
        }

        $validated = $request->validate([
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $entreprise->update([
            'description' => $validated['description'] ?? null,
            'description_updated_by' => $request->user()->id,
            'description_updated_at' => now(),
        ]);

        return back()->with('success', 'Description entreprise enregistrée.');
    }
}
