<?php

namespace App\Http\Controllers\Rh\Concerns;

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
}
