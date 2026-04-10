# PHP SDK

> **Enterprise-Grade PHP SDK for Laravel, Symfony, and Custom Applications**  
> Modern PHP 8.0+ support with comprehensive type declarations and PSR-4 compliance

---

## Installation

### Composer
```bash
composer require susudigital/php-sdk
```

### Laravel Package Discovery
The SDK automatically registers with Laravel's package discovery. No additional configuration needed.

---

## Quick Start

### Basic Setup

```php
<?php
use SusuDigital\Client;

$client = new Client([
    'api_key' => $_ENV['SUSU_API_KEY'],
    'environment' => 'production', // or 'sandbox'
    'organization' => $_ENV['SUSU_ORGANIZATION_ID'] ?? null,
    'timeout' => 30,
    'max_retries' => 3
]);
```

### Environment Configuration

```php
// .env file
SUSU_API_KEY=sk_live_your_secret_key_here
SUSU_ORGANIZATION_ID=org_your_organization_id
SUSU_ENVIRONMENT=production
SUSU_WEBHOOK_SECRET=whsec_your_webhook_secret
```

---

## Core Services

### Customer Management

```php
<?php
use SusuDigital\Types\CustomerCreate;
use SusuDigital\Types\Address;
use SusuDigital\Types\Identification;

// Create a new customer
$customerData = new CustomerCreate([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'phone' => '+233XXXXXXXXX',
    'email' => 'john.doe@example.com',
    'date_of_birth' => '1990-01-15',
    'address' => new Address([
        'street' => '123 Main Street',
        'city' => 'Accra',
        'region' => 'Greater Accra',
        'country' => 'Ghana'
    ]),
    'identification' => new Identification([
        'type' => 'national_id',
        'number' => 'GHA-123456789-0',
        'expiry_date' => '2030-12-31'
    ])
]);

$customer = $client->customers()->create($customerData);

// Get customer details
$customerDetails = $client->customers()->get($customer->id);

// Update customer information
$updatedCustomer = $client->customers()->update($customer->id, [
    'email' => 'john.newemail@example.com',
    'phone' => '+233YYYYYYYYY'
]);

// Get customer balance
$balance = $client->customers()->getBalance($customer->id);

// List customers with pagination
$customers = $client->customers()->list([
    'page' => 1,
    'limit' => 50,
    'search' => 'john',
    'status' => 'active'
]);
```

### Transaction Processing

```php
<?php
use SusuDigital\Types\DepositRequest;
use SusuDigital\Types\WithdrawalRequest;
use SusuDigital\Types\TransferRequest;

// Process a deposit
$deposit = $client->transactions()->deposit(new DepositRequest([
    'customer_id' => 'cust_123456789',
    'amount' => 100.00,
    'currency' => 'GHS',
    'description' => 'Savings deposit',
    'reference' => 'DEP-' . time(),
    'metadata' => [
        'branch' => 'Accra Main',
        'collector' => 'John Collector'
    ]
]));

// Process a withdrawal
$withdrawal = $client->transactions()->withdraw(new WithdrawalRequest([
    'customer_id' => 'cust_123456789',
    'amount' => 50.00,
    'currency' => 'GHS',
    'description' => 'Cash withdrawal',
    'reference' => 'WTH-' . time()
]));

// Transfer between customers
$transfer = $client->transactions()->transfer(new TransferRequest([
    'from_customer_id' => 'cust_123456789',
    'to_customer_id' => 'cust_987654321',
    'amount' => 25.00,
    'currency' => 'GHS',
    'description' => 'P2P transfer',
    'reference' => 'TRF-' . time()
]));

// Get transaction history
$transactions = $client->transactions()->list([
    'customer_id' => 'cust_123456789',
    'start_date' => '2026-01-01',
    'end_date' => '2026-03-31',
    'type' => 'deposit',
    'status' => 'completed'
]);

// Get transaction details
$transaction = $client->transactions()->get('txn_123456789');
```

### Loan Management

```php
<?php
use SusuDigital\Types\LoanApplicationRequest;
use SusuDigital\Types\Collateral;
use SusuDigital\Types\Guarantor;

// Create loan application
$loanApplication = $client->loans()->createApplication(new LoanApplicationRequest([
    'customer_id' => 'cust_123456789',
    'amount' => 5000.00,
    'currency' => 'GHS',
    'purpose' => 'business_expansion',
    'term' => 12, // months
    'interest_rate' => 15.0, // annual percentage
    'collateral' => new Collateral([
        'type' => 'property',
        'description' => 'Residential property in Accra',
        'value' => 50000.00
    ]),
    'guarantors' => [
        new Guarantor([
            'name' => 'Jane Guarantor',
            'phone' => '+233XXXXXXXXX',
            'relationship' => 'spouse'
        ])
    ]
]));

// Approve loan
$approvedLoan = $client->loans()->approve($loanApplication->id, [
    'approved_amount' => 4500.00,
    'approved_term' => 12,
    'approved_rate' => 14.0,
    'conditions' => ['Provide additional documentation']
]);

// Disburse loan
$disbursement = $client->loans()->disburse($approvedLoan->id, [
    'disbursement_method' => 'bank_transfer',
    'account_details' => [
        'bank_code' => '030',
        'account_number' => '1234567890'
    ]
]);

// Record loan repayment
$repayment = $client->loans()->recordRepayment($approvedLoan->id, [
    'amount' => 450.00,
    'payment_date' => '2026-04-10',
    'payment_method' => 'cash',
    'reference' => 'REP-' . time()
]);

// Get loan schedule
$schedule = $client->loans()->getSchedule($approvedLoan->id);

// List loans
$loans = $client->loans()->list([
    'customer_id' => 'cust_123456789',
    'status' => 'active',
    'page' => 1,
    'limit' => 20
]);
```

---

## Laravel Integration

### Service Provider Registration

```php
<?php
// config/app.php
'providers' => [
    // ... other providers
    SusuDigital\Laravel\SusuDigitalServiceProvider::class,
],

'aliases' => [
    // ... other aliases
    'SusuDigital' => SusuDigital\Laravel\Facades\SusuDigital::class,
],
```

### Configuration

```php
<?php
// config/susudigital.php
return [
    'api_key' => env('SUSU_API_KEY'),
    'environment' => env('SUSU_ENVIRONMENT', 'sandbox'),
    'organization' => env('SUSU_ORGANIZATION_ID'),
    'webhook_secret' => env('SUSU_WEBHOOK_SECRET'),
    'timeout' => env('SUSU_TIMEOUT', 30),
    'max_retries' => env('SUSU_MAX_RETRIES', 3),
    'enable_logging' => env('SUSU_ENABLE_LOGGING', false),
];
```

### Laravel Service Class

```php
<?php
namespace App\Services;

use SusuDigital\Laravel\Facades\SusuDigital;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CustomerService
{
    public function createCustomerFromUser(User $user): object
    {
        try {
            $customerData = [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->profile->phone,
            ];
            
            $customer = SusuDigital::customers()->create($customerData);
            
            // Save customer ID to user profile
            $user->profile->update(['susu_customer_id' => $customer->id]);
            
            return $customer;
        } catch (\Exception $e) {
            Log::error('Customer creation failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    public function getCustomerBalance(string $customerId): object
    {
        return SusuDigital::customers()->getBalance($customerId);
    }
}
```

### Laravel Controllers

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use SusuDigital\Laravel\Facades\SusuDigital;
use SusuDigital\Exceptions\ValidationException;
use SusuDigital\Exceptions\NotFoundException;

class SusuController extends Controller
{
    public function createCustomer(Request $request): JsonResponse
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'phone' => 'required|string|regex:/^\+233[0-9]{9}$/',
            'email' => 'nullable|email'
        ]);
        
        try {
            $customer = SusuDigital::customers()->create($request->all());
            return response()->json($customer, 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->getDetails()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Customer creation failed'], 500);
        }
    }
    
    public function getCustomer(string $customerId): JsonResponse
    {
        try {
            $customer = SusuDigital::customers()->get($customerId);
            return response()->json($customer);
        } catch (NotFoundException $e) {
            return response()->json(['error' => 'Customer not found'], 404);
        }
    }
}
```

### Webhook Handling

```php
<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SusuDigital\Laravel\Facades\SusuDigital;
use SusuDigital\Webhook\WebhookHandler;

class WebhookController extends Controller
{
    public function handle(Request $request): Response
    {
        $webhookHandler = new WebhookHandler(config('susudigital.webhook_secret'));
        
        try {
            $event = $webhookHandler->constructEvent(
                $request->getContent(),
                $request->header('Susu-Signature')
            );
            
            switch ($event->type) {
                case 'transaction.completed':
                    $this->handleTransactionCompleted($event->data);
                    break;
                case 'loan.approved':
                    $this->handleLoanApproved($event->data);
                    break;
                default:
                    \Log::info('Unhandled webhook event', ['type' => $event->type]);
            }
            
            return response('OK', 200);
        } catch (\Exception $e) {
            \Log::error('Webhook processing failed', ['error' => $e->getMessage()]);
            return response('Bad Request', 400);
        }
    }
    
    private function handleTransactionCompleted(object $data): void
    {
        // Update local transaction status
        \App\Models\Transaction::where('susu_transaction_id', $data->transaction->id)
            ->update(['status' => 'completed']);
    }
    
    private function handleLoanApproved(object $data): void
    {
        // Notify customer about loan approval
        \App\Jobs\SendLoanApprovalNotification::dispatch($data->loan);
    }
}
```

### Laravel Models Integration

```php
<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use SusuDigital\Laravel\Traits\HasSusuCustomer;

class Customer extends Model
{
    use HasSusuCustomer;
    
    protected $fillable = [
        'first_name', 'last_name', 'phone', 'email', 'susu_customer_id'
    ];
    
    public function syncWithSusu(): void
    {
        if ($this->susu_customer_id) {
            $susuCustomer = $this->getSusuCustomer();
            $this->update([
                'phone' => $susuCustomer->phone,
                'email' => $susuCustomer->email,
            ]);
        }
    }
    
    public function getBalanceAttribute(): object
    {
        return $this->getSusuBalance();
    }
}

class Transaction extends Model
{
    protected $fillable = [
        'customer_id', 'susu_transaction_id', 'amount', 'type', 'status'
    ];
    
    protected $casts = [
        'amount' => 'decimal:2'
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
```

---

## Symfony Integration

### Bundle Configuration

```yaml
# config/packages/susu_digital.yaml
susu_digital:
    api_key: '%env(SUSU_API_KEY)%'
    environment: '%env(SUSU_ENVIRONMENT)%'
    organization: '%env(SUSU_ORGANIZATION_ID)%'
    webhook_secret: '%env(SUSU_WEBHOOK_SECRET)%'
    timeout: 30
    max_retries: 3
```

### Service Definition

```yaml
# config/services.yaml
services:
    SusuDigital\Client:
        arguments:
            $config:
                api_key: '%env(SUSU_API_KEY)%'
                environment: '%env(SUSU_ENVIRONMENT)%'
                organization: '%env(SUSU_ORGANIZATION_ID)%'
        
    App\Service\CustomerService:
        arguments:
            $susuClient: '@SusuDigital\Client'
```

### Symfony Controller

```php
<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use SusuDigital\Client;
use SusuDigital\Exceptions\ValidationException;

class SusuController extends AbstractController
{
    public function __construct(private Client $susuClient)
    {
    }
    
    #[Route('/api/customers', methods: ['POST'])]
    public function createCustomer(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        
        try {
            $customer = $this->susuClient->customers()->create($data);
            return $this->json($customer, 201);
        } catch (ValidationException $e) {
            return $this->json(['error' => $e->getDetails()], 422);
        }
    }
    
    #[Route('/api/customers/{customerId}', methods: ['GET'])]
    public function getCustomer(string $customerId): JsonResponse
    {
        try {
            $customer = $this->susuClient->customers()->get($customerId);
            return $this->json($customer);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Customer not found'], 404);
        }
    }
}
```

---

## Error Handling

### Exception Types

```php
<?php
use SusuDigital\Exceptions\SusuDigitalException;
use SusuDigital\Exceptions\ValidationException;
use SusuDigital\Exceptions\AuthenticationException;
use SusuDigital\Exceptions\RateLimitException;
use SusuDigital\Exceptions\NetworkException;
use SusuDigital\Exceptions\NotFoundException;

try {
    $customer = $client->customers()->create($customerData);
} catch (ValidationException $e) {
    echo "Validation failed: " . json_encode($e->getDetails());
    // Handle validation errors
} catch (AuthenticationException $e) {
    echo "Authentication failed: " . $e->getMessage();
    // Handle auth errors
} catch (RateLimitException $e) {
    echo "Rate limit exceeded. Retry after: " . $e->getRetryAfter() . " seconds";
    // Wait and retry
    sleep($e->getRetryAfter());
} catch (NetworkException $e) {
    echo "Network error: " . $e->getMessage();
    // Handle network issues
} catch (NotFoundException $e) {
    echo "Resource not found: " . $e->getMessage();
    // Handle not found errors
} catch (SusuDigitalException $e) {
    echo "Susu Digital error: " . $e->getMessage();
    // Handle general Susu errors
}
```

### Custom Error Handler

```php
<?php
class SusuErrorHandler
{
    public static function handle(callable $operation, int $maxRetries = 3)
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                return $operation();
            } catch (RateLimitException $e) {
                if ($attempt === $maxRetries - 1) {
                    throw $e;
                }
                sleep($e->getRetryAfter());
                $attempt++;
            } catch (NetworkException $e) {
                if ($attempt === $maxRetries - 1) {
                    throw $e;
                }
                sleep(pow(2, $attempt)); // Exponential backoff
                $attempt++;
            }
        }
    }
}

// Usage
$customer = SusuErrorHandler::handle(function() use ($client, $customerData) {
    return $client->customers()->create($customerData);
});
```

---

## Type Declarations

### Customer Types

```php
<?php
namespace SusuDigital\Types;

class Customer
{
    public function __construct(
        public readonly string $id,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $phone,
        public readonly ?string $email = null,
        public readonly ?string $dateOfBirth = null,
        public readonly string $status = 'active',
        public readonly string $createdAt = '',
        public readonly string $updatedAt = '',
        public readonly ?Address $address = null,
        public readonly ?Identification $identification = null,
        public readonly array $metadata = []
    ) {}
}

class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $region,
        public readonly string $country,
        public readonly ?string $postalCode = null
    ) {}
}

class Identification
{
    public function __construct(
        public readonly string $type,
        public readonly string $number,
        public readonly ?string $expiryDate = null,
        public readonly ?string $issueDate = null
    ) {}
}
```

### Transaction Types

```php
<?php
namespace SusuDigital\Types;

class Transaction
{
    public function __construct(
        public readonly string $id,
        public readonly string $customerId,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $currency,
        public readonly string $status,
        public readonly ?string $description = null,
        public readonly string $reference = '',
        public readonly string $createdAt = '',
        public readonly ?string $completedAt = null,
        public readonly array $metadata = []
    ) {}
}

class DepositRequest
{
    public function __construct(
        public readonly string $customerId,
        public readonly float $amount,
        public readonly string $currency = 'GHS',
        public readonly ?string $description = null,
        public readonly string $reference = '',
        public readonly array $metadata = []
    ) {}
}
```

---

## Testing

### PHPUnit Testing

```php
<?php
namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use SusuDigital\Client;
use SusuDigital\Types\Customer;
use Mockery;

class CustomerServiceTest extends TestCase
{
    private Client $mockClient;
    
    protected function setUp(): void
    {
        $this->mockClient = Mockery::mock(Client::class);
    }
    
    public function testCreateCustomerSuccess(): void
    {
        $customerData = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '+233XXXXXXXXX'
        ];
        
        $expectedCustomer = new Customer(
            id: 'cust_123',
            firstName: 'John',
            lastName: 'Doe',
            phone: '+233XXXXXXXXX'
        );
        
        $this->mockClient->shouldReceive('customers->create')
            ->once()
            ->with($customerData)
            ->andReturn($expectedCustomer);
        
        $result = $this->mockClient->customers()->create($customerData);
        
        $this->assertEquals('cust_123', $result->id);
        $this->assertEquals('John', $result->firstName);
    }
    
    protected function tearDown(): void
    {
        Mockery::close();
    }
}
```

### Integration Testing

```php
<?php
namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use SusuDigital\Client;

class SusuIntegrationTest extends TestCase
{
    private Client $client;
    
    protected function setUp(): void
    {
        $this->client = new Client([
            'api_key' => $_ENV['SUSU_TEST_API_KEY'],
            'environment' => 'sandbox'
        ]);
    }
    
    public function testCustomerLifecycle(): void
    {
        // Create customer
        $customerData = [
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'phone' => '+233XXXXXXXXX',
            'email' => 'test@example.com'
        ];
        
        $customer = $this->client->customers()->create($customerData);
        $this->assertNotNull($customer->id);
        
        // Retrieve customer
        $retrieved = $this->client->customers()->get($customer->id);
        $this->assertEquals($customerData['first_name'], $retrieved->firstName);
        
        // Update customer
        $updated = $this->client->customers()->update($customer->id, [
            'email' => 'updated@example.com'
        ]);
        $this->assertEquals('updated@example.com', $updated->email);
        
        // Cleanup
        $this->client->customers()->delete($customer->id);
    }
}
```

---

## Performance Optimization

### Connection Pooling

```php
<?php
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;

$handler = new CurlMultiHandler();
$stack = HandlerStack::create($handler);

$httpClient = new HttpClient([
    'handler' => $stack,
    'timeout' => 30,
    'connect_timeout' => 10,
    'pool_size' => 50
]);

$client = new Client([
    'api_key' => $_ENV['SUSU_API_KEY'],
    'http_client' => $httpClient
]);
```

### Caching with Redis

```php
<?php
use Predis\Client as RedisClient;
use SusuDigital\Cache\RedisCache;

$redis = new RedisClient([
    'scheme' => 'tcp',
    'host' => '127.0.0.1',
    'port' => 6379,
]);

$cache = new RedisCache($redis, 300); // 5 minutes default TTL

$client = new Client([
    'api_key' => $_ENV['SUSU_API_KEY'],
    'cache' => $cache
]);

// Cached operations
$customer = $client->customers()->get('cust_123', ['cache' => true, 'ttl' => 600]);
```

### Batch Operations

```php
<?php
use SusuDigital\Batch\BatchProcessor;

$batchProcessor = new BatchProcessor($client, 100); // Batch size of 100

// Batch customer creation
$customersData = [
    ['first_name' => 'John', 'last_name' => 'Doe', 'phone' => '+233XXXXXXXXX'],
    ['first_name' => 'Jane', 'last_name' => 'Smith', 'phone' => '+233YYYYYYYYY'],
    // ... more customers
];

$results = $batchProcessor->customers()->createBatch($customersData);

foreach ($results as $result) {
    if ($result->isSuccess()) {
        echo "Created customer: " . $result->getData()->id . "\n";
    } else {
        echo "Failed to create customer: " . $result->getError() . "\n";
    }
}
```

---

## Best Practices

### 1. **Configuration Management**

```php
<?php
class SusuConfig
{
    public static function fromEnvironment(): array
    {
        return [
            'api_key' => $_ENV['SUSU_API_KEY'] ?? throw new \InvalidArgumentException('SUSU_API_KEY is required'),
            'environment' => $_ENV['SUSU_ENVIRONMENT'] ?? 'sandbox',
            'organization' => $_ENV['SUSU_ORGANIZATION_ID'] ?? null,
            'timeout' => (int)($_ENV['SUSU_TIMEOUT'] ?? 30),
            'max_retries' => (int)($_ENV['SUSU_MAX_RETRIES'] ?? 3),
            'enable_logging' => filter_var($_ENV['SUSU_ENABLE_LOGGING'] ?? false, FILTER_VALIDATE_BOOLEAN),
        ];
    }
}

// Usage
$client = new Client(SusuConfig::fromEnvironment());
```

### 2. **Service Layer Pattern**

```php
<?php
interface CustomerServiceInterface
{
    public function createCustomer(array $data): Customer;
    public function getCustomer(string $customerId): ?Customer;
}

class SusuCustomerService implements CustomerServiceInterface
{
    public function __construct(private Client $client)
    {
    }
    
    public function createCustomer(array $data): Customer
    {
        try {
            return $this->client->customers()->create($data);
        } catch (\Exception $e) {
            error_log("Customer creation failed: " . $e->getMessage());
            throw new \RuntimeException('Unable to create customer');
        }
    }
    
    public function getCustomer(string $customerId): ?Customer
    {
        try {
            return $this->client->customers()->get($customerId);
        } catch (NotFoundException $e) {
            return null;
        }
    }
}
```

### 3. **Logging Integration**

```php
<?php
use Psr\Log\LoggerInterface;
use SusuDigital\Client;

class LoggingSusuClient
{
    public function __construct(
        private Client $client,
        private LoggerInterface $logger
    ) {}
    
    public function createCustomer(array $data): Customer
    {
        $this->logger->info('Creating customer', ['data' => $this->sanitizeData($data)]);
        
        try {
            $customer = $this->client->customers()->create($data);
            $this->logger->info('Customer created successfully', ['customer_id' => $customer->id]);
            return $customer;
        } catch (\Exception $e) {
            $this->logger->error('Customer creation failed', [
                'error' => $e->getMessage(),
                'data' => $this->sanitizeData($data)
            ]);
            throw $e;
        }
    }
    
    private function sanitizeData(array $data): array
    {
        $sanitized = $data;
        if (isset($sanitized['phone'])) {
            $sanitized['phone'] = '[REDACTED]';
        }
        return $sanitized;
    }
}
```

---

## Support

### Getting Help

- **Documentation**: [developers.susudigital.app/php-sdk](https://developers.susudigital.app/php-sdk)
- **Packagist**: [packagist.org/packages/susudigital/php-sdk](https://packagist.org/packages/susudigital/php-sdk)
- **GitHub Issues**: [github.com/susudigital/php-sdk/issues](https://github.com/susudigital/php-sdk/issues)
- **Email Support**: [php-sdk@susudigital.app](mailto:php-sdk@susudigital.app)

### Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/susudigital/php-sdk/blob/main/CONTRIBUTING.md) for details.

---

**© 2026 Susu Digital. All rights reserved.**

*Last Updated: April 10, 2026*  
*SDK Version: 1.8.2*