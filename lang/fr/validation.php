<?php

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'email' => 'Le :attribute doit être une adresse courriel valide.',
    'min' => [
        'string' => 'Le texte :attribute doit contenir au moins :min caractères.',
    ],
    'max' => [
        'string' => 'Le :attribute ne peut pas dépasser :max caractères.',
    ],
    'confirmed' => 'La confirmation de :attribute ne correspond pas.',
    'unique' => ':attribute est déjà pris.',
    'attributes' => [
        'email' => 'adresse e-mail',
        'password' => 'mot de passe',
        'name' => 'name',
    ],
];
