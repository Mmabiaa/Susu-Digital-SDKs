<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SusuDigital\Http\HttpClient;
use SusuDigital\Models\PagedResult;
use SusuDigital\Models\Transaction;
use SusuDigital\Models\TransactionStatus;
use SusuDigital\Models\TransactionType;
use SusuDigital\Services\TransactionService;

final class TransactionServiceTest extends TestCase
{
    private function makeService(array $responses): TransactionService
    {
        $mock   = new MockHandler($responses);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);
        $http   = new HttpClient(apiKey: 'sk_test', httpClient: $guzzle);
        return new TransactionService($http);
    }

    private function json(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function txnPayload(string $type = 'deposit'): array
    {
        return [
            'id'          => 'txn_001',
            'customer_id' => 'cust_001',
            'type'        => $type,
            'amount'      => '100.00',
            'currency'    => 'GHS',
            'status'      => 'completed',
        ];
    }

    public function test_deposit_returns_transaction(): void
    {
        $service = $this->makeService([$this->json($this->txnPayload('deposit'), 201)]);
        $txn     = $service->deposit([
            'customer_id' => 'cust_001',
            'amount'      => '100.00',
        ]);

        $this->assertInstanceOf(Transaction::class, $txn);
        $this->assertSame('deposit', $txn->type);
        $this->assertSame('100.00', $txn->amount);
    }

    public function test_withdraw_returns_transaction(): void
    {
        $service = $this->makeService([$this->json($this->txnPayload('withdrawal'), 201)]);
        $txn     = $service->withdraw([
            'customer_id' => 'cust_001',
            'amount'      => '50.00',
        ]);

        $this->assertSame('withdrawal', $txn->type);
    }

    public function test_transfer_returns_transaction(): void
    {
        $service = $this->makeService([$this->json($this->txnPayload('transfer'), 201)]);
        $txn     = $service->transfer([
            'from_customer_id' => 'cust_001',
            'to_customer_id'   => 'cust_002',
            'amount'           => '75.00',
        ]);

        $this->assertSame('transfer', $txn->type);
    }

    public function test_transfer_validates_different_customers(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must be different/');

        $service = $this->makeService([]);
        $service->transfer([
            'from_customer_id' => 'cust_001',
            'to_customer_id'   => 'cust_001', // same!
            'amount'           => '10.00',
        ]);
    }

    public function test_get_returns_transaction(): void
    {
        $service = $this->makeService([$this->json($this->txnPayload())]);
        $txn     = $service->get('txn_001');

        $this->assertSame('txn_001', $txn->id);
    }

    public function test_list_returns_paged_result(): void
    {
        $service = $this->makeService([$this->json([
            'data'     => [$this->txnPayload(), $this->txnPayload('withdrawal')],
            'total'    => 2,
            'page'     => 1,
            'limit'    => 50,
            'has_next' => false,
            'has_prev' => false,
        ])]);

        $result = $service->list();

        $this->assertInstanceOf(PagedResult::class, $result);
        $this->assertCount(2, $result->data);
    }

    public function test_list_with_type_enum_filter(): void
    {
        $service = $this->makeService([$this->json([
            'data'  => [$this->txnPayload('deposit')],
            'total' => 1,
            'page'  => 1,
            'limit' => 50,
        ])]);

        $result = $service->list(type: TransactionType::Deposit);

        $this->assertCount(1, $result->data);
    }

    public function test_list_with_string_type_and_status_filter(): void
    {
        $service = $this->makeService([$this->json([
            'data'  => [],
            'total' => 0,
            'page'  => 1,
            'limit' => 50,
        ])]);

        $result = $service->list(
            type: 'withdrawal',
            status: TransactionStatus::Failed,
        );

        $this->assertSame(0, $result->total);
    }
}
