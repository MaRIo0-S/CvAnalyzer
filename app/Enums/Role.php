<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case SousAdmin = 'sous_admin';
    case Candidat = 'candidat';
}
