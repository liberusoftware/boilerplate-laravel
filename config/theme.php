<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | This option controls the default theme for the application.
    | Supported: "light", "dark", "auto"
    |
    */

    'default' => env('THEME_DEFAULT', 'auto'),

    /*
    |--------------------------------------------------------------------------
    | Available Themes
    |--------------------------------------------------------------------------
    |
    | The themes available for users to choose from.
    |
    */

    'available' => [
        'light' => 'Light Mode',
        'dark' => 'Dark Mode',
        'auto' => 'System Default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Colors
    |--------------------------------------------------------------------------
    |
    | Custom color palette for themes. These can be overridden per theme.
    |
    */

    'colors' => [
        'primary' => env('THEME_PRIMARY_COLOR', 'gray'),
        'secondary' => env('THEME_SECONDARY_COLOR', 'slate'),
        'success' => env('THEME_SUCCESS_COLOR', 'green'),
        'danger' => env('THEME_DANGER_COLOR', 'red'),
        'warning' => env('THEME_WARNING_COLOR', 'yellow'),
        'info' => env('THEME_INFO_COLOR', 'blue'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Persistence
    |--------------------------------------------------------------------------
    |
    | Determines whether theme preferences should be saved to the database
    | or stored in session only.
    |
    */

    'persist' => env('THEME_PERSIST', true),

    /*
    |--------------------------------------------------------------------------
    | Session Key
    |--------------------------------------------------------------------------
    |
    | The session key used to store theme preference if not persisting to DB.
    |
    */

    'session_key' => 'theme_preference',

];
