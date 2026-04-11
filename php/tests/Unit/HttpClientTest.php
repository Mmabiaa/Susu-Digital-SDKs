<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;
use SusuDigital\Exceptions\AuthenticationException;
use SusuDigital\Exceptions\NetworkException;
use SusuDigital\Exceptions\NotFoundException;
use SusuDigital\Exceptions\RateLimitException;
use SusuDigital\Exceptions\ServerException;
use SusuDigital\Exceptions\ValidationException;
use SusuDigital\Http\HttpClient;

final class HttpClientTest extends TestCase
{
    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build an HttpClient backed by a Guzzle MockHandler.
     *
     * @param Response[] $responses
     */
    private function makeClient(array $responses, int $maxRetries = 0): HttpClient
    {
        $mock    = new MockHandler($responses);
        $stack   = HandlerStack::create($mock);
        $guzzle  = new GuzzleClient([
            'handler'                      => $stack,
            'http_errors'                  => false,
        ]);

        return new HttpClient(
            apiKey: 'sk_test_key',
            httpClient: $guzzle,
            maxRetries: $maxRetries,
        );
    }

    private function json(array $data, int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], json_encode($data));
    }

    // -------------------------------------------------------------------------
    // 2xx – success
    // -------------------------------------------------------------------------

    public function test_get_returns_decoded_json(): void
    {
        $client = $this->makeClient([
            $this->json(['id' => 'cust_1', 'first_name' => 'Kwame']),
        ]);

        $result = $client->get('/customers/cust_1');

        $this->assertSame('cust_1', $result['id']);
        $this->assertSame('Kwame', $result['first_name']);
    }

    public function test_post_returns_decoded_json(): void
    {
        $client = $this->makeClient([
            $this->json(['id' => 'txn_1', 'amount' => '100.00'], 201),
        ]);

        $result = $client->post('/transactions', ['amount' => '100.00', 'customer_id' => 'c1']);

        $this->assertSame('txn_1', $result['id']);
    }

    public function test_empty_body_on_204_returns_empty_array(): void
    {
        $client = $this->makeClient([
            new Response(204, [], ''),
        ]);

        $result = $client->delete('/customers/cust_1');

        $this->assertSame([], $result);
    }

    // -------------------------------------------------------------------------
    // 4xx / 5xx – error mapping
    // -------------------------------------------------------------------------

    public function test_401_raises_authentication_exception(): void
    {
        $this->expectException(AuthenticationException::class);

        $client = $this->makeClient([
            $this->json(['message' => 'Unauthorized', 'code' => 'AUTH_FAILED'], 401),
        ]);

        $client->get('/customers');
    }

    public function test_403_raises_authentication_exception(): void
    {
        $this->expectException(AuthenticationException::class);

        $client = $this->makeClient([
            $this->json(['message' => 'Forbidden'], 403),
        ]);

        $client->get('/customers');
    }

    public function test_404_raises_not_found_exception(): void
    {
        $this->expectException(NotFoundException::class);

        $client = $this->makeClient([
            $this->json(['message' => 'Customer not found', 'code' => 'NOT_FOUND'], 404),
        ]);

        $client->get('/customers/missing');
    }

    public function test_429_raises_rate_limit_exception(): void
    {
        $this->expectException(RateLimitException::class);

        $client = $this->makeClient([
            new Response(429, ['Retry-After' => '30', 'Content-Type' => 'application/json'],
                json_encode(['message' => 'Too many requests'])),
        ]);

        $client->get('/customers');
    }

    public function test_429_carries_retry_after_value(): void
    {
        $client = $this->makeClient([
            new Response(429, ['Retry-After' => '45', 'Content-Type' => 'application/json'],
                json_encode(['message' => 'Rate limited'])),
        ]);

        try {
            $client->get('/customers');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertSame(45, $e->getRetryAfter());
        }
    }

    public function test_422_raises_validation_exception(): void
    {
        $this->expectException(ValidationException::class);

        $client = $this->makeClient([
            $this->json([
                'message'      => 'Validation failed',
                'code'         => 'VALIDATION_ERROR',
                'field_errors' => ['phone' => ['Invalid format']],
            ], 422),
        ]);

        $client->post('/customers', ['phone' => 'bad']);
    }

    public function test_validation_exception_carries_field_errors(): void
    {
        $client = $this->makeClient([
            $this->json([
                'message'      => 'Validation failed',
                'field_errors' => ['email' => ['Must be valid email']],
            ], 422),
        ]);

        try {
            $client->post('/customers', []);
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertSame(['email' => ['Must be valid email']], $e->getFieldErrors());
        }
    }

    public function test_500_raises_server_exception(): void
    {
        $this->expectException(ServerException::class);

        $client = $this->makeClient([
            $this->json(['message' => 'Internal Server Error'], 500),
        ]);

        $client->get('/analytics');
    }

    public function test_503_raises_server_exception(): void
    {
        $this->expectException(ServerException::class);

        $client = $this->makeClient([
            $this->json(['message' => 'Service unavailable'], 503),
        ]);

        $client->get('/health');
    }

    // -------------------------------------------------------------------------
    // Error body extraction
    // -------------------------------------------------------------------------

    public function test_non_json_error_body_is_handled_gracefully(): void
    {
        $client = $this->makeClient([
            new Response(500, [], 'Plain text error'),
        ]);

        try {
            $client->get('/health');
            $this->fail('Expected ServerException');
        } catch (ServerException $e) {
            $this->assertStringContainsString('Plain text error', $e->getMessage());
        }
    }

    public function test_request_id_header_is_captured(): void
    {
        $client = $this->makeClient([
            new Response(404, [
                'Content-Type' => 'application/json',
                'X-Request-ID' => 'req_abc123',
            ], json_encode(['message' => 'Not found'])),
        ]);

        try {
            $client->get('/missing');
        } catch (NotFoundException $e) {
            $this->assertSame('req_abc123', $e->getRequestId());
        }
    }

    // -------------------------------------------------------------------------
    // HTTP verbs
    // -------------------------------------------------------------------------

    public function test_put_sends_json_body(): void
    {
        $client = $this->makeClient([
            $this->json(['updated' => true]),
        ]);

        $result = $client->put('/customers/c1', ['email' => 'new@example.com']);

        $this->assertTrue($result['updated']);
    }

    public function test_patch_sends_json_body(): void
    {
        $client = $this->makeClient([
            $this->json(['patched' => true]),
        ]);

        $result = $client->patch('/customers/c1', ['first_name' => 'Updated']);

        $this->assertTrue($result['patched']);
    }
}
