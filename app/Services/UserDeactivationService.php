<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Poste;
use App\Models\User;

class UserDeactivationService
{
    /**
     * Désactive le gérant, ses RH et ferme leurs postes en mémorisant l'état initial.
     */
    public function deactivateGerant(User $gerant): void
    {
        if ($gerant->role !== Role::Admin) {
            return;
        }

        $rhUsers = User::query()
            ->where('role', Role::SousAdmin)
            ->where('super_admin_id', $gerant->id)
            ->get();

        $rhIds = $rhUsers->pluck('id');
        $postes = $rhIds->isEmpty()
            ? collect()
            : Poste::query()->whereIn('user_id', $rhIds)->get();

        $gerant->etat_cascade_snapshot = [
            'rhs' => $rhUsers->mapWithKeys(fn (User $r) => [(string) $r->id => $r->est_actif])->all(),
            'postes' => $postes->mapWithKeys(fn (Poste $p) => [(string) $p->id => $p->est_ouvert])->all(),
        ];
        $gerant->save();

        if ($rhIds->isNotEmpty()) {
            User::query()->whereIn('id', $rhIds)->update(['est_actif' => false]);
            Poste::query()->whereIn('user_id', $rhIds)->update(['est_ouvert' => false]);
        }
    }

    /**
     * Restaure RH et postes à l'état mémorisé avant la désactivation du gérant.
     */
    public function reactivateGerant(User $gerant): void
    {
        if ($gerant->role !== Role::Admin) {
            return;
        }

        $snapshot = $gerant->etat_cascade_snapshot;
        if (! is_array($snapshot)) {
            return;
        }

        foreach ($snapshot['rhs'] ?? [] as $rhId => $wasActive) {
            if ($wasActive) {
                User::query()->where('id', (int) $rhId)->update(['est_actif' => true]);
            }
        }

        foreach ($snapshot['postes'] ?? [] as $posteId => $wasOpen) {
            Poste::query()->where('id', (int) $posteId)->update(['est_ouvert' => (bool) $wasOpen]);
        }

        $gerant->etat_cascade_snapshot = null;
        $gerant->save();
    }

    /**
     * Désactive un RH et ferme ses postes en mémorisant leur état d'ouverture.
     */
    public function deactivateRh(User $rh): void
    {
        if ($rh->role !== Role::SousAdmin) {
            return;
        }

        $postes = Poste::query()->where('user_id', $rh->id)->get();

        $rh->etat_cascade_snapshot = [
            'postes' => $postes->mapWithKeys(fn (Poste $p) => [(string) $p->id => $p->est_ouvert])->all(),
        ];
        $rh->save();

        Poste::query()->where('user_id', $rh->id)->update(['est_ouvert' => false]);
    }

    /**
     * Réactive les postes du RH selon l'état mémorisé (les postes fermés manuellement restent fermés).
     */
    public function reactivateRh(User $rh): void
    {
        if ($rh->role !== Role::SousAdmin) {
            return;
        }

        $snapshot = $rh->etat_cascade_snapshot;
        if (! is_array($snapshot)) {
            return;
        }

        foreach ($snapshot['postes'] ?? [] as $posteId => $wasOpen) {
            Poste::query()->where('id', (int) $posteId)->update(['est_ouvert' => (bool) $wasOpen]);
        }

        $rh->etat_cascade_snapshot = null;
        $rh->save();
    }
}
