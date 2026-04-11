<?php

declare(strict_types=1);

namespace SusuDigital\Http;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use SusuDigital\Exceptions\AuthenticationException;
use SusuDigital\Exceptions\NetworkException;
use SusuDigital\Exceptions\NotFoundException;
use SusuDigital\Exceptions\RateLimitException;
use SusuDigital\Exceptions\ServerException;
use SusuDigital\Exceptions\SusuDigitalException;
use SusuDigital\Exceptions\ValidationException;
use SusuDigital\Version;

/**
 * Low-level HTTP client built on Guzzle.
 *
 * Responsibilities:
 * - Injects authentication and SDK identification headers on every request.
 * - Implements automatic retry with exponential back-off and jitter.
 * - Raises domain-specific exceptions on error responses.
 *
 * @internal
 */
final class HttpClient
{
    private const BASE_URLS = [
        'production' => 'https://susu-digital.onrender.com',
        'sandbox'    => 'https://api-sandbox.susudigital.app/v1',
    ];

    private const DEFAULT_TIMEOUT     = 30.0;
    private const DEFAULT_MAX_RETRIES = 3;

    /** @var int[] HTTP status codes that trigger an automatic retry. */
    private const RETRYABLE_STATUS_CODES = [429, 500, 502, 503, 504];

    private Client $client;
    private int $maxRetries;
    private bool $enableLogging;

    /**
     * @param array<string, string> $customHeaders
     */
    public function __construct(
        string $apiKey,
        string $environment = 'sandbox',
        ?string $organization = null,
        float $timeout = self::DEFAULT_TIMEOUT,
        int $maxRetries = self::DEFAULT_MAX_RETRIES,
        array $customHeaders = [],
        bool $enableLogging = false,
        ?Client $httpClient = null,
    ) {
        $this->maxRetries    = $maxRetries;
        $this->enableLogging = $enableLogging;

        $baseUrl = self::BASE_URLS[$environment] ?? self::BASE_URLS['sandbox'];

        $headers = $this->buildDefaultHeaders($apiKey, $organization);

        if ($customHeaders !== []) {
            $headers = array_merge($headers, $customHeaders);
        }

        $this->client = $httpClient ?? new Client([
            'base_uri'                => rtrim($baseUrl, '/') . '/',
            'timeout'                 => $timeout,
            'headers'                 => $headers,
            RequestOptions::HTTP_ERRORS => false,   // We handle errors ourselves
        ]);
    }

    // -------------------------------------------------------------------------
    // Public HTTP verbs
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $params
     * @return array<string, mixed>
     */
    public function get(string $path, array $params = []): array
    {
        $options = $params !== [] ? [RequestOptions::QUERY => $params] : [];
        return $this->request('GET', $path, $options);
    }

    /**
     * @param  array<string, mixed>|null $json
     * @return array<string, mixed>
     */
    public function post(string $path, ?array $json = null): array
    {
        $options = $json !== null ? [RequestOptions::JSON => $json] : [];
        return $this->request('POST', $path, $options);
    }

    /**
     * @param  array<string, mixed>|null $json
     * @return array<string, mixed>
     */
    public function put(string $path, ?array $json = null): array
    {
        $options = $json !== null ? [RequestOptions::JSON => $json] : [];
        return $this->request('PUT', $path, $options);
    }

    /**
     * @param  array<string, mixed>|null $json
     * @return array<string, mixed>
     */
    public function patch(string $path, ?array $json = null): array
    {
        $options = $json !== null ? [RequestOptions::JSON => $json] : [];
        return $this->request('PATCH', $path, $options);
    }

    /**
     * @return array<string, mixed>
     */
    public function delete(string $path): array
    {
        return $this->request('DELETE', $path, []);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Execute an HTTP request with retry / back-off logic.
     *
     * @param  array<string, mixed> $options  Guzzle request options.
     * @return array<string, mixed>
     *
     * @throws SusuDigitalException
     */
    private function request(string $method, string $path, array $options): array
    {
        $correlationId = $this->generateId();
        $options['headers']['X-Idempotency-Key'] = $correlationId;

        $lastException = null;

        for ($attempt = 0; $attempt <= $this->maxRetries; $attempt++) {
            try {
                if ($this->enableLogging) {
                    error_log(sprintf(
                        '[SusuDigital] %s %s (attempt %d, id=%s)',
                        $method,
                        $path,
                        $attempt + 1,
                        $correlationId,
                    ));
                }

                $response = $this->client->request($method, ltrim($path, '/'), $options);
                return $this->parseResponse($response);

            } catch (ConnectException $e) {
                // Network-level failure (timeout, DNS, TCP)
                $wrapped = new NetworkException($e->getMessage(), previous: $e);
                if ($attempt < $this->maxRetries) {
                    $this->sleep((int) $this->backoffDelay($attempt));
                    $lastException = $wrapped;
                    continue;
                }
                throw $wrapped;

            } catch (RateLimitException | ServerException $e) {
                if ($e->isRetryable() && $attempt < $this->maxRetries) {
                    $delay = ($e instanceof RateLimitException)
                        ? $e->getRetryAfter()
                        : (int) $this->backoffDelay($attempt);
                    $this->sleep($delay);
                    $lastException = $e;
                    continue;
                }
                throw $e;

            } catch (RequestException $e) {
                // Guzzle HTTP error (HTTP_ERRORS is off, so this is transport-level)
                $wrapped = new NetworkException($e->getMessage(), previous: $e);
                throw $wrapped;
            }
        }

        // Should only be reached if maxRetries is 0 and all paths above return/throw
        throw $lastException ?? new SusuDigitalException('Maximum retries exceeded');
    }

    /**
     * Parse an HTTP response into a plain array, raising exceptions on errors.
     *
     * @return array<string, mixed>
     *
     * @throws SusuDigitalException
     */
    private function parseResponse(ResponseInterface $response): array
    {
        $statusCode = $response->getStatusCode();
        $requestId  = $response->getHeaderLine('X-Request-ID') ?: null;
        $body       = (string) $response->getBody();

        if ($statusCode >= 200 && $statusCode < 300) {
            if ($body === '') {
                return [];
            }

            $data = json_decode($body, true);
            if (!is_array($data)) {
                return ['_raw' => $body];
            }

            return $data;
        }

        // Attempt to decode error body
        $errorBody = [];
        if ($body !== '') {
            $decoded = json_decode($body, true);
            $errorBody = is_array($decoded) ? $decoded : ['message' => $body];
        }

        $message = (string) ($errorBody['message'] ?? $errorBody['error'] ?? 'An error occurred');
        $code    = (string) ($errorBody['code'] ?? 'UNKNOWN_ERROR');
        $details = $errorBody['details'] ?? null;

        match (true) {
            $statusCode === 401 || $statusCode === 403 => throw new AuthenticationException(
                $message, $code, $requestId, $details
            ),
            $statusCode === 404 => throw new NotFoundException(
                $message, $code, $requestId, $details
            ),
            $statusCode === 429 => throw new RateLimitException(
                $message,
                $code,
                $requestId,
                (int) ($response->getHeaderLine('Retry-After') ?: 60),
                $details,
            ),
            $statusCode === 400 || $statusCode === 422 => throw new ValidationException(
                $message,
                $code,
                $requestId,
                (array) ($errorBody['field_errors'] ?? $errorBody['errors'] ?? []),
                $details,
            ),
            $statusCode >= 500 => throw new ServerException(
                $message, $code, $requestId, $statusCode, $details
            ),
            default => throw new SusuDigitalException(
                $message, $code, $requestId, $statusCode, false, $details
            ),
        };
    }

    /**
     * Compute exponential back-off delay with ±25 % jitter.
     */
    private function backoffDelay(int $attempt, float $base = 0.5, float $cap = 30.0): float
    {
        $delay = min($base * (2 ** $attempt), $cap);
        return $delay * (0.75 + (mt_rand() / mt_getrandmax()) * 0.5);
    }

    private function sleep(int $seconds): void
    {
        if ($seconds > 0) {
            sleep($seconds);
        }
    }

    private function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * @param  string|null $organization
     * @return array<string, string>
     */
    private function buildDefaultHeaders(string $apiKey, ?string $organization): array
    {
        $headers = [
            'Authorization' => "Bearer {$apiKey}",
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'User-Agent'    => 'susudigital-php/' . Version::VERSION,
            'X-SDK-Version' => Version::VERSION,
            'X-SDK-Language' => 'php',
        ];

        if ($organization !== null) {
            $headers['X-Organization-ID'] = $organization;
        }

        return $headers;
    }
}
