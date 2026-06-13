<?php

return [
    /*
    | Rôles (libellé métier → users.role) :
    | - super_admin : super administrateur plateforme
    | - admin       : gérant d'entreprise
    | - sous_admin  : RH
    |
    | Chemins de connexion staff (non listés sur le site public).
    */
    'super_admin_login_path' => env('SUPER_ADMIN_LOGIN_PATH', env('ADMIN_LOGIN_PATH', 'acces-admin-plateforme')),
    'gerant_login_path' => env('GERANT_LOGIN_PATH', 'acces-gerant-entreprise'),

    /*
    | Préfixes des espaces applicatifs (après connexion).
    */
    'admin_app_prefix' => env('ADMIN_APP_PREFIX', 'espace-admin'),
    'gerant_app_prefix' => env('GERANT_APP_PREFIX', 'espace-gerant'),
];
