<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectStaffFromDepot
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && in_array($user->role, [Role::Admin, Role::SousAdmin], true)) {
            return redirect()->route(
                $user->role === Role::Admin ? 'admin.super-admins' : 'rh.dashboard'
            );
        }

        return $next($request);
    }
}
