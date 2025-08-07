# Laravel Brew - Terminal Shop Integration ☕

[![Latest Version on Packagist](https://img.shields.io/packagist/v/coding-wisely/laravel-brew.svg?style=flat-square)](https://packagist.org/packages/coding-wisely/laravel-brew)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/coding-wisely/laravel-brew/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/coding-wisely/laravel-brew/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/coding-wisely/laravel-brew/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/coding-wisely/laravel-brew/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/coding-wisely/laravel-brew.svg?style=flat-square)](https://packagist.org/packages/coding-wisely/laravel-brew)

Order coffee directly from your Laravel terminal! This package integrates with the Terminal Shop API, allowing developers to order coffee, manage subscriptions, and track orders without leaving their development environment.

Because developers run on coffee ☕

## Support us

[<img src="https://github-ads.s3.eu-central-1.amazonaws.com/laravel-brew.jpg?t=1" width="419px" />](https://spatie.be/github-ad-click/laravel-brew)

We invest a lot of resources into creating [best in class open source packages](https://spatie.be/open-source). You can support us by [buying one of our paid products](https://spatie.be/open-source/support-us).

We highly appreciate you sending us a postcard from your hometown, mentioning which of our package(s) you are using. You'll find our address on [our contact page](https://spatie.be/about-us). We publish all received postcards on [our virtual postcard wall](https://spatie.be/open-source/postcards).

## Requirements

- PHP 8.2+
- Laravel 11.0+
- Terminal Shop API Token (get one at https://terminal.shop)

## Installation

You can install the package via composer:

```bash
composer require coding-wisely/laravel-brew
```

Publish the config file:

```bash
php artisan vendor:publish --tag="laravel-brew-config"
```

Add your Terminal Shop API token to your `.env` file:

```env
TERMINAL_SHOP_TOKEN=your_api_token_here
```

## Configuration

The published config file `config/laravel-brew.php` contains:

```php
return [
    'token' => env('TERMINAL_SHOP_TOKEN'),
    'sandbox' => env('TERMINAL_SHOP_SANDBOX', false),
    'default_subscription_interval' => 2,
    'cache_ttl' => 300,
];
```

## Usage

### List Available Coffee Products

```bash
php artisan brew
# or
php artisan brew list
```

### Order Coffee

```bash
# Order a specific variant
php artisan brew order --variant=var_XXXXXXXXX --quantity=2

# Order with a subscription
php artisan brew order --variant=var_XXXXXXXXX --subscribe --interval=3

# Order with specific address and card
php artisan brew order --variant=var_XXXXXXXXX --address=shp_XXXXXXXXX --card=crd_XXXXXXXXX
```

### View Shopping Cart

```bash
php artisan brew cart
```

### Check Order Status

```bash
php artisan brew status
```

### View Profile

```bash
php artisan brew profile
```

### Manage Addresses

```bash
php artisan brew addresses
```

### Manage Payment Methods

```bash
php artisan brew cards
```

### Manage Subscriptions

```bash
php artisan brew subscriptions
```

### Get Help

```bash
php artisan brew help
```

## Command Options

- `--token=` - Your Terminal Shop API token (overrides .env setting)
- `--sandbox` - Use sandbox environment for testing
- `--product=` - Product ID
- `--variant=` - Product variant ID (required for ordering)
- `--quantity=` - Quantity to order (default: 1)
- `--subscribe` - Subscribe to the product
- `--interval=` - Subscription interval in weeks (for weekly subscriptions)
- `--address=` - Address ID for shipping
- `--card=` - Card ID for payment

## Examples

### Quick Coffee Order

```bash
# List products first
php artisan brew

# Order your favorite variant
php artisan brew order --variant=var_Colombia12oz --quantity=2
```

### Set Up a Coffee Subscription

```bash
# Subscribe to coffee every 2 weeks
php artisan brew order --variant=var_Ethiopia12oz --subscribe --interval=2
```

### Use Sandbox Mode for Testing

```bash
php artisan brew list --sandbox --token=test_token_123
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Vladimir Nikolic](https://github.com/coding-wisely)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
