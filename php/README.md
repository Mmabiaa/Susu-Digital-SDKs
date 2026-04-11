# Susu Digital PHP SDK

[![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue?logo=php)](https://www.php.net)
[![License: MIT](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![Packagist](https://img.shields.io/packagist/v/susudigital/susudigital-php)](https://packagist.org/packages/susudigital/susudigital-php)

**Enterprise-Grade PHP SDK for the [Susu Digital](https://developers.susudigital.app) microfinance platform.**

---

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
- [Services](#services)
  - [Customers](#customers)
  - [Transactions](#transactions)
  - [Loans](#loans)
  - [Savings](#savings)
  - [Analytics](#analytics)
- [Webhook Verification](#webhook-verification)
- [Batch Processing](#batch-processing)
- [Error Handling](#error-handling)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

## Requirements

| Requirement | Version |
|-------------|---------|
| PHP         | `^8.0`  |
| `ext-json`  | any     |
| `ext-curl`  | any     |
| Guzzle      | `^7.5`  |

---

## Installation

```bash
composer require susudigital/susudigital-php
```

---

## Quick Start

```php
<?php

use SusuDigital\SusuDigitalClient;

$client = new SusuDigitalClient(
    apiKey:      'sk_live_...',
    environment: 'production',   // or 'sandbox' (default)
);

// Create a customer
$customer = $client->customers->create([
    'first_name' => 'Kwame',
    'last_name'  => 'Mensah',
    'phone'      => '+233201234567',
    'email'      => 'kwame@example.com',
]);

echo $customer->id;                // cust_xxxxx
echo $customer->getFullName();     // Kwame Mensah

// Deposit funds
$txn = $client->transactions->deposit([
    'customer_id' => $customer->id,
    'amount'      => '100.00',
    'currency'    => 'GHS',
]);

echo $txn->status; // completed
```

---

## Configuration

```php
$client = new SusuDigitalClient(
    apiKey:        'sk_live_...',          // Required — your API key
    environment:   'production',           // 'production' | 'sandbox'  (default: 'sandbox')
    organization:  'org_...',              // Optional — scopes all requests to one org
    timeout:       30.0,                   // HTTP timeout in seconds (default: 30)
    maxRetries:    3,                      // Automatic retries on 429/5xx (default: 3)
    enableLogging: true,                   // Log requests to error_log (default: false)
    customHeaders: ['X-App-Id' => 'app'], // Extra headers on every request
);
```

### Bring Your Own Guzzle Client

```php
use GuzzleHttp\Client;

$guzzle = new Client(['proxy' => 'http://proxy.example.com']);
$client = new SusuDigitalClient(apiKey: '...', httpClient: $guzzle);
```

---

## Services

All services are available as **public readonly properties** on the client:

| Property             | Class                  | Domain                         |
|----------------------|------------------------|--------------------------------|
| `$client->customers` | `CustomerService`      | KYC and customer management    |
| `$client->transactions` | `TransactionService`| Deposits, withdrawals, transfers|
| `$client->loans`     | `LoanService`          | Loan origination & servicing   |
| `$client->savings`   | `SavingsService`       | Savings accounts & goals       |
| `$client->analytics` | `AnalyticsService`     | Business intelligence          |

---

### Customers

```php
use SusuDigital\Models\CustomerStatus;

// Create
$customer = $client->customers->create([
    'first_name'    => 'Ama',
    'last_name'     => 'Owusu',
    'phone'         => '+233244123456',
    'email'         => 'ama@example.com',
    'date_of_birth' => '1990-05-15',
    'address'       => [
        'street'  => '5 Independence Ave',
        'city'    => 'Accra',
        'region'  => 'Greater Accra',
        'country' => 'Ghana',
    ],
]);

// Retrieve
$customer = $client->customers->get('cust_123');

// Update
$customer = $client->customers->update('cust_123', ['email' => 'new@example.com']);

// Delete (deactivate)
$client->customers->delete('cust_123');

// Balance
$balance = $client->customers->getBalance('cust_123');
echo $balance->available; // "500.00"
echo $balance->currency;  // "GHS"

// List with filters
$page = $client->customers->list(
    page:   1,
    limit:  20,
    search: 'Kwame',
    status: CustomerStatus::Active,  // or 'active'
);

foreach ($page->data as $c) {
    echo $c->getFullName() . PHP_EOL;
}

echo $page->total;        // 42
echo $page->hasNext ? 'more pages' : 'last page';
```

---

### Transactions

```php
// Deposit
$txn = $client->transactions->deposit([
    'customer_id' => 'cust_123',
    'amount'      => '250.00',
    'currency'    => 'GHS',
    'reference'   => 'INV-001',
]);

// Withdrawal
$txn = $client->transactions->withdraw([
    'customer_id' => 'cust_123',
    'amount'      => '50.00',
]);

// Transfer
$txn = $client->transactions->transfer([
    'from_customer_id' => 'cust_123',
    'to_customer_id'   => 'cust_456',
    'amount'           => '100.00',
    'description'      => 'Rent payment',
]);

// Retrieve
$txn = $client->transactions->get('txn_789');

// List
$page = $client->transactions->list(
    customerId: 'cust_123',
    startDate:  '2026-01-01',
    endDate:    '2026-03-31',
    type:       'deposit',
    page:       1,
    limit:      50,
);
```

---

### Loans

```php
// Apply
$loan = $client->loans->createApplication([
    'customer_id'   => 'cust_123',
    'amount'        => '5000.00',
    'currency'      => 'GHS',
    'term'          => 12,              // months
    'interest_rate' => '15.0',
    'purpose'       => 'business_expansion',
    'guarantors'    => [
        ['name' => 'Abena Poku', 'phone' => '+233244000001', 'relationship' => 'spouse'],
    ],
]);

// Approve
$loan = $client->loans->approve($loan->id, [
    'approved_amount' => '4500.00',
    'approved_term'   => 12,
    'approved_rate'   => '14.5',
]);

// Disburse
$loan = $client->loans->disburse($loan->id, [
    'disbursement_method' => 'mobile_money',
    'account_details'     => ['network' => 'MTN', 'number' => '+233244000000'],
]);

// Record repayment
$client->loans->recordRepayment($loan->id, [
    'amount'         => '416.67',
    'payment_date'   => '2026-05-01',
    'payment_method' => 'mobile_money',
]);

// Schedule
$schedule = $client->loans->getSchedule($loan->id);

foreach ($schedule as $item) {
    echo "#{$item->installment_number}: {$item->total} due {$item->due_date}" . PHP_EOL;
}

// List
$page = $client->loans->list(customerId: 'cust_123', status: 'active');
```

---

### Savings

```php
// Open account
$account = $client->savings->createAccount([
    'customer_id'     => 'cust_123',
    'account_type'    => 'regular',  // 'regular' | 'fixed' | 'susu'
    'currency'        => 'GHS',
    'minimum_balance' => '10.00',
]);

// Get balance
$balance = $client->savings->getBalance($account->id);

// Create goal
$goal = $client->savings->createGoal([
    'account_id'           => $account->id,
    'name'                 => 'New laptop',
    'target_amount'        => '3000.00',
    'target_date'          => '2027-01-01',
    'monthly_contribution' => '250.00',
]);

// List accounts
$page = $client->savings->listAccounts(customerId: 'cust_123');
```

---

### Analytics

```php
// Customer analytics
$analytics = $client->analytics->getCustomerAnalytics(
    customerId: 'cust_123',
    startDate:  '2026-01-01',
    endDate:    '2026-03-31',
);

echo $analytics->total_deposits;    // "12500.00"
echo $analytics->transaction_count; // 48

// Transaction summary
$summaries = $client->analytics->getTransactionSummary(
    startDate: '2026-01-01',
    endDate:   '2026-12-31',
    groupBy:   'month',
);

foreach ($summaries as $s) {
    echo "{$s->period}: {$s->total_amount} ({$s->transaction_count} txns)" . PHP_EOL;
}

// Generate report
$report = $client->analytics->generateReport(
    reportType: 'transaction_summary',
    startDate:  '2026-01-01',
    endDate:    '2026-12-31',
    format:     'csv',
);

echo $report->download_url; // https://...
```

---

## Webhook Verification

```php
use SusuDigital\WebhookHandler;

$handler = new WebhookHandler(
    secret:           'whsec_...',    // from your dashboard
    verifySignatures: true,           // set false only in tests
    tolerance:        300,            // max age in seconds (default: 300)
);

// Register event listeners (fluent chaining)
$handler
    ->on('transaction.completed', function ($event) {
        echo "Transaction {$event->data['id']} completed\n";
    })
    ->on('customer.created', function ($event) {
        sendWelcomeEmail($event->data['email']);
    })
    ->on('*', function ($event) {
        // Called for every event
        logEvent($event->type);
    });

// In a framework controller (e.g. Laravel):
$event = $handler->constructEvent(
    payload:   $request->getContent(),
    signature: $request->header('Susu-Signature'),
);

$handler->dispatch($event);
```

### Signature Format

```
Susu-Signature: t=<unix_timestamp>,v1=<hex_hmac_sha256>
```

---

## Batch Processing

```php
use SusuDigital\BatchProcessor;

$processor = new BatchProcessor($client, batchSize: 100);

$customersData = [
    ['first_name' => 'John', 'last_name' => 'Doe',  'phone' => '+233201111111'],
    ['first_name' => 'Jane', 'last_name' => 'Smith', 'phone' => '+233202222222'],
    // ... thousands more
];

$results = $processor->customers->createBatch($customersData);

echo "Created:  {$results->successCount()}\n";
echo "Failed:   {$results->failureCount()}\n";

foreach ($results->failed() as $failure) {
    echo "Item #{$failure->index} failed: {$failure->error->getMessage()}\n";
}

// Iterate all results
foreach ($results as $result) {
    if ($result->success) {
        echo "Created: {$result->data->id}\n";
    }
}
```

---

## Error Handling

All SDK errors extend `SusuDigital\Exceptions\SusuDigitalException`:

```php
use SusuDigital\Exceptions\AuthenticationException;
use SusuDigital\Exceptions\NetworkException;
use SusuDigital\Exceptions\NotFoundException;
use SusuDigital\Exceptions\RateLimitException;
use SusuDigital\Exceptions\ServerException;
use SusuDigital\Exceptions\SusuDigitalException;
use SusuDigital\Exceptions\ValidationException;

try {
    $customer = $client->customers->get('cust_missing');

} catch (NotFoundException $e) {
    echo "Not found: {$e->getMessage()}\n";

} catch (ValidationException $e) {
    echo "Validation errors:\n";
    foreach ($e->getFieldErrors() as $field => $errors) {
        echo "  {$field}: " . implode(', ', $errors) . "\n";
    }

} catch (RateLimitException $e) {
    echo "Rate limited. Retry after {$e->getRetryAfter()}s\n";

} catch (AuthenticationException $e) {
    echo "Auth failed: {$e->getMessage()}\n";

} catch (ServerException $e) {
    echo "Server error ({$e->getStatusCode()}): {$e->getMessage()}\n";

} catch (NetworkException $e) {
    echo "Network error: {$e->getMessage()}\n";

} catch (SusuDigitalException $e) {
    // Catch-all for any SDK error
    echo "[{$e->getSdkCode()}] {$e->getMessage()} (request_id={$e->getRequestId()})\n";
}
```

### Exception Hierarchy

```
SusuDigitalException
├── AuthenticationException    (HTTP 401 / 403) — invalid API key
├── ValidationException        (HTTP 422 / 400) — getFieldErrors()
├── NotFoundException          (HTTP 404)
├── RateLimitException         (HTTP 429)       — getRetryAfter()
├── ServerException            (HTTP 5xx)       — retryable
├── NetworkException           (transport)      — retryable
└── WebhookSignatureException  (HMAC failure)
```

---

## Testing

```bash
# Install dependencies
composer install

# Run the test suite
composer test

# With coverage report (requires Xdebug or PCOV)
composer test:coverage

# Static analysis
composer stan
```

---

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feat/my-feature`
3. Write tests first (TDD encouraged)
4. Submit a pull request against `main`

---

## License

MIT © Susu Digital. See [LICENSE](../LICENSE) for details.
