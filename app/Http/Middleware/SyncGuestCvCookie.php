<?php

namespace App\Http\Middleware;

use App\Support\GuestCvCookie;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncGuestCvCookie
{
    public function handle(Request $request, Closure $next): Response
    {
        GuestCvCookie::syncSession($request);

        return $next($request);
    }
}
