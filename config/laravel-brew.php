<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Terminal Shop API Token
    |--------------------------------------------------------------------------
    |
    | This is your Terminal Shop API token. You can get one by signing up
    | at https://terminal.shop and creating a personal access token.
    |
    */
    'token' => env('TERMINAL_SHOP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Use Sandbox Environment
    |--------------------------------------------------------------------------
    |
    | Set this to true to use the Terminal Shop sandbox environment for testing.
    | This will use the dev API endpoint instead of production.
    |
    */
    'sandbox' => env('TERMINAL_SHOP_SANDBOX', false),

    /*
    |--------------------------------------------------------------------------
    | Default Subscription Interval
    |--------------------------------------------------------------------------
    |
    | The default interval (in weeks) for coffee subscriptions when
    | the --interval option is not specified.
    |
    */
    'default_subscription_interval' => 2,

    /*
    |--------------------------------------------------------------------------
    | Cache TTL
    |--------------------------------------------------------------------------
    |
    | How long (in seconds) to cache API responses. Set to 0 to disable caching.
    |
    */
    'cache_ttl' => 300, // 5 minutes
];
