<?php

namespace App\Http\Controllers\Rh\Concerns;

use Illuminate\Http\Request;

trait GereSessionAnalyseRh
{
    protected const SESSION_ANALYSE = 'rh_derniere_analyse';

    protected function sessionAnalyse(Request $request): ?array
    {
        $data = $request->session()->get(self::SESSION_ANALYSE);

        return is_array($data) ? $data : null;
    }

    protected function lotAnalyseEnAttente(Request $request): bool
    {
        $data = $this->sessionAnalyse($request);

        return $data && ($data['en_attente_confirmation'] ?? false);
    }

    protected function cvDansLotAnalyse(Request $request, int $cvId): bool
    {
        if (! $this->lotAnalyseEnAttente($request)) {
            return false;
        }

        return in_array($cvId, $this->sessionAnalyse($request)['cv_ids'] ?? [], true);
    }

    protected function decisionProvisoire(Request $request, int $cvId): ?string
    {
        $data = $this->sessionAnalyse($request);

        return $data['decisions'][$cvId] ?? null;
    }

    protected function enregistrerDecisionProvisoire(Request $request, int $cvId, string $decision): void
    {
        $data = $this->sessionAnalyse($request);
        if (! $data) {
            return;
        }

        $data['decisions'] = $data['decisions'] ?? [];
        $data['decisions'][$cvId] = $decision;
        $request->session()->put(self::SESSION_ANALYSE, $data);
    }

    protected function retirerDecisionProvisoire(Request $request, int $cvId): void
    {
        $data = $this->sessionAnalyse($request);
        if (! $data) {
            return;
        }

        $decisions = $data['decisions'] ?? [];
        unset($decisions[$cvId]);
        $data['decisions'] = $decisions;
        $request->session()->put(self::SESSION_ANALYSE, $data);
    }

    protected function oublierSessionAnalyse(Request $request): void
    {
        $request->session()->forget(self::SESSION_ANALYSE);
    }
}
