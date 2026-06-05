<?php

namespace App\Support;

use App\Enums\Role;
use App\Enums\StatutCv;
use App\Models\Cv;
use Illuminate\Http\Request;

class CandidatureSession
{
    public static function enCours(Request $request): ?array
    {
        $cv = null;

        if ($user = $request->user()) {
            if ($user->role !== Role::Candidat) {
                return null;
            }
            $cv = $user->cvs()
                ->where('statut', StatutCv::CvRecu)
                ->orderByDesc('date_depot')
                ->first();
        } elseif ($request->session()->has('guest_cv_id')) {
            $cv = Cv::find($request->session()->get('guest_cv_id'));
        }

        if (! $cv || ! $cv->peutModifier() || $cv->statut !== StatutCv::CvRecu) {
            return null;
        }

        if ($user && $cv->user_id !== $user->id) {
            return null;
        }

        if (! $user && (int) $request->session()->get('guest_cv_id') !== $cv->id) {
            return null;
        }

        return [
            'id' => $cv->id,
            'numero_dossier' => $cv->id,
            'url' => route('guest.deposer'),
            'poste' => $cv->poste?->titre,
            'entreprise' => $cv->entreprise?->nom,
            'modifiable_jusqu' => $cv->modifiable_jusqu?->format('d/m/Y à H:i'),
        ];
    }
}
