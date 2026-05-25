<?php

namespace App\Http\Controllers\Rh\Concerns;

use App\Models\Cv;
use App\Models\Poste;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait ResolvesRhEntreprise
{
    protected function entrepriseId(Request $request): int
    {
        $id = $request->user()->entreprise_id;
        if (! $id) {
            abort(403, 'Compte sub-admin non rattaché à une entreprise.');
        }

        return (int) $id;
    }

    protected function rhUserId(Request $request): int
    {
        return (int) $request->user()->id;
    }

    protected function queryPostesRh(Request $request): Builder
    {
        return Poste::query()
            ->where('user_id', $this->rhUserId($request))
            ->where('entreprise_id', $this->entrepriseId($request));
    }

    protected function queryCvsRh(Request $request): Builder
    {
        return Cv::query()->whereHas(
            'poste',
            fn (Builder $q) => $q->where('user_id', $this->rhUserId($request))
        );
    }

    protected function posteAppartientAuRh(Request $request, int $posteId): bool
    {
        return $this->queryPostesRh($request)->where('id', $posteId)->exists();
    }

    protected function authorizeCvDuRh(Request $request, Cv $cv): void
    {
        $cv->loadMissing('poste');

        if (! $cv->poste || $cv->poste->user_id !== $this->rhUserId($request)) {
            abort(403);
        }
    }
}
