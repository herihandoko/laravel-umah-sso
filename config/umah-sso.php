<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Enable / disable SSO
    |--------------------------------------------------------------------------
    */
    'enabled' => env('UMAH_SSO_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Umah auth endpoint
    |--------------------------------------------------------------------------
    | Returns JSON user profile when Banprov session cookies are valid.
    */
    'auth_url' => env('UMAH_AUTH_URL', 'https://layanan.bantenprov.go.id/v2/umah/auth'),

    'timeout' => (int) env('UMAH_AUTH_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Auto-try SSO when opening the login page
    |--------------------------------------------------------------------------
    | Use AttemptsUmahSso on your LoginController, or call UmahSso::attempt().
    */
    'auto_on_login' => env('UMAH_SSO_AUTO_ON_LOGIN', true),

    /*
    |--------------------------------------------------------------------------
    | Browser bridge SSO
    |--------------------------------------------------------------------------
    | When server-side cookie replay to Umah auth fails (session bound to
    | browser), fetch auth JSON in the user's browser then POST it back here.
    */
    'browser_sso' => env('UMAH_SSO_BROWSER', true),

    /*
    |--------------------------------------------------------------------------
    | Pintu Umah portal hostnames
    |--------------------------------------------------------------------------
    | When users return from Pintu Umah to /login, redirect to /sso/umah so
    | browser-bridge SSO runs (same as clicking "Login dengan Pintu Umah").
    */
    'pintu_umah_hosts' => [
        'pintu-umah.bantenprov.go.id',
    ],

    'auth_referer' => env('UMAH_AUTH_REFERER', 'https://layanan.bantenprov.go.id/pemerintahan/'),

    /*
    |--------------------------------------------------------------------------
    | Session key set on local logout to prevent auto-SSO bounce-back
    |--------------------------------------------------------------------------
    */
    'skip_session_key' => 'umah_sso_skip',

    /*
    |--------------------------------------------------------------------------
    | App display name (used in error messages)
    |--------------------------------------------------------------------------
    */
    'app_name' => env('UMAH_SSO_APP_NAME', env('APP_NAME', 'aplikasi')),

    /*
    |--------------------------------------------------------------------------
    | User model & email column
    |--------------------------------------------------------------------------
    | Defaults to the auth provider model (config auth.providers.users.model).
    */
    'user_model' => null,

    'email_column' => 'email',

    /*
    |--------------------------------------------------------------------------
    | JSON keys from Umah auth payload to match against local email
    |--------------------------------------------------------------------------
    | Checked in order; first valid emails are collected then matched.
    */
    'email_keys' => ['BantenMail', 'OtherMail'],

    /*
    |--------------------------------------------------------------------------
    | Auto-provision missing users from Umah auth payload
    |--------------------------------------------------------------------------
    */
    'auto_provision' => env('UMAH_SSO_AUTO_PROVISION', false),

    'provisioner' => env('UMAH_SSO_PROVISIONER'),

    'default_role_id' => (int) env('UMAH_SSO_DEFAULT_ROLE_ID', 0),

    /*
    |--------------------------------------------------------------------------
    | Remember login
    |--------------------------------------------------------------------------
    */
    'remember' => env('UMAH_SSO_REMEMBER', true),

    /*
    |--------------------------------------------------------------------------
    | Session keys to forget before login (e.g. cached profile)
    |--------------------------------------------------------------------------
    */
    'forget_session_keys' => ['user'],

    /*
    |--------------------------------------------------------------------------
    | Redirects & routes
    |--------------------------------------------------------------------------
    */
    'redirect_to' => env('UMAH_SSO_REDIRECT', '/home'),

    'login_route' => 'login',

    'error_key' => 'login_error',

    'register_routes' => env('UMAH_SSO_REGISTER_ROUTES', true),

    'route_path' => 'sso/umah',

    'route_name' => 'sso.umah',

    'complete_route_path' => 'sso/umah/complete',

    'complete_route_name' => 'sso.umah.complete',

    'route_middleware' => ['web', 'guest'],

];
