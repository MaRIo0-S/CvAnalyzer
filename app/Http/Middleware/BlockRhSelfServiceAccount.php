<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockRhSelfServiceAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->role === Role::SousAdmin) {
            abort(403, 'Votre compte est géré par le gérant de votre entreprise.');
        }

        return $next($request);
    }
}
