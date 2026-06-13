<?php

namespace App\Enums;

/**
 * Hiérarchie métier (valeur users.role) :
 * - super_admin : super administrateur plateforme
 * - admin       : gérant d'entreprise
 * - sous_admin  : RH
 * - candidat    : candidat connecté
 */
enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case SousAdmin = 'sous_admin';
    case Candidat = 'candidat';

    public function loginRoute(): string
    {
        return match ($this) {
            self::SuperAdmin => 'login.super-admin',
            self::Admin => 'login.gerant',
            self::SousAdmin, self::Candidat => 'login',
        };
    }
}
