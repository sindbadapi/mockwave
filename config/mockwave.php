<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Admin Credentials
    |--------------------------------------------------------------------------
    | Used by the database seeder to create the initial admin user.
    | Reading env() here (inside a config file) is safe with config:cache.
    */
    'admin' => [
        'email' => env('ADMIN_EMAIL', 'admin@mockwave.local'),
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Features
    |--------------------------------------------------------------------------
    | Registration and password recovery are opt-in. Disabled endpoints return
    | 404 for both page requests and form submissions.
    */
    'auth' => [
        'registration' => env('AUTH_REGISTRATION_ENABLED', false),
        'password_reset' => env('AUTH_PASSWORD_RESET_ENABLED', false),
    ],

];
