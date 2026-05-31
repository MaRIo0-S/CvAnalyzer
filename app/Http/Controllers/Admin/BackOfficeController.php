<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Cv;
use App\Models\Entreprise;
use App\Models\MessageContact;
use App\Models\Poste;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class BackOfficeController extends Controller
{
    public function index(Request $request)
    {
        $adminId = $request->user()->id;

        $entreprises = Entreprise::withCount('sousAdmins')
            ->with([
                'sousAdmins' => fn ($q) => $q
                    ->where('admin_id', $adminId)
                    ->select('id', 'name', 'email', 'entreprise_id'),
            ])
            ->orderBy('nom')
            ->get()
            ->map(fn (Entreprise $e) => [
                'id' => $e->id,
                'nom' => $e->nom,
                'rh_count' => $e->sous_admins_count,
                'rh' => $e->sousAdmins->map(fn ($u) => [
                    'name' => $u->name,
                    'email' => $u->email,
                ])->values()->all(),
            ]);

        return Inertia::render('Admin/BackOffice', [
            'stats' => [
                'entreprises' => Entreprise::count(),
                'rh' => User::where('role', Role::SousAdmin)
                    ->where('admin_id', $adminId)
                    ->count(),
                'candidats' => User::where('role', Role::Candidat)->count(),
                'cvs' => Cv::count(),
                'postes_ouverts' => Poste::where('est_ouvert', true)->count(),
                'sessions' => count($this->sessionsRhActives()),
                'session_minutes' => (int) config('session.lifetime', 120),
                'messages_contact' => MessageContact::count(),
                'messages_contact_non_lus' => MessageContact::where('lu', false)->count(),
            ],
            'entreprises' => $entreprises,
            'sessions' => $this->sessionsRhActives(),
        ]);
    }

    private function sessionsRhActives(): array
    {
        $depuis = time() - (config('session.lifetime', 120) * 60);

        return DB::table('sessions')
            ->join('users', 'sessions.user_id', '=', 'users.id')
            ->leftJoin('entreprises', 'users.entreprise_id', '=', 'entreprises.id')
            ->where('sessions.last_activity', '>=', $depuis)
            ->where('users.role', Role::SousAdmin->value)
            ->orderByDesc('sessions.last_activity')
            ->get([
                'users.name',
                'entreprises.nom as entreprise',
                'sessions.last_activity',
            ])
            ->map(fn ($row) => [
                'name' => $row->name,
                'entreprise' => $row->entreprise ?? '—',
                'derniere_activite' => date('d/m/Y H:i', (int) $row->last_activity),
            ])
            ->values()
            ->all();
    }
}
