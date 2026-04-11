<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SusuDigital\Http\HttpClient;
use SusuDigital\Models\Balance;
use SusuDigital\Models\Customer;
use SusuDigital\Models\CustomerStatus;
use SusuDigital\Models\PagedResult;
use SusuDigital\Services\CustomerService;

final class CustomerServiceTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeService(array $responses): CustomerService
    {
        $mock   = new MockHandler($responses);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);
        $http   = new HttpClient(apiKey: 'sk_test', httpClient: $guzzle);
        return new CustomerService($http);
    }

    private function json(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function customerPayload(string $id = 'cust_001'): array
    {
        return [
            'id'         => $id,
            'first_name' => 'Kwame',
            'last_name'  => 'Mensah',
            'phone'      => '+233201234567',
            'status'     => 'active',
        ];
    }

    // -------------------------------------------------------------------------
    // create
    // -------------------------------------------------------------------------

    public function test_create_returns_customer(): void
    {
        $service  = $this->makeService([$this->json($this->customerPayload(), 201)]);
        $customer = $service->create([
            'first_name' => 'Kwame',
            'last_name'  => 'Mensah',
            'phone'      => '+233201234567',
        ]);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertSame('cust_001', $customer->id);
        $this->assertSame('Kwame', $customer->first_name);
    }

    public function test_create_validates_phone_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/E\.164/');

        // makeService won't be called if the exception is thrown before the request
        $service = $this->makeService([]);
        $service->create([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'phone'      => '0201234567', // missing "+"
        ]);
    }

    // -------------------------------------------------------------------------
    // get
    // -------------------------------------------------------------------------

    public function test_get_returns_customer(): void
    {
        $service  = $this->makeService([$this->json($this->customerPayload('cust_xyz'))]);
        $customer = $service->get('cust_xyz');

        $this->assertSame('cust_xyz', $customer->id);
    }

    // -------------------------------------------------------------------------
    // update
    // -------------------------------------------------------------------------

    public function test_update_returns_updated_customer(): void
    {
        $payload           = $this->customerPayload();
        $payload['email']  = 'updated@example.com';
        $service           = $this->makeService([$this->json($payload)]);
        $customer          = $service->update('cust_001', ['email' => 'updated@example.com']);

        $this->assertSame('updated@example.com', $customer->email);
    }

    // -------------------------------------------------------------------------
    // delete
    // -------------------------------------------------------------------------

    public function test_delete_sends_delete_request(): void
    {
        $service = $this->makeService([new Response(204, [], '')]);
        $service->delete('cust_001'); // must not throw

        $this->assertTrue(true);
    }

    // -------------------------------------------------------------------------
    // getBalance
    // -------------------------------------------------------------------------

    public function test_get_balance_returns_balance(): void
    {
        $service = $this->makeService([$this->json([
            'customer_id' => 'cust_001',
            'currency'    => 'GHS',
            'available'   => '500.00',
            'ledger'      => '500.00',
            'pending'     => '0.00',
        ])]);

        $balance = $service->getBalance('cust_001');

        $this->assertInstanceOf(Balance::class, $balance);
        $this->assertSame('500.00', $balance->available);
        $this->assertSame('GHS', $balance->currency);
    }

    // -------------------------------------------------------------------------
    // list
    // -------------------------------------------------------------------------

    public function test_list_returns_paged_result(): void
    {
        $service = $this->makeService([$this->json([
            'data'     => [$this->customerPayload(), $this->customerPayload('cust_002')],
            'total'    => 2,
            'page'     => 1,
            'limit'    => 50,
            'has_next' => false,
            'has_prev' => false,
        ])]);

        $result = $service->list();

        $this->assertInstanceOf(PagedResult::class, $result);
        $this->assertCount(2, $result->data);
        $this->assertSame(2, $result->total);
        $this->assertFalse($result->hasNext);
    }

    public function test_list_with_status_enum_filter(): void
    {
        $service = $this->makeService([$this->json([
            'data'  => [$this->customerPayload()],
            'total' => 1,
            'page'  => 1,
            'limit' => 50,
        ])]);

        $result = $service->list(status: CustomerStatus::Active);

        $this->assertCount(1, $result->data);
    }

    public function test_list_with_string_status_filter(): void
    {
        $service = $this->makeService([$this->json([
            'data'  => [],
            'total' => 0,
            'page'  => 1,
            'limit' => 50,
        ])]);

        $result = $service->list(status: 'inactive');

        $this->assertSame(0, $result->total);
    }
}
