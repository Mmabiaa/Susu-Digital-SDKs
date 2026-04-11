<?php

declare(strict_types=1);

namespace SusuDigital\Services;

use SusuDigital\Http\HttpClient;
use SusuDigital\Models\AnalyticsReport;
use SusuDigital\Models\CustomerAnalytics;
use SusuDigital\Models\TransactionSummary;

/**
 * Business intelligence and reporting.
 */
final class AnalyticsService
{
    private const PATH = '/analytics';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * Retrieve analytics for a specific customer over a period.
     */
    public function getCustomerAnalytics(
        string $customerId,
        string $startDate,
        string $endDate,
    ): CustomerAnalytics {
        $params = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
        ];
        $response = $this->http->get(self::PATH . "/customers/{$customerId}", $params);
        return new CustomerAnalytics($response);
    }

    /**
     * Retrieve aggregated transaction summaries.
     *
     * @return TransactionSummary[]
     */
    public function getTransactionSummary(
        string $startDate,
        string $endDate,
        string $groupBy = 'month',
    ): array {
        $params = [
            'start_date' => $startDate,
            'end_date'   => $endDate,
            'group_by'   => $groupBy,
        ];
        $response = $this->http->get(self::PATH . '/transactions', $params);
        return array_map(
            static fn (array $item) => new TransactionSummary($item),
            $response['data'] ?? [],
        );
    }

    /**
     * Request generation of an analytics report.
     *
     * @param array<string, mixed>|null $filters
     */
    public function generateReport(
        string $reportType,
        string $startDate,
        string $endDate,
        string $format = 'json',
        ?array $filters = null,
    ): AnalyticsReport {
        $payload = [
            'report_type' => $reportType,
            'start_date'  => $startDate,
            'end_date'    => $endDate,
            'format'      => $format,
        ];

        if ($filters !== null) {
            $payload['filters'] = $filters;
        }

        $response = $this->http->post(self::PATH . '/reports', $payload);
        return new AnalyticsReport($response);
    }
}
