<?php

return [
    /*
    | Chemins de connexion staff (non listés sur le site public).
    | À définir dans .env — ne pas exposer aux candidats.
    */
    'admin_login_path' => env('ADMIN_LOGIN_PATH', 'acces-admin-plateforme'),
    'gerant_login_path' => env('GERANT_LOGIN_PATH', 'acces-gerant-entreprise'),

    /*
    | Préfixes des espaces applicatifs (après connexion).
    | Distincts des URLs de login pour éviter les accès directs /super-admin ou /admin.
    */
    'admin_app_prefix' => env('ADMIN_APP_PREFIX', 'espace-admin'),
    'gerant_app_prefix' => env('GERANT_APP_PREFIX', 'espace-gerant'),
];
