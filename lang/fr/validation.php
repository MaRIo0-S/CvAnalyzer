<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'max' => [
        'string' => 'Le champ :attribute ne doit pas dépasser :max caractères.',
        'file' => 'Le fichier :attribute ne doit pas dépasser :max kilo-octets.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
    ],
    'confirmed' => 'La confirmation du champ :attribute ne correspond pas.',
    'unique' => 'Cette valeur de :attribute est déjà utilisée.',
    'exists' => 'La valeur sélectionnée pour :attribute est invalide.',

    'attributes' => [
        'name' => 'nom',
        'email' => 'e-mail',
        'password' => 'mot de passe',
        'nom_candidat' => 'nom complet',
        'email_candidat' => 'e-mail de contact',
        'poste_id' => 'poste',
        'entreprise_id' => 'entreprise',
        'fichier' => 'CV',
        'code' => 'code de confirmation',
        'current_password' => 'mot de passe actuel',
    ],
];
