<?php

namespace App\Http\Controllers\Rh;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Poste;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PosteController extends Controller
{
    use Concerns\ResolvesRhEntreprise;

    public function index(Request $request)
    {
        $entrepriseId = $request->user()->entreprise_id;

        $entreprise = $request->user()->entreprise?->load('descriptionUpdatedBy:id,name');

        return Inertia::render('Rh/Postes', [
            'postes' => $this->queryPostesRh($request)
                ->orderBy('created_at', 'desc')
                ->get(['id', 'titre', 'description', 'est_ouvert', 'created_at']),
            'entreprise' => $entreprise ? [
                'id' => $entreprise->id,
                'nom' => $entreprise->nom,
                'description' => $entreprise->description,
                'description_updated_at' => $entreprise->description_updated_at?->format('d/m/Y à H:i'),
                'description_updated_by' => $entreprise->descriptionUpdatedBy?->name,
            ] : null,
            'peutModifierEntreprise' => $request->user()->isSuperAdmin(),
            'rhColleguesCount' => User::where('role', Role::SousAdmin)
                ->where('entreprise_id', $entrepriseId)
                ->where('est_actif', true)
                ->count(),
        ]);
    }

    public function store(Request $request)
    {
        $entrepriseId = $request->user()->entreprise_id;
        if (! $entrepriseId) {
            return back()->withErrors(['titre' => 'Votre compte n\'est rattaché à aucune entreprise.']);
        }

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'est_ouvert' => ['boolean'],
        ]);

        Poste::create([
            'titre' => $validated['titre'],
            'description' => $validated['description'] ?? null,
            'entreprise_id' => $entrepriseId,
            'user_id' => $request->user()->id,
            'date_creation' => now(),
            'est_ouvert' => $validated['est_ouvert'] ?? true,
        ]);

        return back()->with('success', 'Poste créé pour votre entreprise.');
    }

    public function updateEntreprise(Request $request)
    {
        abort(403, 'Seul le gérant peut modifier la description de l\'entreprise.');

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

        return back()->with(
            'success',
            'Présentation enregistrée pour toute l\'entreprise « '.$entreprise->nom.' ». Les autres RH de la même société verront ce texte.'
        );
    }

    public function update(Request $request, Poste $poste)
    {
        if ($poste->user_id !== $request->user()->id
            || $poste->entreprise_id !== $request->user()->entreprise_id) {
            abort(403);
        }

        $validated = $request->validate([
            'titre' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);

        $poste->update($validated);

        return back()->with('success', 'Poste mis à jour.');
    }

    public function destroy(Request $request, Poste $poste)
    {
        if ($poste->user_id !== $request->user()->id
            || $poste->entreprise_id !== $request->user()->entreprise_id) {
            abort(403);
        }

        $nbCvs = Cv::where('poste_id', $poste->id)->count();
        if ($nbCvs > 0) {
            return back()->withErrors([
                'poste' => "Impossible de supprimer ce poste : {$nbCvs} CV y sont encore rattachés. Fermez le poste aux candidatures plutôt que de le supprimer.",
            ]);
        }

        $poste->delete();

        return back()->with('success', 'Poste supprimé.');
    }

    public function toggleOuvert(Request $request, Poste $poste)
    {
        if ($poste->user_id !== $request->user()->id
            || $poste->entreprise_id !== $request->user()->entreprise_id) {
            abort(403);
        }

        $poste->update(['est_ouvert' => ! $poste->est_ouvert]);

        $message = $poste->est_ouvert
            ? 'Poste rouvert aux candidatures.'
            : 'Poste fermé aux candidatures.';

        return back()->with('success', $message);
    }
}
