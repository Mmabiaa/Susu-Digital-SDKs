<?php

declare(strict_types=1);

namespace SusuDigital;

use GuzzleHttp\Client as GuzzleClient;
use SusuDigital\Http\HttpClient;
use SusuDigital\Services\AnalyticsService;
use SusuDigital\Services\CustomerService;
use SusuDigital\Services\LoanService;
use SusuDigital\Services\SavingsService;
use SusuDigital\Services\TransactionService;

/**
 * Primary entry-point for the Susu Digital PHP SDK.
 *
 * Usage:
 *
 *   use SusuDigital\SusuDigitalClient;
 *
 *   $client = new SusuDigitalClient(
 *       apiKey:      'sk_live_...',
 *       environment: 'production',
 *       organization: 'org_...',
 *       timeout:     30,
 *       maxRetries:  3,
 *   );
 *
 *   // Access services as properties
 *   $customer = $client->customers->create([...]);
 *   $txn      = $client->transactions->deposit([...]);
 *   $loan     = $client->loans->createApplication([...]);
 *
 *   // Context-manager-style via try/finally is idiomatic PHP:
 *   try {
 *       $data = $client->customers->get('cust_123');
 *   } finally {
 *       // HttpClient releases Guzzle connection pool automatically
 *   }
 */
final class SusuDigitalClient
{
    private readonly HttpClient $http;

    public readonly CustomerService    $customers;
    public readonly TransactionService $transactions;
    public readonly LoanService        $loans;
    public readonly SavingsService     $savings;
    public readonly AnalyticsService   $analytics;

    /**
     * @param string                $apiKey        Your Susu Digital API key (sk_live_... or sk_test_...).
     * @param string                $environment   'production' or 'sandbox' (default: 'sandbox').
     * @param string|null           $organization  Optional organization ID to scope requests.
     * @param float                 $timeout       HTTP timeout in seconds (default: 30).
     * @param int                   $maxRetries    Maximum number of automatic retries (default: 3).
     * @param bool                  $enableLogging Enable structured SDK-level error_log output.
     * @param array<string, string> $customHeaders Additional HTTP headers to include on every request.
     * @param GuzzleClient|null     $httpClient    Bring-your-own Guzzle client (for testing or connection pooling).
     */
    public function __construct(
        string $apiKey,
        string $environment = 'sandbox',
        ?string $organization = null,
        float $timeout = 30.0,
        int $maxRetries = 3,
        bool $enableLogging = false,
        array $customHeaders = [],
        ?GuzzleClient $httpClient = null,
    ) {
        $this->http = new HttpClient(
            apiKey: $apiKey,
            environment: $environment,
            organization: $organization,
            timeout: $timeout,
            maxRetries: $maxRetries,
            customHeaders: $customHeaders,
            enableLogging: $enableLogging,
            httpClient: $httpClient,
        );

        $this->customers    = new CustomerService($this->http);
        $this->transactions = new TransactionService($this->http);
        $this->loans        = new LoanService($this->http);
        $this->savings      = new SavingsService($this->http);
        $this->analytics    = new AnalyticsService($this->http);
    }

    public function __toString(): string
    {
        return sprintf('SusuDigitalClient(version=%s)', Version::VERSION);
    }
}
