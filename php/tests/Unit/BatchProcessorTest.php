<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SusuDigital\BatchProcessor;
use SusuDigital\BatchResult;
use SusuDigital\BatchResults;
use SusuDigital\Http\HttpClient;
use SusuDigital\SusuDigitalClient;

final class BatchProcessorTest extends TestCase
{
    private function makeClient(array $responses): SusuDigitalClient
    {
        $mock   = new MockHandler($responses);
        $stack  = HandlerStack::create($mock);
        $guzzle = new GuzzleClient(['handler' => $stack, 'http_errors' => false]);

        return new SusuDigitalClient(
            apiKey: 'sk_test',
            httpClient: $guzzle,
        );
    }

    private function json(array $data, int $status = 201): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    private function customerPayload(int $n): array
    {
        return [
            'id'         => "cust_{$n}",
            'first_name' => "Customer{$n}",
            'last_name'  => 'Test',
            'phone'      => '+233201234567',
            'status'     => 'active',
        ];
    }

    // -------------------------------------------------------------------------
    // BatchResult value objects
    // -------------------------------------------------------------------------

    public function test_batch_result_success(): void
    {
        $result = new BatchResult(success: true, data: 'payload', index: 0);

        $this->assertTrue($result->success);
        $this->assertSame('payload', $result->data);
        $this->assertNull($result->error);
        $this->assertSame(0, $result->index);
    }

    public function test_batch_result_failure(): void
    {
        $exc = new \RuntimeException('fail');
        $result = new BatchResult(success: false, error: $exc, index: 5);

        $this->assertFalse($result->success);
        $this->assertNull($result->data);
        $this->assertSame($exc, $result->error);
        $this->assertSame(5, $result->index);
    }

    // -------------------------------------------------------------------------
    // BatchResults aggregate
    // -------------------------------------------------------------------------

    public function test_batch_results_counts(): void
    {
        $results = new BatchResults();
        $results->add(new BatchResult(success: true, data: 'a', index: 0));
        $results->add(new BatchResult(success: false, error: new \RuntimeException(), index: 1));
        $results->add(new BatchResult(success: true, data: 'b', index: 2));

        $this->assertSame(3, count($results));
        $this->assertSame(2, $results->successCount());
        $this->assertSame(1, $results->failureCount());
        $this->assertSame(['a', 'b'], $results->successful());
        $this->assertCount(1, $results->failed());
    }

    public function test_batch_results_is_iterable(): void
    {
        $results = new BatchResults();
        $results->add(new BatchResult(success: true, data: 'x', index: 0));

        $items = [];
        foreach ($results as $r) {
            $items[] = $r;
        }

        $this->assertCount(1, $items);
        $this->assertInstanceOf(BatchResult::class, $items[0]);
    }

    // -------------------------------------------------------------------------
    // BatchProcessor with SusuDigitalClient
    // -------------------------------------------------------------------------

    public function test_create_batch_all_success(): void
    {
        $responses = [
            $this->json($this->customerPayload(1)),
            $this->json($this->customerPayload(2)),
            $this->json($this->customerPayload(3)),
        ];

        $client    = $this->makeClient($responses);
        $processor = new BatchProcessor($client, batchSize: 10);

        $items = array_map(fn ($n) => [
            'first_name' => "Customer{$n}",
            'last_name'  => 'Test',
            'phone'      => '+233201234567',
        ], [1, 2, 3]);

        $results = $processor->customers->createBatch($items);

        $this->assertSame(3, $results->successCount());
        $this->assertSame(0, $results->failureCount());
    }

    public function test_create_batch_partial_failure_isolated(): void
    {
        $responses = [
            $this->json($this->customerPayload(1)),
            new Response(422, ['Content-Type' => 'application/json'],
                json_encode(['message' => 'Validation failed'])),
            $this->json($this->customerPayload(3)),
        ];

        $client    = $this->makeClient($responses);
        $processor = new BatchProcessor($client, batchSize: 10);

        $items = array_map(fn ($n) => [
            'first_name' => "C{$n}",
            'last_name'  => 'T',
            'phone'      => '+233',
        ], [1, 2, 3]);

        $results = $processor->customers->createBatch($items);

        $this->assertSame(2, $results->successCount());
        $this->assertSame(1, $results->failureCount());
    }

    public function test_processor_magic_property_access(): void
    {
        $client    = $this->makeClient([]);
        $processor = new BatchProcessor($client);

        // Should return a BatchServiceWrapper without throwing
        $this->assertNotNull($processor->customers);
        $this->assertNotNull($processor->transactions);
        $this->assertNotNull($processor->loans);
    }

    public function test_processor_invalid_service_throws(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $client    = $this->makeClient([]);
        $processor = new BatchProcessor($client);
        $_ = $processor->nonexistent; // @phpstan-ignore-line
    }
}
