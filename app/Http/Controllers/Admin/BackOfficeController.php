<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\MessageContact;
use App\Models\Poste;
use App\Models\User;
use App\Support\ExcelExporter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackOfficeController extends Controller
{
    public function index(Request $request)
    {
        $adminId = $request->user()->id;

        $lignes = $this->lignesOrganisation($adminId);

        return Inertia::render('Admin/BackOffice', [
            'stats' => [
                'entreprises' => Entreprise::count(),
                'gerants' => User::where('role', Role::Admin)->where('admin_id', $adminId)->count(),
                'rh' => User::where('role', Role::SousAdmin)
                    ->whereHas('gerant', fn ($q) => $q->where('admin_id', $adminId))
                    ->count(),
                'candidats' => User::where('role', Role::Candidat)->count(),
                'cvs' => Cv::count(),
                'postes_ouverts' => Poste::where('est_ouvert', true)->count(),
                'sessions_rh' => count($this->sessionsActives(Role::SousAdmin)),
                'sessions_gerants' => count($this->sessionsActives(Role::Admin)),
                'session_minutes' => (int) config('session.lifetime', 120),
                'messages_contact' => MessageContact::count(),
                'messages_contact_non_lus' => MessageContact::where('lu', false)->count(),
            ],
            'lignes' => $lignes,
            'sessionsRh' => $this->sessionsActives(Role::SousAdmin),
            'sessionsGerants' => $this->sessionsActives(Role::Admin),
        ]);
    }

    public function exportExcel(Request $request): StreamedResponse
    {
        $adminId = $request->user()->id;
        $lignes = $this->lignesOrganisation($adminId);

        $rows = [];
        foreach ($lignes as $l) {
            $rows[] = [
                $l['entreprise'],
                $l['gerant_nom'],
                $l['gerant_email'],
                $l['gerant_telephone'],
                $l['gerant_actif'] ? 'oui' : 'non',
                $l['rh_nom'],
                $l['rh_email'],
                $l['rh_telephone'],
                $l['rh_actif'] ? 'oui' : 'non',
            ];
        }

        $indicateurs = [
            ['Entreprises', (string) Entreprise::count()],
            ['Gérants', (string) User::where('role', Role::Admin)->where('admin_id', $adminId)->count()],
            ['RH', (string) User::where('role', Role::SousAdmin)
                ->whereHas('gerant', fn ($q) => $q->where('admin_id', $adminId))
                ->count()],
            ['CV déposés (total)', (string) Cv::count()],
            ['Messages contact non lus', (string) MessageContact::where('lu', false)->count()],
        ];

        return ExcelExporter::download('back-office.xlsx', [
            [
                'name' => 'Synthèse',
                'headers' => ['Indicateur', 'Valeur'],
                'rows' => $indicateurs,
            ],
            [
                'name' => 'Entreprises gérants RH',
                'headers' => [
                    'Entreprise',
                    'Gérant nom',
                    'Gérant email',
                    'Gérant téléphone',
                    'Gérant actif',
                    'RH nom',
                    'RH email',
                    'RH téléphone',
                    'RH actif',
                ],
                'rows' => $rows,
            ],
        ]);
    }

    private function lignesOrganisation(int $adminId): array
    {
        $gerants = User::query()
            ->where('role', Role::Admin)
            ->where('admin_id', $adminId)
            ->with(['entreprise', 'rhEquipe'])
            ->orderBy('name')
            ->get();

        $lignes = [];
        foreach ($gerants as $gerant) {
            $nomEntreprise = $gerant->entreprise?->nom ?? '—';
            $rhs = $gerant->rhEquipe;

            if ($rhs->isEmpty()) {
                $lignes[] = $this->ligne($nomEntreprise, $gerant, null);

                continue;
            }

            foreach ($rhs as $rh) {
                $lignes[] = $this->ligne($nomEntreprise, $gerant, $rh);
            }
        }

        return $lignes;
    }

    private function ligne(string $entreprise, ?User $gerant, ?User $rh): array
    {
        return [
            'entreprise' => $entreprise,
            'gerant_id' => $gerant?->id,
            'gerant_nom' => $gerant?->name ?? '—',
            'gerant_email' => $gerant?->email ?? '—',
            'gerant_telephone' => $gerant?->telephone ?? '—',
            'gerant_actif' => $gerant?->est_actif ?? false,
            'rh_id' => $rh?->id,
            'rh_nom' => $rh?->name ?? '—',
            'rh_email' => $rh?->email ?? '—',
            'rh_telephone' => $rh?->telephone ?? '—',
            'rh_actif' => $rh?->est_actif ?? false,
        ];
    }

    private function sessionsActives(Role $role): array
    {
        $depuis = time() - (config('session.lifetime', 120) * 60);

        return DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->leftJoin('entreprises', 'users.entreprise_id', '=', 'entreprises.id')
            ->where('sessions.last_activity', '>=', $depuis)
            ->where('users.role', $role->value)
            ->orderByDesc('sessions.last_activity')
            ->get([
                'users.id as user_id',
                'users.name',
                'users.email',
                'entreprises.nom as entreprise',
                'sessions.last_activity',
            ])
            ->map(fn ($row) => [
                'user_id' => $row->user_id,
                'name' => $row->name,
                'email' => $row->email,
                'entreprise' => $row->entreprise ?? '—',
                'derniere_activite' => date('d/m/Y H:i', (int) $row->last_activity),
            ])
            ->values()
            ->all();
    }
}
