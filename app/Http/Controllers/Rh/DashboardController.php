<?php

namespace App\Http\Controllers\Rh;

use App\Enums\StatutCv;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Rh\Concerns\GereSessionAnalyseRh;
use App\Http\Controllers\Rh\Concerns\ResolvesRhEntreprise;
use App\Mail\ReanalyseCvMail;
use App\Mail\StatutCandidatureMail;
use App\Models\Cv;
use App\Models\MotCle;
use App\Models\Poste;
use App\Services\ServiceAnalyse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DashboardController extends Controller
{
    use GereSessionAnalyseRh;
    use ResolvesRhEntreprise;

    public function __construct(private ServiceAnalyse $serviceAnalyse) {}

    public function filtrerPage(Request $request)
    {
        $eid = $this->entrepriseId($request);

        return Inertia::render('Rh/Filtrer', [
            'postes' => Poste::where('entreprise_id', $eid)
                ->where('est_ouvert', true)
                ->orderBy('titre')
                ->get(['id', 'titre']),
            'entreprise' => $request->user()->entreprise?->nom,
            'hasDerniereAnalyse' => $this->lotAnalyseEnAttente($request),
        ]);
    }

    public function index(Request $request)
    {
        $eid = $this->entrepriseId($request);

        $parStatut = Cv::where('entreprise_id', $eid)
            ->get()
            ->groupBy(fn (Cv $cv) => $cv->statut->value)
            ->map->count();

        $stats = [
            'total' => $parStatut->sum(),
            'recus' => $parStatut->get(StatutCv::CvRecu->value, 0),
            'analyses' => $parStatut->get(StatutCv::EnCoursAnalyse->value, 0),
            'valides' => $parStatut->get(StatutCv::Valide->value, 0),
            'non_valides' => $parStatut->get(StatutCv::NonValide->value, 0),
        ];

        $parPoste = Poste::where('entreprise_id', $eid)
            ->withCount('cvs')
            ->whereHas('cvs')
            ->orderByDesc('cvs_count')
            ->limit(6)
            ->get()
            ->map(fn ($p) => ['label' => $p->titre, 'value' => $p->cvs_count]);

        return Inertia::render('Rh/Dashboard', [
            'stats' => $stats,
            'entreprise' => $request->user()->entreprise?->nom,
            'chartStatuts' => [
                ['label' => 'Reçus', 'value' => $stats['recus']],
                ['label' => 'En analyse', 'value' => $stats['analyses']],
                ['label' => 'Validés', 'value' => $stats['valides']],
                ['label' => 'Non validés', 'value' => $stats['non_valides']],
            ],
            'chartPostes' => $parPoste,
        ]);
    }

    public function filtrer(Request $request)
    {
        $eid = $this->entrepriseId($request);

        $validated = $request->validate([
            'poste_id' => [
                'nullable',
                Rule::exists('postes', 'id')->where(
                    fn ($q) => $q->where('entreprise_id', $eid)
                ),
            ],
            'mots_cles' => ['required', 'array', 'min:1'],
            'mots_cles.*' => ['string', 'max:100'],
            'inclure_non_valides' => ['sometimes', 'boolean'],
        ]);

        $mots = array_values(array_filter(array_map('trim', $validated['mots_cles'])));

        if ($mots === []) {
            return back()->withErrors([
                'mots_cles' => 'Ajoutez au moins un mot-clé valide (non vide).',
            ]);
        }

        $this->enregistrerMotsCles($request, $mots, $validated['poste_id'] ?? null);

        if ($this->sessionAnalyse($request)) {
            $this->annulerAnalyseProvisoire($request, $eid);
        }

        $nonValidesUniquement = $request->boolean('inclure_non_valides');
        $eligibles = $this->cvsEligiblesAnalyse($eid, $validated['poste_id'] ?? null, $nonValidesUniquement);

        if ($eligibles->isEmpty()) {
            $msg = $nonValidesUniquement
                ? 'Aucun CV non validé éligible (dépôt de moins de 30 jours, hors CV validés).'
                : 'Aucun CV éligible (CV reçus après 24 h ou en cours d\'analyse — les non validés ne sont pas mélangés).';

            return back()->withErrors(['mots_cles' => $msg]);
        }

        $statutsInitiaux = $eligibles->mapWithKeys(
            fn (Cv $cv) => [$cv->id => $cv->statut->value]
        )->all();

        $ids = $eligibles->pluck('id')->all();

        $cvs = Cv::with(['poste', 'entreprise', 'resultatAnalyse'])
            ->whereIn('id', $ids)
            ->get();

        $this->serviceAnalyse->analyserCollection($cvs, $mots, notifier: false);

        $cvsList = Cv::with(['poste', 'entreprise', 'resultatAnalyse'])
            ->where('entreprise_id', $eid)
            ->whereIn('id', $ids)
            ->where('statut', StatutCv::EnCoursAnalyse)
            ->get()
            ->sortByDesc(fn (Cv $cv) => $cv->resultatAnalyse?->nombre_matches ?? 0)
            ->values()
            ->map(fn (Cv $cv) => $cv->pourRh(avecEntreprise: true))
            ->all();

        $request->session()->put(self::SESSION_ANALYSE, [
            'cv_ids' => collect($cvsList)->pluck('id')->all(),
            'decisions' => [],
            'mots_cles' => $mots,
            'entreprise' => $request->user()->entreprise?->nom,
            'en_attente_confirmation' => true,
            'mode' => $nonValidesUniquement ? 'non_valides' : 'standard',
            'statuts_initiaux' => $statutsInitiaux,
        ]);

        return redirect()->route('rh.filtrer.resultats');
    }

    public function confirmerAnalyse(Request $request)
    {
        $data = $this->sessionAnalyse($request);

        if (! $data || ! ($data['en_attente_confirmation'] ?? false)) {
            return redirect()->route('rh.filtrer.page')
                ->withErrors(['decision' => 'Aucune analyse en attente de confirmation.']);
        }

        $eid = $this->entrepriseId($request);
        $statutsInitiaux = $data['statuts_initiaux'] ?? [];
        $decisions = $data['decisions'] ?? [];
        $mode = $data['mode'] ?? 'standard';
        $cvIds = $data['cv_ids'] ?? [];
        $nbDecisions = 0;
        $nbMails = 0;

        $cvs = Cv::with(['poste', 'entreprise'])
            ->where('entreprise_id', $eid)
            ->whereIn('id', $cvIds)
            ->get()
            ->keyBy('id');

        if ($mode === 'non_valides') {
            foreach ($cvIds as $cvId) {
                if (($decisions[$cvId] ?? null) === 'valide') {
                    continue;
                }
                $cv = $cvs->get($cvId);
                if ($cv && filled($cv->email_candidat)) {
                    Mail::to($cv->email_candidat)->send(new ReanalyseCvMail($cv));
                    $nbMails++;
                }
            }
        }

        foreach ($cvIds as $cvId) {
            $cv = $cvs->get($cvId);
            if (! $cv || $cv->statut !== StatutCv::EnCoursAnalyse) {
                continue;
            }

            $decision = $decisions[$cvId] ?? null;
            $initial = StatutCv::tryFrom($statutsInitiaux[$cvId] ?? '') ?? $cv->statut;

            if ($decision === 'valide') {
                if (! filled($cv->email_candidat)) {
                    continue;
                }
                $ancien = $cv->statut;
                $cv->update(['statut' => StatutCv::Valide]);
                $cv->refresh();
                if (StatutCandidatureMail::envoyerSiChange($cv, $ancien, StatutCv::Valide)) {
                    $nbMails++;
                }
                $nbDecisions++;

                continue;
            }

            if ($decision === 'non_valide') {
                $ancien = $cv->statut;
                $cv->update(['statut' => StatutCv::NonValide]);
                $cv->refresh();
                if (StatutCandidatureMail::envoyerSiChange($cv, $ancien, StatutCv::NonValide)) {
                    $nbMails++;
                }
                $nbDecisions++;

                continue;
            }

            if ($initial === StatutCv::CvRecu && filled($cv->email_candidat)) {
                if (StatutCandidatureMail::envoyerSiChange($cv, StatutCv::CvRecu, StatutCv::EnCoursAnalyse)) {
                    $nbMails++;
                }
            }
        }

        $this->oublierSessionAnalyse($request);

        $msg = $nbDecisions > 0
            ? "Analyse confirmée : {$nbDecisions} décision(s) appliquée(s)."
            : 'Analyse confirmée.';

        if ($nbMails > 0) {
            $msg .= " {$nbMails} e-mail(s) envoyé(s).";
        }

        return redirect()->route('rh.cvs.liste')->with('success', $msg);
    }

    public function derniereAnalyse(Request $request)
    {
        $data = $this->sessionAnalyse($request);

        if (! $data || ! ($data['en_attente_confirmation'] ?? false)) {
            return redirect()->route('rh.filtrer.page');
        }

        $eid = $this->entrepriseId($request);
        $decisions = $data['decisions'] ?? [];
        $cvIds = $data['cv_ids'] ?? [];

        $cvsList = Cv::with(['poste:id,titre', 'entreprise:id,nom', 'resultatAnalyse'])
            ->where('entreprise_id', $eid)
            ->whereIn('id', $cvIds)
            ->where('statut', StatutCv::EnCoursAnalyse)
            ->get()
            ->sortByDesc(fn (Cv $cv) => $cv->resultatAnalyse?->nombre_matches ?? 0)
            ->values()
            ->map(fn (Cv $cv) => $cv->pourRh(
                $decisions[$cv->id] ?? null,
                avecEntreprise: true
            ))
            ->all();

        $nbDecisions = count(array_filter($decisions));

        return Inertia::render('Rh/CvsFiltres', [
            'cvs' => $cvsList,
            'mots_cles' => $data['mots_cles'] ?? [],
            'chartClassement' => $this->chartClassement($cvsList),
            'zipUrl' => route('rh.cvs.zip'),
            'modeAnalyse' => $data['mode'] ?? 'standard',
            'nbDecisions' => $nbDecisions,
            'postes' => Poste::where('entreprise_id', $eid)
                ->orderBy('titre')
                ->get(['id', 'titre']),
        ]);
    }

    private function chartClassement(array $cvsList): array
    {
        return collect($cvsList)->map(fn ($cv, $i) => [
            'label' => ($i + 1).'. '.$cv['nom_candidat'],
            'matches' => $cv['nombre_matches'] ?? 0,
            'score' => $cv['score'] ?? 0,
        ])->take(10)->values()->all();
    }

    private function cvsEligiblesAnalyse(int $entrepriseId, ?int $posteId, bool $nonValidesUniquement)
    {
        $limiteNonValide = now()->subDays(30);

        $query = Cv::with(['poste', 'entreprise'])
            ->where('entreprise_id', $entrepriseId)
            ->when($posteId, fn ($q) => $q->where('poste_id', $posteId));

        if ($nonValidesUniquement) {
            return $query
                ->where('statut', StatutCv::NonValide)
                ->where('date_depot', '>=', $limiteNonValide)
                ->get();
        }

        return $query
            ->where(function ($q) {
                $q->where('statut', StatutCv::EnCoursAnalyse);

                $q->orWhere(function ($q2) {
                    $q2->where('statut', StatutCv::CvRecu)
                        ->where(function ($q3) {
                            $q3->whereNull('modifiable_jusqu')
                                ->orWhere('modifiable_jusqu', '<=', now());
                        });
                });
            })
            ->get();
    }

    private function annulerAnalyseProvisoire(Request $request, int $entrepriseId): void
    {
        $data = $this->sessionAnalyse($request);
        if (! $data || empty($data['cv_ids'])) {
            $this->oublierSessionAnalyse($request);

            return;
        }

        $statutsInitiaux = $data['statuts_initiaux'] ?? [];

        Cv::where('entreprise_id', $entrepriseId)
            ->whereIn('id', $data['cv_ids'])
            ->where('statut', StatutCv::EnCoursAnalyse)
            ->get()
            ->each(function (Cv $cv) use ($statutsInitiaux) {
                $cv->resultatAnalyse()?->delete();
                $initial = StatutCv::tryFrom($statutsInitiaux[$cv->id] ?? '');
                $cv->update([
                    'statut' => $initial && $initial !== StatutCv::EnCoursAnalyse
                        ? $initial
                        : StatutCv::CvRecu,
                ]);
            });

        $this->oublierSessionAnalyse($request);
    }

    private function enregistrerMotsCles(Request $request, array $mots, ?int $posteId): void
    {
        $rhId = $request->user()->id;
        $eid = $this->entrepriseId($request);

        $posteIds = [];
        if ($posteId) {
            $poste = Poste::where('id', $posteId)->where('entreprise_id', $eid)->first();
            if ($poste) {
                $posteIds = [$poste->id];
            }
        }

        foreach ($mots as $valeur) {
            $valeur = mb_strtolower(trim($valeur));
            if ($valeur === '') {
                continue;
            }

            $motCle = MotCle::firstOrCreate(
                ['user_id' => $rhId, 'valeur' => $valeur],
                ['date_creation' => now()],
            );

            if ($posteIds !== []) {
                $motCle->postes()->syncWithoutDetaching($posteIds);
            }
        }
    }
}
