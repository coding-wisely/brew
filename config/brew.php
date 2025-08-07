<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Terminal Shop API Token
    |--------------------------------------------------------------------------
    |
    | Your Terminal Shop API token for authentication.
    | Get your token from: https://terminal.shop
    |
    */
    'token' => env('TERMINAL_SHOP_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Use Sandbox Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, all requests will be sent to the Terminal Shop sandbox API
    | instead of the production API. Useful for testing.
    |
    */
    'sandbox' => env('TERMINAL_SHOP_SANDBOX', false),
];
