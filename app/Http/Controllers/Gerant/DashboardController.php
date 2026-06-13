<?php

namespace App\Http\Controllers\Gerant;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Poste;
use App\Models\User;
use App\Support\ExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $gerant = $request->user();
        $rhIds = $this->rhIdsDuGerant($gerant);

        $rhList = User::where('role', Role::SousAdmin)
            ->where('super_admin_id', $gerant->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'telephone' => $u->telephone ?? '—',
                'est_actif' => $u->est_actif,
                'created_at' => $u->created_at?->format('d/m/Y'),
                'postes_count' => Poste::where('user_id', $u->id)->count(),
            ]);

        $postesQuery = Poste::query()->whereIn('user_id', $rhIds);

        return Inertia::render('Gerant/Dashboard', [
            'entreprise' => $gerant->entreprise?->only(['id', 'nom']),
            'stats' => [
                'rh_total' => $rhList->count(),
                'rh_actifs' => $rhList->where('est_actif', true)->count(),
                'postes_ouverts' => (clone $postesQuery)->where('est_ouvert', true)->count(),
                'postes_total' => (clone $postesQuery)->count(),
                'cvs_recus' => $this->cvsEquipeQuery($rhIds)->count(),
                'sessions_rh' => count($this->sessionsRhActives($gerant->id)),
                'session_minutes' => (int) config('session.lifetime', 120),
            ],
            'rhList' => $rhList->values(),
            'lignesCandidats' => $this->lignesCandidats($rhIds),
            'lignesPostes' => $this->lignesPostes($rhIds),
            'sessionsRh' => $this->sessionsRhActives($gerant->id),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $gerant = $request->user();
        $rhIds = $this->rhIdsDuGerant($gerant);
        $nomEntreprise = $gerant->entreprise?->nom ?? '—';

        $rhRows = User::where('role', Role::SousAdmin)
            ->where('super_admin_id', $gerant->id)
            ->orderBy('name')
            ->get()
            ->map(fn (User $u) => [
                $nomEntreprise,
                $u->name,
                $u->email,
                $u->telephone ?? '—',
                $u->est_actif ? 'oui' : 'non',
                (string) Poste::where('user_id', $u->id)->count(),
                $u->created_at?->format('d/m/Y') ?? '—',
            ])
            ->all();

        $candidatRows = collect($this->lignesCandidats($rhIds))
            ->map(fn (array $l) => [
                (string) $l['numero_dossier'],
                $l['nom'],
                $l['email'],
                $l['poste'],
                $l['rh_nom'],
                $l['statut'],
                $l['date_depot'],
                $l['importe_par_rh'],
                $l['compte_candidat'],
            ])
            ->all();

        $posteRows = collect($this->lignesPostes($rhIds))
            ->map(fn (array $l) => [
                $l['titre'],
                $l['rh_nom'],
                $l['rh_email'],
                $l['est_ouvert'],
                $l['date_creation'],
                (string) $l['cvs_count'],
            ])
            ->all();

        $postesQuery = Poste::query()->whereIn('user_id', $rhIds);

        $indicateurs = [
            ['RH actifs', (string) User::where('role', Role::SousAdmin)->where('super_admin_id', $gerant->id)->where('est_actif', true)->count()],
            ['RH total', (string) count($rhRows)],
            ['Postes ouverts', (string) (clone $postesQuery)->where('est_ouvert', true)->count()],
            ['CV reçus', (string) $this->cvsEquipeQuery($rhIds)->count()],
        ];

        return ExcelExporter::download('back-office-gerant.xlsx', [
            [
                'name' => 'Synthèse',
                'headers' => ['Indicateur', 'Valeur'],
                'rows' => $indicateurs,
            ],
            [
                'name' => 'Équipe RH',
                'headers' => [
                    'Entreprise',
                    'RH nom',
                    'RH email',
                    'RH téléphone',
                    'RH actif',
                    'Postes créés',
                    'Créé le',
                ],
                'rows' => $rhRows,
            ],
            [
                'name' => 'Candidats et CV',
                'headers' => [
                    'N° dossier',
                    'Nom candidat',
                    'E-mail',
                    'Poste',
                    'RH responsable',
                    'Statut',
                    'Date dépôt',
                    'Import RH',
                    'Compte candidat inscrit',
                ],
                'rows' => $candidatRows,
            ],
            [
                'name' => 'Postes',
                'headers' => [
                    'Titre',
                    'RH créateur',
                    'E-mail RH',
                    'Ouvert',
                    'Créé le',
                    'Nb CV',
                ],
                'rows' => $posteRows,
            ],
        ]);
    }

    /** @return array<int, int> */
    private function rhIdsDuGerant(User $gerant): array
    {
        return User::query()
            ->where('role', Role::SousAdmin)
            ->where('super_admin_id', $gerant->id)
            ->pluck('id')
            ->all();
    }

    /** @param  array<int, int>  $rhIds */
    private function cvsEquipeQuery(array $rhIds)
    {
        return Cv::query()->whereHas('poste', fn ($q) => $q->whereIn('user_id', $rhIds));
    }

    /**
     * @param  array<int, int>  $rhIds
     * @return array<int, array<string, mixed>>
     */
    private function lignesCandidats(array $rhIds): array
    {
        if ($rhIds === []) {
            return [];
        }

        return $this->cvsEquipeQuery($rhIds)
            ->with(['poste.createur:id,name,email', 'candidat:id,name,email'])
            ->orderByDesc('date_depot')
            ->get()
            ->map(fn (Cv $cv) => [
                'id' => $cv->id,
                'numero_dossier' => $cv->id,
                'rh_id' => $cv->poste?->createur?->id,
                'nom' => $cv->nom_candidat ?: ('Candidat #'.$cv->id),
                'email' => $cv->emailAffichageRh(),
                'poste' => $cv->poste?->titre ?? '—',
                'rh_nom' => $cv->poste?->createur?->name ?? '—',
                'rh_email' => $cv->poste?->createur?->email ?? '—',
                'statut' => $cv->statut->label(),
                'statut_value' => $cv->statut->value,
                'date_depot' => $cv->date_depot?->format('d/m/Y H:i') ?? '—',
                'date_depot_ts' => $cv->date_depot?->timestamp ?? 0,
                'importe_par_rh' => $cv->importe_par_rh ? 'oui' : 'non',
                'importe_par_rh_bool' => (bool) $cv->importe_par_rh,
                'compte_candidat' => $cv->candidat
                    ? $cv->candidat->name.' ('.$cv->candidat->email.')'
                    : 'Invité ou import RH',
                'candidat_nom' => $cv->candidat?->name ?? '—',
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $rhIds
     * @return array<int, array<string, mixed>>
     */
    private function lignesPostes(array $rhIds): array
    {
        if ($rhIds === []) {
            return [];
        }

        return Poste::query()
            ->whereIn('user_id', $rhIds)
            ->with('createur:id,name,email')
            ->withCount('cvs')
            ->orderBy('titre')
            ->get()
            ->map(fn (Poste $p) => [
                'id' => $p->id,
                'titre' => $p->titre,
                'rh_nom' => $p->createur?->name ?? '—',
                'rh_email' => $p->createur?->email ?? '—',
                'est_ouvert' => $p->est_ouvert ? 'oui' : 'non',
                'est_ouvert_bool' => (bool) $p->est_ouvert,
                'date_creation' => $p->date_creation?->format('d/m/Y') ?? '—',
                'date_creation_ts' => $p->date_creation?->timestamp ?? 0,
                'cvs_count' => $p->cvs_count,
            ])
            ->values()
            ->all();
    }

    private function sessionsRhActives(int $gerantId): array
    {
        $depuis = time() - (config('session.lifetime', 120) * 60);

        return DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->leftJoin('entreprises', 'users.entreprise_id', '=', 'entreprises.id')
            ->where('sessions.last_activity', '>=', $depuis)
            ->where('users.role', Role::SousAdmin->value)
            ->where('users.super_admin_id', $gerantId)
            ->orderByDesc('sessions.last_activity')
            ->get([
                'users.id as user_id',
                'users.name',
                'users.email',
                'users.telephone',
                'entreprises.nom as entreprise',
                'sessions.last_activity',
            ])
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'name' => $row->name,
                'email' => $row->email,
                'telephone' => $row->telephone ?? '—',
                'entreprise' => $row->entreprise ?? '—',
                'derniere_activite' => date('d/m/Y H:i', (int) $row->last_activity),
            ])
            ->values()
            ->all();
    }
}
