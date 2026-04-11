<?php

declare(strict_types=1);

namespace SusuDigital\Services;

use SusuDigital\Http\HttpClient;
use SusuDigital\Models\PagedResult;
use SusuDigital\Models\Transaction;
use SusuDigital\Models\TransactionStatus;
use SusuDigital\Models\TransactionType;

/**
 * Process payments and retrieve transaction history.
 *
 * Usage:
 *
 *   $txn = $client->transactions->deposit([
 *       'customer_id' => 'cust_123',
 *       'amount'      => '100.00',
 *       'currency'    => 'GHS',
 *   ]);
 */
final class TransactionService
{
    private const PATH = '/transactions';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * Create a deposit transaction.
     *
     * @param array<string, mixed> $data
     */
    public function deposit(array $data): Transaction
    {
        $payload = $this->clean($data);
        $payload['type'] = TransactionType::Deposit->value;
        $response = $this->http->post(self::PATH, $payload);
        return new Transaction($response);
    }

    /**
     * Create a withdrawal transaction.
     *
     * @param array<string, mixed> $data
     */
    public function withdraw(array $data): Transaction
    {
        $payload = $this->clean($data);
        $payload['type'] = TransactionType::Withdrawal->value;
        $response = $this->http->post(self::PATH, $payload);
        return new Transaction($response);
    }

    /**
     * Create a peer-to-peer transfer.
     *
     * @param array<string, mixed> $data  Must contain `from_customer_id` and `to_customer_id`.
     */
    public function transfer(array $data): Transaction
    {
        if (($data['from_customer_id'] ?? '') === ($data['to_customer_id'] ?? '')) {
            throw new \InvalidArgumentException(
                'from_customer_id and to_customer_id must be different'
            );
        }

        $payload = $this->clean($data);
        $payload['type'] = TransactionType::Transfer->value;
        $response = $this->http->post(self::PATH . '/transfer', $payload);
        return new Transaction($response);
    }

    /**
     * Retrieve a transaction by its ID.
     */
    public function get(string $transactionId): Transaction
    {
        $response = $this->http->get(self::PATH . "/{$transactionId}");
        return new Transaction($response);
    }

    /**
     * List transactions with optional filters.
     *
     * @return PagedResult<Transaction>
     */
    public function list(
        ?string $customerId = null,
        ?string $startDate = null,
        ?string $endDate = null,
        TransactionType|string|null $type = null,
        TransactionStatus|string|null $status = null,
        int $page = 1,
        int $limit = 50,
    ): PagedResult {
        $params = ['page' => $page, 'limit' => $limit];

        if ($customerId !== null) {
            $params['customer_id'] = $customerId;
        }
        if ($startDate !== null) {
            $params['start_date'] = $startDate;
        }
        if ($endDate !== null) {
            $params['end_date'] = $endDate;
        }
        if ($type !== null) {
            $params['type'] = $type instanceof TransactionType ? $type->value : $type;
        }
        if ($status !== null) {
            $params['status'] = $status instanceof TransactionStatus ? $status->value : $status;
        }

        $response = $this->http->get(self::PATH, $params);

        $result        = new PagedResult();
        $result->data  = array_map(
            static fn (array $t) => new Transaction($t),
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
