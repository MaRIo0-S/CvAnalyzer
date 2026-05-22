<?php

namespace App\Http\Controllers\Rh;

use App\Enums\StatutCv;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Rh\Concerns\GereSessionAnalyseRh;
use App\Http\Controllers\Rh\Concerns\ResolvesRhEntreprise;
use App\Mail\StatutCandidatureMail;
use App\Models\Cv;
use App\Models\Poste;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CvController extends Controller
{
    use GereSessionAnalyseRh;
    use ResolvesRhEntreprise;

    private function authorizeCv(Request $request, Cv $cv): void
    {
        if ($cv->entreprise_id !== $this->entrepriseId($request)) {
            abort(403);
        }
    }

    public function index(Request $request)
    {
        $eid = $this->entrepriseId($request);

        $cvs = Cv::with(['poste:id,titre', 'resultatAnalyse'])
            ->where('entreprise_id', $eid)
            ->orderByDesc('date_depot')
            ->get()
            ->map(fn (Cv $cv) => $cv->pourRh());

        return Inertia::render('Rh/CvsListe', [
            'cvs' => $cvs,
            'postes' => Poste::where('entreprise_id', $eid)
                ->orderBy('titre')
                ->get(['id', 'titre']),
            'entreprise' => $request->user()->entreprise?->nom,
            'zipUrl' => route('rh.cvs.zip'),
        ]);
    }

    public function valider(Request $request, Cv $cv)
    {
        $this->authorizeCv($request, $cv);

        if ($cv->statut !== StatutCv::EnCoursAnalyse) {
            return back()->withErrors([
                'decision' => 'Seules les candidatures en cours d\'analyse peuvent être validées ou refusées.',
            ]);
        }

        $validated = $request->validate([
            'valide' => ['required', 'boolean'],
        ]);

        if ($validated['valide'] && ! filled($cv->email_candidat)) {
            return back()->withErrors([
                'email_candidat' => 'E-mail du candidat manquant : complétez le dossier avant de valider.',
            ]);
        }

        if ($this->cvDansLotAnalyse($request, $cv->id)) {
            $decision = $validated['valide'] ? 'valide' : 'non_valide';
            $this->enregistrerDecisionProvisoire($request, $cv->id, $decision);

            return back()->with(
                'success',
                $validated['valide']
                    ? 'Décision enregistrée (à confirmer en fin d\'analyse).'
                    : 'Refus enregistré (à confirmer en fin d\'analyse).'
            );
        }

        $statut = $validated['valide'] ? StatutCv::Valide : StatutCv::NonValide;
        $ancienStatut = $cv->statut;
        $cv->update(['statut' => $statut]);
        $cv->refresh();
        StatutCandidatureMail::envoyerSiChange($cv, $ancienStatut, $statut);

        return back()->with(
            'success',
            $validated['valide'] ? 'CV validé.' : 'Candidature refusée.'
        );
    }

    public function telechargerZip(Request $request): BinaryFileResponse|RedirectResponse
    {
        $eid = $this->entrepriseId($request);

        $validated = $request->validate([
            'poste_id' => [
                'nullable',
                Rule::exists('postes', 'id')->where(fn ($q) => $q->where('entreprise_id', $eid)),
            ],
            'statut' => ['nullable', Rule::in(array_column(StatutCv::cases(), 'value'))],
            'cv_ids' => ['nullable', 'array', 'min:1'],
            'cv_ids.*' => [
                'integer',
                Rule::exists('cvs', 'id')->where(fn ($q) => $q->where('entreprise_id', $eid)),
            ],
        ]);

        $query = Cv::where('entreprise_id', $eid);

        if (! empty($validated['cv_ids'])) {
            $query->whereIn('id', $validated['cv_ids']);
        } elseif (! empty($validated['poste_id'])) {
            $query->where('poste_id', $validated['poste_id']);
            if (! empty($validated['statut'])) {
                $query->where('statut', $validated['statut']);
            }
        } else {
            return redirect()
                ->route('rh.cvs.liste')
                ->withErrors(['zip' => 'Sélectionnez des CV ou un poste pour le téléchargement ZIP.']);
        }

        $cvs = $query->get();

        if ($cvs->isEmpty()) {
            return redirect()
                ->route('rh.cvs.liste')
                ->withErrors(['zip' => 'Aucun CV trouvé pour ce téléchargement.']);
        }

        $zipPath = storage_path('app/temp/cvs-'.uniqid().'.zip');
        if (! is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            abort(500, 'Impossible de créer l\'archive ZIP.');
        }

        $ajoutes = 0;
        foreach ($cvs as $cv) {
            if (! Storage::disk('public')->exists($cv->fichier_url)) {
                continue;
            }
            $contenu = Storage::disk('public')->get($cv->fichier_url);
            if ($contenu === null || $contenu === '') {
                continue;
            }
            $zip->addFromString($this->nomFichierZip($cv), $contenu);
            $ajoutes++;
        }

        $zip->close();

        if ($ajoutes === 0) {
            @unlink($zipPath);

            return redirect()
                ->route('rh.cvs.liste')
                ->withErrors(['zip' => 'Aucun fichier CV disponible sur le disque.']);
        }

        $poste = ! empty($validated['poste_id'])
            ? Poste::find($validated['poste_id'])
            : null;
        $slug = $poste
            ? str($poste->titre)->slug()->limit(40)
            : str('selection');
        $nomZip = 'cvs-'.$slug.'-'.now()->format('Y-m-d').'.zip';

        return response()->download($zipPath, $nomZip)->deleteFileAfterSend(true);
    }

    private function nomFichierZip(Cv $cv): string
    {
        $base = str($cv->nom_candidat ?: 'candidat-'.$cv->id)
            ->slug()
            ->limit(50);

        return $base.'-'.$cv->id.'.'.$cv->format_fichier;
    }

    public function show(Request $request, Cv $cv)
    {
        $this->authorizeCv($request, $cv);
        $cv->load(['poste', 'entreprise', 'resultatAnalyse']);

        $depuisAnalyse = $request->query('depuis') === 'analyse';
        $decision = $depuisAnalyse ? $this->decisionProvisoire($request, $cv->id) : null;

        return Inertia::render('Rh/CvConsulter', [
            'retourUrl' => $depuisAnalyse
                ? route('rh.filtrer.resultats')
                : route('rh.cvs.liste'),
            'retourLabel' => $depuisAnalyse
                ? "Retour aux résultats d'analyse"
                : 'Retour à la liste des CV',
            'lotEnAttente' => $depuisAnalyse && $this->cvDansLotAnalyse($request, $cv->id),
            'cv' => [
                'id' => $cv->id,
                'nom_candidat' => $cv->nom_candidat ?: 'Candidat #'.$cv->id,
                'email_candidat' => $cv->email_candidat,
                'poste' => $cv->poste?->titre,
                'entreprise' => $cv->entreprise?->nom,
                'statut' => $cv->statut->value,
                'statut_label' => Cv::libelleAvecDecision($decision, $cv->statut->label()),
                'date_depot' => $cv->date_depot?->format('d/m/Y à H:i'),
                'format_fichier' => strtoupper($cv->format_fichier),
                'taille_fichier' => $cv->taille_fichier,
                'texte_extrait' => $cv->texte_extrait,
                'score' => $cv->donneesAnalyseAffichage()['score'],
                'mots_cles_matches' => $cv->statut === StatutCv::CvRecu
                    ? []
                    : ($cv->resultatAnalyse?->mots_cles_matches ?? []),
                'fichier_preview' => strtolower($cv->format_fichier) === 'pdf',
            ],
            'fichierUrl' => route('rh.cv.fichier', $cv),
            'telechargerUrl' => route('rh.cv.telecharger', $cv),
        ]);
    }

    public function fichier(Request $request, Cv $cv): \Symfony\Component\HttpFoundation\Response
    {
        $this->authorizeCv($request, $cv);

        if (! Storage::disk('public')->exists($cv->fichier_url)) {
            abort(404, 'Fichier CV introuvable.');
        }

        $path = Storage::disk('public')->path($cv->fichier_url);
        $mime = match (strtolower($cv->format_fichier)) {
            'pdf' => 'application/pdf',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'doc' => 'application/msword',
            default => 'application/octet-stream',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="cv-'.$cv->id.'.'.$cv->format_fichier.'"',
        ]);
    }

    public function telecharger(Request $request, Cv $cv): StreamedResponse
    {
        $this->authorizeCv($request, $cv);

        if (! Storage::disk('public')->exists($cv->fichier_url)) {
            abort(404, 'Fichier CV introuvable.');
        }

        $nom = 'cv-'.($cv->nom_candidat ?: $cv->id).'.'.$cv->format_fichier;

        return Storage::disk('public')->download($cv->fichier_url, $nom);
    }
}
