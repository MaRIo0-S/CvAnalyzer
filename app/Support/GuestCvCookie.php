<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class GuestCvCookie
{
    public const NAME = 'guest_cv_ref';

    public static function remember(int $cvId): void
    {
        Cookie::queue(cookie(
            self::NAME,
            encrypt((string) $cvId),
            60 * 48,
            '/',
            null,
            null,
            true,
            false,
            'lax'
        ));
    }

    public static function id(Request $request): ?int
    {
        if ($request->session()->has('guest_cv_id')) {
            return (int) $request->session()->get('guest_cv_id');
        }

        $raw = $request->cookie(self::NAME);
        if (! $raw) {
            return null;
        }

        try {
            return (int) decrypt($raw);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function syncSession(Request $request): void
    {
        if ($request->user() || $request->session()->has('guest_cv_id')) {
            return;
        }

        $id = self::id($request);
        if ($id) {
            $request->session()->put('guest_cv_id', $id);
        }
    }

    public static function forget(): void
    {
        Cookie::queue(Cookie::forget(self::NAME));
    }
}
