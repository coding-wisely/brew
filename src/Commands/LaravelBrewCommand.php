<?php

namespace CodingWisely\LaravelBrew\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LaravelBrewCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brew
                            {action? : The action to perform (list, order, cart, status)}
                            {--product= : Product ID to order}
                            {--variant= : Product variant ID}
                            {--quantity=1 : Quantity to order}
                            {--subscribe : Subscribe to the product}
                            {--interval= : Subscription interval in weeks (for weekly subscriptions)}
                            {--address= : Address ID for shipping}
                            {--card= : Card ID for payment}
                            {--token= : Terminal Shop API token}
                            {--sandbox : Use sandbox environment}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Order coffee from Terminal Shop - Because developers run on coffee ☕';

    /**
     * Terminal Shop API base URLs
     */
    protected const API_PRODUCTION = 'https://api.terminal.shop';

    protected const API_SANDBOX = 'https://api.dev.terminal.shop';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action') ?? 'list';

        $this->info('☕ Laravel Brew - Terminal Shop Integration');

        try {
            return match ($action) {
                'list' => $this->listProducts(),
                'order' => $this->orderProduct(),
                'cart' => $this->showCart(),
                'status' => $this->orderStatus(),
                'profile' => $this->showProfile(),
                'addresses' => $this->manageAddresses(),
                'cards' => $this->manageCards(),
                'subscriptions' => $this->manageSubscriptions(),
                default => $this->showHelp(),
            };
        } catch (\Exception $e) {
            $this->error("Error: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * List available coffee products
     */
    protected function listProducts(): int
    {
        $this->components->task('Fetching coffee menu', function () {
            $response = $this->apiRequest('GET', '/product');

            if (! isset($response['data'])) {
                throw new \Exception('Invalid response from API');
            }

            $this->newLine();
            $this->components->info('Available Coffee Products:');
            $this->newLine();

            foreach ($response['data'] as $product) {
                $this->components->twoColumnDetail(
                    "<fg=yellow>{$product['name']}</>",
                    "<fg=gray>{$product['id']}</>"
                );

                $this->line("  <fg=gray>{$product['description']}</>");

                if (! empty($product['variants'])) {
                    $this->line('  <fg=cyan>Variants:</>');
                    foreach ($product['variants'] as $variant) {
                        $price = number_format($variant['price'] / 100, 2);
                        $this->line("    - {$variant['name']}: <fg=green>\${$price}</> <fg=gray>({$variant['id']})</>");
                    }
                }

                if (isset($product['subscription'])) {
                    $this->line("  <fg=magenta>Subscription: {$product['subscription']}</>");
                }

                $this->newLine();
            }

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Order a product
     */
    protected function orderProduct(): int
    {
        $productId = $this->option('product');
        $variantId = $this->option('variant');
        $quantity = (int) $this->option('quantity');
        $subscribe = $this->option('subscribe');

        if (! $variantId) {
            $this->error('Please specify a variant ID with --variant=');

            return self::FAILURE;
        }

        $this->components->task('Adding to cart', function () use ($variantId, $quantity) {
            // Add to cart
            $response = $this->apiRequest('POST', '/cart', [
                'items' => [
                    [
                        'variantId' => $variantId,
                        'quantity' => $quantity,
                    ],
                ],
            ]);

            return true;
        });

        if ($subscribe) {
            $interval = $this->option('interval') ?? 2; // Default 2 weeks
            $this->components->task('Setting up subscription', function () use ($variantId, $quantity, $interval) {
                $response = $this->apiRequest('POST', '/subscription', [
                    'productVariantID' => $variantId,
                    'quantity' => $quantity,
                    'schedule' => [
                        'type' => 'weekly',
                        'interval' => (int) $interval,
                    ],
                    'addressID' => $this->option('address') ?? $this->selectAddress(),
                    'cardID' => $this->option('card') ?? $this->selectCard(),
                ]);

                return true;
            });
        }

        $this->components->success('☕ Added to cart successfully!');

        if ($this->confirm('Would you like to checkout now?')) {
            return $this->checkout();
        }

        return self::SUCCESS;
    }

    /**
     * Show current cart
     */
    protected function showCart(): int
    {
        $this->components->task('Fetching cart', function () {
            $response = $this->apiRequest('GET', '/cart');

            if (empty($response['items'])) {
                $this->components->warn('Your cart is empty');

                return true;
            }

            $this->newLine();
            $this->components->info('Your Cart:');
            $this->newLine();

            $headers = ['Item', 'Variant ID', 'Quantity', 'Subtotal'];
            $rows = [];

            foreach ($response['items'] as $item) {
                $subtotal = number_format($item['subtotal'] / 100, 2);
                $rows[] = [
                    $item['id'],
                    $item['productVariantID'],
                    $item['quantity'],
                    "\${$subtotal}",
                ];
            }

            $this->table($headers, $rows);

            $total = number_format($response['subtotal'] / 100, 2);
            $this->components->twoColumnDetail('Subtotal:', "<fg=green>\${$total}</>");

            if (isset($response['shipping'])) {
                $this->newLine();
                $this->components->info('Shipping Info:');
                $this->components->twoColumnDetail('Service:', $response['shipping']['service']);
                $this->components->twoColumnDetail('Timeframe:', $response['shipping']['timeframe']);
            }

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Show order status
     */
    protected function orderStatus(): int
    {
        $this->components->task('Fetching orders', function () {
            $response = $this->apiRequest('GET', '/order');

            if (empty($response['data'])) {
                $this->components->warn('No orders found');

                return true;
            }

            $this->newLine();
            $this->components->info('Your Orders:');
            $this->newLine();

            foreach ($response['data'] as $order) {
                $this->components->twoColumnDetail(
                    "Order #{$order['index']}",
                    "<fg=gray>{$order['id']}</>"
                );

                $created = date('Y-m-d H:i', strtotime($order['created']));
                $this->line("  Created: <fg=gray>{$created}</>");

                if (isset($order['tracking'])) {
                    $tracking = $order['tracking'];
                    $this->line("  Status: <fg=yellow>{$tracking['status']}</>");
                    if (isset($tracking['number'])) {
                        $this->line("  Tracking: <fg=cyan>{$tracking['number']}</>");
                    }
                    if (isset($tracking['statusDetails'])) {
                        $this->line("  Details: <fg=gray>{$tracking['statusDetails']}</>");
                    }
                }

                $this->newLine();
            }

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Show user profile
     */
    protected function showProfile(): int
    {
        $this->components->task('Fetching profile', function () {
            $response = $this->apiRequest('GET', '/profile');

            $user = $response['user'] ?? null;
            if (! $user) {
                throw new \Exception('Unable to fetch profile');
            }

            $this->newLine();
            $this->components->info('Your Profile:');
            $this->newLine();

            $this->components->twoColumnDetail('ID:', $user['id']);
            $this->components->twoColumnDetail('Name:', $user['name'] ?? 'Not set');
            $this->components->twoColumnDetail('Email:', $user['email'] ?? 'Not set');
            $this->components->twoColumnDetail('Fingerprint:', $user['fingerprint'] ?? 'Not set');

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Checkout current cart
     */
    protected function checkout(): int
    {
        $addressId = $this->option('address') ?? $this->selectAddress();
        $cardId = $this->option('card') ?? $this->selectCard();

        if (! $addressId || ! $cardId) {
            $this->error('Address and card are required for checkout');

            return self::FAILURE;
        }

        $this->components->task('Processing checkout', function () use ($addressId, $cardId) {
            // Update cart with address and card
            $this->apiRequest('PUT', '/cart', [
                'addressID' => $addressId,
                'cardID' => $cardId,
            ]);

            // Create order
            $response = $this->apiRequest('POST', '/order');

            $this->newLine();
            $this->components->success('☕ Order placed successfully!');
            $this->components->info("Order ID: {$response['id']}");

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Make API request
     */
    protected function apiRequest(string $method, string $endpoint, array $data = []): array
    {
        $token = $this->getApiToken();
        $baseUrl = $this->option('sandbox') ? self::API_SANDBOX : self::API_PRODUCTION;

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$token}",
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->$method("{$baseUrl}{$endpoint}", $data);

        if (! $response->successful()) {
            $error = $response->json();
            throw new \Exception($error['message'] ?? 'API request failed');
        }

        return $response->json();
    }

    /**
     * Get API token
     */
    protected function getApiToken(): string
    {
        if ($token = $this->option('token')) {
            return $token;
        }

        if ($token = config('laravel-brew.token')) {
            return $token;
        }

        if ($token = env('TERMINAL_SHOP_TOKEN')) {
            return $token;
        }

        throw new \Exception('No API token provided. Use --token= or set TERMINAL_SHOP_TOKEN in .env');
    }

    /**
     * Select address interactively
     */
    protected function selectAddress(): ?string
    {
        $response = $this->apiRequest('GET', '/address');
        $addresses = $response['data'] ?? [];

        if (empty($addresses)) {
            $this->components->warn('No addresses found. Please add an address first.');

            return null;
        }

        $choices = [];
        foreach ($addresses as $address) {
            $label = "{$address['name']} - {$address['street1']}, {$address['city']}, {$address['country']}";
            $choices[$address['id']] = $label;
        }

        return $this->choice('Select shipping address:', $choices);
    }

    /**
     * Select card interactively
     */
    protected function selectCard(): ?string
    {
        $response = $this->apiRequest('GET', '/card');
        $cards = $response['data'] ?? [];

        if (empty($cards)) {
            $this->components->warn('No cards found. Please add a payment method first.');

            return null;
        }

        $choices = [];
        foreach ($cards as $card) {
            $label = "{$card['brand']} ending in {$card['last4']}";
            $choices[$card['id']] = $label;
        }

        return $this->choice('Select payment method:', $choices);
    }

    /**
     * Manage addresses
     */
    protected function manageAddresses(): int
    {
        $this->components->task('Fetching addresses', function () {
            $response = $this->apiRequest('GET', '/address');

            if (empty($response['data'])) {
                $this->components->warn('No addresses found');

                return true;
            }

            $this->newLine();
            $this->components->info('Your Addresses:');
            $this->newLine();

            foreach ($response['data'] as $address) {
                $this->components->twoColumnDetail(
                    $address['name'],
                    "<fg=gray>{$address['id']}</>"
                );
                $this->line("  {$address['street1']}");
                if (! empty($address['street2'])) {
                    $this->line("  {$address['street2']}");
                }
                $this->line("  {$address['city']}, {$address['province']} {$address['zip']}");
                $this->line("  {$address['country']}");
                if (! empty($address['phone'])) {
                    $this->line("  Phone: {$address['phone']}");
                }
                $this->newLine();
            }

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Manage payment cards
     */
    protected function manageCards(): int
    {
        $this->components->task('Fetching cards', function () {
            $response = $this->apiRequest('GET', '/card');

            if (empty($response['data'])) {
                $this->components->warn('No payment methods found');

                return true;
            }

            $this->newLine();
            $this->components->info('Your Payment Methods:');
            $this->newLine();

            foreach ($response['data'] as $card) {
                $this->components->twoColumnDetail(
                    "{$card['brand']} ****{$card['last4']}",
                    "<fg=gray>{$card['id']}</>"
                );
                $this->line("  Expires: {$card['expiration']['month']}/{$card['expiration']['year']}");
                $created = date('Y-m-d', strtotime($card['created']));
                $this->line("  Added: <fg=gray>{$created}</>");
                $this->newLine();
            }

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Manage subscriptions
     */
    protected function manageSubscriptions(): int
    {
        $this->components->task('Fetching subscriptions', function () {
            $response = $this->apiRequest('GET', '/subscription');

            if (empty($response['data'])) {
                $this->components->warn('No active subscriptions');

                return true;
            }

            $this->newLine();
            $this->components->info('Your Subscriptions:');
            $this->newLine();

            foreach ($response['data'] as $subscription) {
                $this->components->twoColumnDetail(
                    'Subscription',
                    "<fg=gray>{$subscription['id']}</>"
                );
                $this->line("  Product: <fg=yellow>{$subscription['productVariantID']}</>");
                $price = number_format($subscription['price'] / 100, 2);
                $this->line("  Price: <fg=green>\${$price}</>");
                $this->line("  Quantity: {$subscription['quantity']}");

                if (isset($subscription['schedule'])) {
                    $schedule = $subscription['schedule'];
                    if ($schedule['type'] === 'weekly') {
                        $this->line("  Schedule: Every {$schedule['interval']} weeks");
                    } else {
                        $this->line("  Schedule: {$schedule['type']}");
                    }
                }

                if (isset($subscription['next'])) {
                    $next = date('Y-m-d', strtotime($subscription['next']));
                    $this->line("  Next delivery: <fg=cyan>{$next}</>");
                }

                $this->newLine();
            }

            return true;
        });

        return self::SUCCESS;
    }

    /**
     * Show help information
     */
    protected function showHelp(): int
    {
        $this->newLine();
        $this->line('Order coffee directly from your terminal!');
        $this->newLine();
        $this->line('<fg=cyan>Available Actions:</>');
        $this->line('  <fg=green>list</>           - Show available coffee products');
        $this->line('  <fg=green>order</>          - Order coffee (requires --variant)');
        $this->line('  <fg=green>cart</>           - View your shopping cart');
        $this->line('  <fg=green>status</>         - Check order status');
        $this->line('  <fg=green>profile</>        - View your profile');
        $this->line('  <fg=green>addresses</>      - Manage shipping addresses');
        $this->line('  <fg=green>cards</>          - Manage payment methods');
        $this->line('  <fg=green>subscriptions</>  - Manage coffee subscriptions');
        $this->newLine();
        $this->line('<fg=cyan>Examples:</>');
        $this->line('  php artisan brew list');
        $this->line('  php artisan brew order --variant=var_XXX --quantity=2');
        $this->line('  php artisan brew order --variant=var_XXX --subscribe --interval=3');
        $this->line('  php artisan brew cart');
        $this->line('  php artisan brew status');
        $this->newLine();
        $this->line('<fg=cyan>Options:</>');
        $this->line('  --token=       Your Terminal Shop API token');
        $this->line('  --sandbox      Use sandbox environment');
        $this->line('  --product=     Product ID');
        $this->line('  --variant=     Product variant ID');
        $this->line('  --quantity=    Quantity to order (default: 1)');
        $this->line('  --subscribe    Subscribe to the product');
        $this->line('  --interval=    Subscription interval in weeks');
        $this->line('  --address=     Address ID for shipping');
        $this->line('  --card=        Card ID for payment');
        $this->newLine();

        return self::SUCCESS;
    }
}
