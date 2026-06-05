<?php

namespace App\Http\Controllers\Rh;

use App\Enums\StatutCv;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Rh\Concerns\ResolvesRhEntreprise;
use App\Models\Cv;
use App\Models\Poste;
use App\Services\ServiceAnalyse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CvImportController extends Controller
{
    use ResolvesRhEntreprise;

    public function __construct(private ServiceAnalyse $serviceAnalyse) {}

    public function create(Request $request)
    {
        return Inertia::render('Rh/CvImporter', [
            'postes' => $this->queryPostesRh($request)
                ->orderBy('titre')
                ->get(['id', 'titre', 'description']),
            'entreprise' => $request->user()->entreprise?->nom,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'poste_id' => ['required', 'exists:postes,id'],
            'fichiers' => ['required', 'array', 'min:1', 'max:20'],
            'fichiers.*' => ['required', 'file', 'max:5120'],
        ]);

        $poste = Poste::findOrFail($validated['poste_id']);
        if ((int) $poste->entreprise_id !== (int) $request->user()->entreprise_id) {
            abort(403);
        }
        if (! $poste->est_ouvert) {
            return back()->withErrors(['poste_id' => 'Ce poste n\'accepte plus de candidatures.']);
        }

        $importes = 0;
        $erreurs = [];

        foreach ($request->file('fichiers', []) as $index => $file) {
            $extension = strtolower($file->getClientOriginalExtension());

            if (! $this->serviceAnalyse->verifierFormat($extension)) {
                $erreurs[] = ($file->getClientOriginalName() ?: 'Fichier '.($index + 1)).' : format non accepté.';

                continue;
            }

            $tailleMo = $file->getSize() / 1024 / 1024;
            if (! $this->serviceAnalyse->verifierTaille($tailleMo)) {
                $erreurs[] = ($file->getClientOriginalName() ?: 'Fichier '.($index + 1)).' : trop volumineux (max 5 Mo).';

                continue;
            }

            Cv::create([
                'poste_id' => $poste->id,
                'entreprise_id' => $request->user()->entreprise_id,
                'user_id' => null,
                'nom_candidat' => '',
                'email_candidat' => '',
                'fichier_url' => $file->store('cvs', 'public'),
                'taille_fichier' => round($tailleMo, 2),
                'format_fichier' => $extension,
                'statut' => StatutCv::CvRecu,
                'date_depot' => now(),
                'modifiable_jusqu' => null,
                'importe_par_rh' => true,
            ]);

            $importes++;
        }

        if ($importes === 0) {
            return back()->withErrors([
                'fichiers' => $erreurs[0] ?? 'Aucun fichier valide n\'a pu être importé.',
            ]);
        }

        $message = $importes === 1
            ? '1 CV importé (numéro de dossier attribué automatiquement).'
            : "{$importes} CV importés (numéro de dossier pour chaque dossier).";

        if ($erreurs !== []) {
            $message .= ' '.count($erreurs).' fichier(s) ignoré(s).';
        }

        return redirect()
            ->route('rh.cvs.liste')
            ->with('success', $message);
    }
}
