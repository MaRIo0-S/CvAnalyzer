<?php

namespace App\Support;

use App\Models\Poste;
use Illuminate\Http\Request;

class DepotOffreSession
{
    public const KEY = 'depot_offre';

    public static function autoriser(Poste $poste): void
    {
        session([
            self::KEY => [
                'poste_id' => $poste->id,
                'entreprise_id' => $poste->entreprise_id,
                'at' => now()->timestamp,
            ],
        ]);
    }

    public static function estAutorise(Request $request, ?int $posteId = null): bool
    {
        $data = $request->session()->get(self::KEY);
        if (! is_array($data) || empty($data['poste_id'])) {
            return false;
        }

        if ($posteId !== null && (int) $data['poste_id'] !== (int) $posteId) {
            return false;
        }

        return true;
    }

    public static function posteId(Request $request): ?int
    {
        $id = $request->session()->get(self::KEY.'.poste_id');

        return $id ? (int) $id : null;
    }

    public static function entrepriseId(Request $request): ?int
    {
        $id = $request->session()->get(self::KEY.'.entreprise_id');

        return $id ? (int) $id : null;
    }
}
