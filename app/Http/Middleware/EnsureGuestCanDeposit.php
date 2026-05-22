<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGuestCanDeposit
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            return $next($request);
        }

        $count = (int) $request->session()->get('guest_depot_count', 0);
        $max = (int) config('cv.guest_max_depots_session', 5);

        if ($count >= $max) {
            return back()->withErrors([
                'depot' => 'Vous avez atteint le nombre maximum de dépôts pour cette session. Créez un compte ou réessayez plus tard.',
            ]);
        }

        return $next($request);
    }
}
