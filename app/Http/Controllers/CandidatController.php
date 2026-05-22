<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class CandidatController extends Controller
{
    public function statut(Request $request)
    {
        $cv = $request->user()
            ->cvs()
            ->with(['poste', 'entreprise'])
            ->orderBy('date_depot', 'desc')
            ->first();

        return Inertia::render('Candidat/Statut', [
            'cv' => $cv ? [
                'statut' => $cv->statut->value,
                'statut_label' => $cv->statut->label(),
                'date_depot' => $cv->date_depot?->format('d/m/Y H:i'),
                'modifiable_jusqu' => $cv->modifiable_jusqu?->format('d/m/Y à H:i'),
                'peut_modifier' => $cv->peutModifier(),
                'poste' => $cv->poste?->titre,
                'entreprise' => $cv->entreprise?->nom,
            ] : null,
        ]);
    }
}
