<?php

declare(strict_types=1);

namespace SusuDigital\Services;

use SusuDigital\Http\HttpClient;
use SusuDigital\Models\Balance;
use SusuDigital\Models\PagedResult;
use SusuDigital\Models\SavingsAccount;
use SusuDigital\Models\SavingsGoal;

/**
 * Savings account management.
 */
final class SavingsService
{
    private const PATH = '/savings';

    public function __construct(private readonly HttpClient $http) {}

    /**
     * Open a new savings account for a customer.
     *
     * @param array<string, mixed> $data  Must contain `customer_id`.
     */
    public function createAccount(array $data): SavingsAccount
    {
        $response = $this->http->post(self::PATH . '/accounts', $this->clean($data));
        return new SavingsAccount($response);
    }

    /**
     * Retrieve savings account details.
     */
    public function getAccount(string $accountId): SavingsAccount
    {
        $response = $this->http->get(self::PATH . "/accounts/{$accountId}");
        return new SavingsAccount($response);
    }

    /**
     * Retrieve the balance for a savings account.
     */
    public function getBalance(string $accountId): Balance
    {
        $response = $this->http->get(self::PATH . "/accounts/{$accountId}/balance");
        return new Balance($response);
    }

    /**
     * Create a savings goal linked to an account.
     *
     * @param array<string, mixed> $data  Must contain `account_id`, `name`, `target_amount`, `target_date`, `monthly_contribution`.
     */
    public function createGoal(array $data): SavingsGoal
    {
        $response = $this->http->post(self::PATH . '/goals', $this->clean($data));
        return new SavingsGoal($response);
    }

    /**
     * Retrieve a savings goal.
     */
    public function getGoal(string $goalId): SavingsGoal
    {
        $response = $this->http->get(self::PATH . "/goals/{$goalId}");
        return new SavingsGoal($response);
    }

    /**
     * List savings accounts.
     *
     * @return PagedResult<SavingsAccount>
     */
    public function listAccounts(
        ?string $customerId = null,
        int $page = 1,
        int $limit = 20,
    ): PagedResult {
        $params = ['page' => $page, 'limit' => $limit];

        if ($customerId !== null) {
            $params['customer_id'] = $customerId;
        }

        $response = $this->http->get(self::PATH . '/accounts', $params);

        $result        = new PagedResult();
        $result->data  = array_map(
            static fn (array $a) => new SavingsAccount($a),
            $response['data'] ?? [],
        );
        $result->total   = (int) ($response['total']    ?? count($result->data));
        $result->page    = $page;
        $result->limit   = $limit;
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
