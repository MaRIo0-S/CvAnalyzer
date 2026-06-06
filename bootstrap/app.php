<?php

use App\Http\Middleware\EnsureGuestCanDeposit;
use App\Http\Middleware\SyncGuestCvCookie;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\EnsureUserActive;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RedirectStaffFromDepot;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->redirectGuestsTo(function (Request $request) {
            $adminPrefix = trim(config('cvanalyzer.admin_app_prefix'), '/');
            $gerantPrefix = trim(config('cvanalyzer.gerant_app_prefix'), '/');

            if ($request->is($adminPrefix, $adminPrefix.'/*')) {
                return route('login.admin');
            }
            if ($request->is($gerantPrefix, $gerantPrefix.'/*')) {
                return route('login.super-admin');
            }

            return route('login');
        });

        $middleware->web(append: [
            SyncGuestCvCookie::class,
            HandleInertiaRequests::class,
        ]);
        $middleware->alias([
            'role' => EnsureRole::class,
            'active' => EnsureUserActive::class,
            'guest.deposit' => EnsureGuestCanDeposit::class,
            'no.staff.depot' => RedirectStaffFromDepot::class,
            'no.rh.self.account' => \App\Http\Middleware\BlockRhSelfServiceAccount::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {})->create();
