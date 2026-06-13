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

];
