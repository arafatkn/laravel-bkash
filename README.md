# Laravel bKash

Laravel package for bKash Tokenized Payment Gateway integration.

## Requirements

- PHP ^8.0
- Laravel ^8.0

## Installation

```bash
composer require arafatkn/laravel-bkash
```

The service provider and facade are registered automatically via Laravel's package auto-discovery.

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=bkash-config
```

Add the following variables to your `.env` file:

```env
BKASH_SANDBOX=true
BKASH_APP_KEY=your_app_key
BKASH_APP_SECRET=your_app_secret
BKASH_USERNAME=your_username
BKASH_PASSWORD=your_password
```

### Config Options

| Key | Default | Description |
|---|---|---|
| `sandbox` | `true` | Use sandbox or live environment |
| `debug` | `APP_DEBUG` | Log all requests and responses |
| `app_key` | `''` | bKash app key |
| `app_secret` | `''` | bKash app secret |
| `username` | `''` | bKash username |
| `password` | `''` | bKash password |
| `cache.refresh_token_lifetime` | `604800` | Refresh token cache lifetime in seconds (7 days) |
| `default_currency` | `BDT` | Default payment currency |
| `default_intent` | `sale` | Default payment intent |

## Usage

Use the `BkashTokenizedPayment` facade or resolve `Arafatkn\LaravelBkash\Tokenized\Payment` from the container.

### Create Payment

Initiates a tokenized payment and returns a `bkashURL` to redirect the customer to.

```php
use BkashTokenizedPayment;

$response = BkashTokenizedPayment::createPayment([
    'amount'                => '100',
    'merchantInvoiceNumber' => 'INV-001',
    'callbackURL'           => route('bkash.callback'),
    'payerReference'        => 'user_123',   // optional
    'currency'              => 'BDT',         // optional, defaults to config
    'intent'                => 'sale',        // optional, defaults to config
]);

// Redirect customer to payment page
return redirect($response['bkashURL']);
```

### Execute Payment

Called from your callback URL after the customer approves the payment.

```php
$paymentID = $request->query('paymentID');

$response = BkashTokenizedPayment::executePayment($paymentID);

if ($response['statusCode'] === '0000') {
    // Payment successful
    $trxID = $response['trxID'];
}
```

### Query Payment

Check the status of a payment by `paymentID`.

```php
$response = BkashTokenizedPayment::queryPayment($paymentID);
```

### Search Transaction

Look up a transaction by bKash transaction ID.

```php
$response = BkashTokenizedPayment::searchTransaction($trxID);
```

### Refund Transaction

Initiate a refund for a completed payment.

```php
$response = BkashTokenizedPayment::refundPayment(
    'TR0011xxxxxxxxxxx',
    'ADxxxxxxxx',
    [
        'amount' => '100',                           // optional
        'reason' => 'Customer requested refund',     // optional
        'sku'    => 'product-sku',                   // optional, defaults to 'NA'
    ]
);
```

## Token Management

Token management is handled automatically. The package:

- Grants a new token on first use
- Caches the `id_token` based on the API-reported `expires_in` (minus 10 seconds as a safety buffer)
- Caches the `refresh_token` for 7 days
- Automatically uses the cached `refresh_token` to obtain a new `id_token` when it expires, avoiding a full re-authentication

## Debugging

To log all bKash API requests and responses, set in your `.env`:

```env
BKASH_DEBUG=true
```

Or it will follow `APP_DEBUG` by default. Logs are written at the `debug` level via Laravel's Log facade.

## Typical Payment Flow

```
1. createPayment()  → redirect customer to bkashURL
2. Customer approves on bKash
3. bKash redirects to your callbackURL with paymentID
4. executePayment() → confirm the payment
5. searchTransaction() or queryPayment() → verify if needed
```

## License

MIT
