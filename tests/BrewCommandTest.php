<?php

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // Set up a test token
    Config::set('laravel-brew.token', 'test_token_123');
});

it('can list products', function () {
    Http::fake([
        '*/product' => Http::response([
            'data' => [
                [
                    'id' => 'prd_test123',
                    'name' => 'Test Coffee',
                    'description' => 'A delicious test coffee',
                    'variants' => [
                        [
                            'id' => 'var_test123',
                            'name' => '12oz',
                            'price' => 2200,
                        ],
                    ],
                    'subscription' => 'allowed',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('brew', ['action' => 'list'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();
});

it('can order a product', function () {
    Http::fake([
        '*/cart' => Http::response(['success' => true], 200),
        '*/address' => Http::response([
            'data' => [
                [
                    'id' => 'shp_test123',
                    'name' => 'John Doe',
                    'street1' => '123 Test St',
                    'city' => 'Test City',
                    'country' => 'US',
                ],
            ],
        ], 200),
        '*/card' => Http::response([
            'data' => [
                [
                    'id' => 'crd_test123',
                    'brand' => 'Visa',
                    'last4' => '4242',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('brew', [
        'action' => 'order',
        '--variant' => 'var_test123',
        '--quantity' => '2',
    ])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->expectsConfirmation('Would you like to checkout now?', 'no')
        ->assertSuccessful();
});

it('shows cart contents', function () {
    Http::fake([
        '*/cart' => Http::response([
            'items' => [
                [
                    'id' => 'itm_test123',
                    'productVariantID' => 'var_test123',
                    'quantity' => 2,
                    'subtotal' => 4400,
                ],
            ],
            'subtotal' => 4400,
            'shipping' => [
                'service' => 'USPS Ground',
                'timeframe' => '3-5 days',
            ],
        ], 200),
    ]);

    $this->artisan('brew', ['action' => 'cart'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();
});

it('shows empty cart message', function () {
    Http::fake([
        '*/cart' => Http::response(['items' => []], 200),
    ]);

    $this->artisan('brew', ['action' => 'cart'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();
});

it('shows order status', function () {
    Http::fake([
        '*/order' => Http::response([
            'data' => [
                [
                    'id' => 'ord_test123',
                    'index' => 0,
                    'created' => '2024-01-01T10:00:00Z',
                    'tracking' => [
                        'status' => 'DELIVERED',
                        'number' => '1234567890',
                        'statusDetails' => 'Delivered to mailbox',
                    ],
                ],
            ],
        ], 200),
    ]);

    $this->artisan('brew', ['action' => 'status'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();
});

it('shows user profile', function () {
    Http::fake([
        '*/profile' => Http::response([
            'user' => [
                'id' => 'usr_test123',
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'fingerprint' => '123456-7890',
            ],
        ], 200),
    ]);

    $this->artisan('brew', ['action' => 'profile'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();
});

it('creates a subscription', function () {
    Http::fake([
        '*/cart' => Http::response(['success' => true], 200),
        '*/subscription' => Http::response([
            'id' => 'sub_test123',
            'productVariantID' => 'var_test123',
            'schedule' => [
                'type' => 'weekly',
                'interval' => 2,
            ],
        ], 200),
        '*/address' => Http::response([
            'data' => [
                [
                    'id' => 'shp_test123',
                    'name' => 'John Doe',
                    'street1' => '123 Test St',
                    'city' => 'Test City',
                    'country' => 'US',
                ],
            ],
        ], 200),
        '*/card' => Http::response([
            'data' => [
                [
                    'id' => 'crd_test123',
                    'brand' => 'Visa',
                    'last4' => '4242',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('brew', [
        'action' => 'order',
        '--variant' => 'var_test123',
        '--subscribe' => true,
        '--interval' => '2',
        '--address' => 'shp_test123',
        '--card' => 'crd_test123',
    ])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->expectsConfirmation('Would you like to checkout now?', 'no')
        ->assertSuccessful();
});

it('shows help information', function () {
    $this->artisan('brew', ['action' => 'help'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->expectsOutputToContain('Available Actions:')
        ->expectsOutputToContain('Examples:')
        ->assertSuccessful();
});

it('requires variant id for ordering', function () {
    $this->artisan('brew', ['action' => 'order'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->expectsOutput('Please specify a variant ID with --variant=')
        ->assertFailed();
});

it('uses sandbox mode', function () {
    Http::fake([
        'https://api.dev.terminal.shop/product' => Http::response([
            'data' => [],
        ], 200),
    ]);

    $this->artisan('brew', [
        'action' => 'list',
        '--sandbox' => true,
    ])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return str_starts_with($request->url(), 'https://api.dev.terminal.shop');
    });
});

it('fails without api token', function () {
    Config::set('laravel-brew.token', null);

    $this->artisan('brew', ['action' => 'list'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->expectsOutputToContain('Error: No API token provided')
        ->assertFailed();
});

it('handles api errors gracefully', function () {
    Http::fake([
        '*/product' => Http::response([
            'type' => 'authentication',
            'code' => 'unauthorized',
            'message' => 'Invalid token',
        ], 401),
    ]);

    $this->artisan('brew', ['action' => 'list'])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->expectsOutputToContain('Error: Invalid token')
        ->assertFailed();
});

it('defaults to list action when no action specified', function () {
    Http::fake([
        '*/product' => Http::response(['data' => []], 200),
    ]);

    $this->artisan('brew')
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();
});

it('uses custom token from command line', function () {
    Config::set('laravel-brew.token', null);

    Http::fake([
        '*/product' => Http::response(['data' => []], 200),
    ]);

    $this->artisan('brew', [
        'action' => 'list',
        '--token' => 'custom_token_456',
    ])
        ->expectsOutput('☕ Laravel Brew - Terminal Shop Integration')
        ->assertSuccessful();

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer custom_token_456');
    });
});
