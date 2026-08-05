<?php

return [
    'registration' => env('IDENTITY_REGISTRATION', 'open'),
    'require_verified_email' => true,
    'recent_auth_seconds' => 10800,
];
