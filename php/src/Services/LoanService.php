<?php

declare(strict_types=1);

namespace SusuDigital\Services;

use SusuDigital\Http\HttpClient;
use SusuDigital\Models\Loan;
use SusuDigital\Models\LoanScheduleItem;
use SusuDigital\Models\LoanStatus;
use SusuDigital\Models\PagedResult;

/**
 * Loan origination and servicing.
 *
 * Usage:
 *
 *   $loan = $client->loans->createApplication([
 *       'customer_id'   => 'cust_123',
 *       'amount'        => '5000.00',
 *       'term'          => 12,
 *       'interest_rate' => '15.0',
 *       'purpose'       => 'business_expansion',
 *   ]);
 */
final class LoanService
{
    private const PATH = '/loans';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * Submit a new loan application.
     *
     * @param array<string, mixed> $data
     */
    public function createApplication(array $data): Loan
    {
        $response = $this->http->post(self::PATH . '/applications', $this->clean($data));
        return new Loan($response);
    }

    /**
     * Approve a loan application with negotiated terms.
     *
     * @param array<string, mixed> $data  Must contain `approved_amount`, `approved_term`, `approved_rate`.
     */
    public function approve(string $loanId, array $data): Loan
    {
        $response = $this->http->post(self::PATH . "/{$loanId}/approve", $this->clean($data));
        return new Loan($response);
    }

    /**
     * Disburse an approved loan.
     *
     * @param array<string, mixed> $data  Must contain `disbursement_method`.
     */
    public function disburse(string $loanId, array $data): Loan
    {
        $response = $this->http->post(self::PATH . "/{$loanId}/disburse", $this->clean($data));
        return new Loan($response);
    }

    /**
     * Record a repayment against a loan.
     *
     * @param  array<string, mixed> $data  Must contain `amount`, `payment_date`, `payment_method`.
     * @return array<string, mixed>
     */
    public function recordRepayment(string $loanId, array $data): array
    {
        return $this->http->post(self::PATH . "/{$loanId}/repayments", $this->clean($data));
    }

    /**
     * Retrieve loan details by ID.
     */
    public function get(string $loanId): Loan
    {
        $response = $this->http->get(self::PATH . "/{$loanId}");
        return new Loan($response);
    }

    /**
     * Retrieve the full repayment schedule for a loan.
     *
     * @return LoanScheduleItem[]
     */
    public function getSchedule(string $loanId): array
    {
        $response = $this->http->get(self::PATH . "/{$loanId}/schedule");
        return array_map(
            static fn (array $item) => new LoanScheduleItem($item),
            $response['data'] ?? [],
        );
    }

    /**
     * List loans with optional filters.
     *
     * @return PagedResult<Loan>
     */
    public function list(
        ?string $customerId = null,
        LoanStatus|string|null $status = null,
        int $page = 1,
        int $limit = 20,
    ): PagedResult {
        $params = ['page' => $page, 'limit' => $limit];

        if ($customerId !== null) {
            $params['customer_id'] = $customerId;
        }
        if ($status !== null) {
            $params['status'] = $status instanceof LoanStatus ? $status->value : $status;
        }

        $response = $this->http->get(self::PATH, $params);

        $result        = new PagedResult();
        $result->data  = array_map(
            static fn (array $l) => new Loan($l),
            $response['data'] ?? [],
        );
        $result->total   = (int) ($response['total']    ?? count($result->data));
        $result->page    = (int) ($response['page']     ?? $page);
        $result->limit   = (int) ($response['limit']    ?? $limit);
        $result->hasNext = (bool) ($response['has_next'] ?? false);
        $result->hasPrev = (bool) ($response['has_prev'] ?? false);

        return $result;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function clean(array $data): array
    {
        return array_filter($data, static fn ($v) => $v !== null);
    }
}
