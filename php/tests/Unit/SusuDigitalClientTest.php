<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SusuDigital\Services\AnalyticsService;
use SusuDigital\Services\CustomerService;
use SusuDigital\Services\LoanService;
use SusuDigital\Services\SavingsService;
use SusuDigital\Services\TransactionService;
use SusuDigital\SusuDigitalClient;
use SusuDigital\Version;

final class SusuDigitalClientTest extends TestCase
{
    private function makeClient(array $responses = []): SusuDigitalClient
    {
        $mock   = new MockHandler($responses);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        return new SusuDigitalClient(
            apiKey: 'sk_test_abc',
            environment: 'sandbox',
            httpClient: $guzzle,
        );
    }

    // -------------------------------------------------------------------------
    // Instantiation
    // -------------------------------------------------------------------------

    public function test_client_can_be_instantiated(): void
    {
        $client = $this->makeClient();
        $this->assertInstanceOf(SusuDigitalClient::class, $client);
    }

    public function test_client_exposes_all_services(): void
    {
        $client = $this->makeClient();

        $this->assertInstanceOf(CustomerService::class, $client->customers);
        $this->assertInstanceOf(TransactionService::class, $client->transactions);
        $this->assertInstanceOf(LoanService::class, $client->loans);
        $this->assertInstanceOf(SavingsService::class, $client->savings);
        $this->assertInstanceOf(AnalyticsService::class, $client->analytics);
    }

    public function test_client_services_are_same_instances(): void
    {
        $client = $this->makeClient();

        // Properties are readonly – always the same object
        $this->assertSame($client->customers, $client->customers);
        $this->assertSame($client->transactions, $client->transactions);
    }

    // -------------------------------------------------------------------------
    // __toString
    // -------------------------------------------------------------------------

    public function test_to_string_contains_version(): void
    {
        $client = $this->makeClient();
        $str    = (string) $client;

        $this->assertStringContainsString(Version::VERSION, $str);
        $this->assertStringContainsString('SusuDigitalClient', $str);
    }

    // -------------------------------------------------------------------------
    // End-to-end smoke: client → service → HTTP → model
    // -------------------------------------------------------------------------

    public function test_customers_create_via_client(): void
    {
        $client = $this->makeClient([
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id'         => 'cust_e2e',
                'first_name' => 'E2E',
                'last_name'  => 'Test',
                'phone'      => '+233201234567',
                'status'     => 'active',
            ])),
        ]);

        $customer = $client->customers->create([
            'first_name' => 'E2E',
            'last_name'  => 'Test',
            'phone'      => '+233201234567',
        ]);

        $this->assertSame('cust_e2e', $customer->id);
        $this->assertSame('E2E Test', $customer->getFullName());
    }

    public function test_transactions_deposit_via_client(): void
    {
        $client = $this->makeClient([
            new Response(201, ['Content-Type' => 'application/json'], json_encode([
                'id'          => 'txn_e2e',
                'customer_id' => 'cust_e2e',
                'type'        => 'deposit',
                'amount'      => '200.00',
                'currency'    => 'GHS',
                'status'      => 'completed',
            ])),
        ]);

        $txn = $client->transactions->deposit([
            'customer_id' => 'cust_e2e',
            'amount'      => '200.00',
        ]);

        $this->assertSame('txn_e2e', $txn->id);
        $this->assertSame('deposit', $txn->type);
    }

    // -------------------------------------------------------------------------
    // Configuration options
    // -------------------------------------------------------------------------

    public function test_client_accepts_production_environment(): void
    {
        $mock   = new MockHandler([]);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        // Must not throw
        $client = new SusuDigitalClient(
            apiKey: 'sk_live_key',
            environment: 'production',
            httpClient: $guzzle,
        );

        $this->assertInstanceOf(SusuDigitalClient::class, $client);
    }

    public function test_client_accepts_custom_headers(): void
    {
        $mock   = new MockHandler([
            new Response(200, ['Content-Type' => 'application/json'],
                json_encode(['id' => 'c1', 'first_name' => 'A', 'last_name' => 'B', 'phone' => '+1'])),
        ]);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        $client = new SusuDigitalClient(
            apiKey: 'sk_test',
            customHeaders: ['X-Custom-Header' => 'my-value'],
            httpClient: $guzzle,
        );

        $customer = $client->customers->get('c1');
        $this->assertSame('c1', $customer->id);
    }
}
