<?php

declare(strict_types=1);

namespace SusuDigital\Services;

use SusuDigital\Http\HttpClient;
use SusuDigital\Models\Balance;
use SusuDigital\Models\Customer;
use SusuDigital\Models\CustomerStatus;
use SusuDigital\Models\PagedResult;

/**
 * Manage Susu Digital customers.
 *
 * Usage:
 *
 *   $customer = $client->customers->create([
 *       'first_name' => 'John',
 *       'last_name'  => 'Doe',
 *       'phone'      => '+233XXXXXXXXX',
 *   ]);
 */
final class CustomerService
{
    private const PATH = '/customers';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * Create a new customer.
     *
     * @param array<string, mixed> $data
     */
    public function create(array $data): Customer
    {
        $this->validatePhone($data['phone'] ?? '');
        $response = $this->http->post(self::PATH, $this->clean($data));
        return new Customer($response);
    }

    /**
     * Retrieve a customer by their ID.
     */
    public function get(string $customerId): Customer
    {
        $response = $this->http->get(self::PATH . "/{$customerId}");
        return new Customer($response);
    }

    /**
     * Update customer fields (only provided fields are updated).
     *
     * @param array<string, mixed> $data
     */
    public function update(string $customerId, array $data): Customer
    {
        $response = $this->http->patch(self::PATH . "/{$customerId}", $this->clean($data));
        return new Customer($response);
    }

    /**
     * Delete (deactivate) a customer record.
     */
    public function delete(string $customerId): void
    {
        $this->http->delete(self::PATH . "/{$customerId}");
    }

    /**
     * Retrieve a customer's current balance.
     */
    public function getBalance(string $customerId): Balance
    {
        $response = $this->http->get(self::PATH . "/{$customerId}/balance");
        return new Balance($response);
    }

    /**
     * List customers with optional filtering and pagination.
     *
     * @return PagedResult<Customer>
     */
    public function list(
        int $page = 1,
        int $limit = 50,
        ?string $search = null,
        CustomerStatus|string|null $status = null,
    ): PagedResult {
        $params = ['page' => $page, 'limit' => $limit];

        if ($search !== null) {
            $params['search'] = $search;
        }

        if ($status !== null) {
            $params['status'] = $status instanceof CustomerStatus
                ? $status->value
                : $status;
        }

        $response = $this->http->get(self::PATH, $params);

        $result        = new PagedResult();
        $result->data  = array_map(
            static fn (array $c) => new Customer($c),
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

    private function validatePhone(string $phone): void
    {
        if ($phone !== '' && !str_starts_with($phone, '+')) {
            throw new \InvalidArgumentException(
                'Phone must be in E.164 format (e.g. +233XXXXXXXXX)'
            );
        }
    }
}
