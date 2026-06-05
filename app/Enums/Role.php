<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';
    case SousAdmin = 'sous_admin';
    case Candidat = 'candidat';

    public function loginRoute(): string
    {
        return match ($this) {
            self::Admin => 'login.admin',
            self::SuperAdmin => 'login.super-admin',
            self::SousAdmin, self::Candidat => 'login',
        };
    }
}
