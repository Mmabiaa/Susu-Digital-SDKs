<?php

declare(strict_types=1);

namespace SusuDigital\Models;

// ---------------------------------------------------------------------------
// Enums (PHP 8.1+)
// ---------------------------------------------------------------------------

enum CustomerStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';
    case Pending   = 'pending';
}

enum TransactionType: string
{
    case Deposit    = 'deposit';
    case Withdrawal = 'withdrawal';
    case Transfer   = 'transfer';
}

enum TransactionStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
    case Reversed   = 'reversed';
}

enum LoanStatus: string
{
    case Pending     = 'pending';
    case UnderReview = 'under_review';
    case Approved    = 'approved';
    case Disbursed   = 'disbursed';
    case Active      = 'active';
    case Closed      = 'closed';
    case Defaulted   = 'defaulted';
    case Rejected    = 'rejected';
}

enum SavingsAccountType: string
{
    case Regular = 'regular';
    case Fixed   = 'fixed';
    case Susu    = 'susu';
}

enum CollateralType: string
{
    case Property  = 'property';
    case Vehicle   = 'vehicle';
    case Equipment = 'equipment';
    case Savings   = 'savings';
    case Other     = 'other';
}

// ---------------------------------------------------------------------------
// Shared sub-models
// ---------------------------------------------------------------------------

final class Address extends BaseModel
{
    public string $street  = '';
    public string $city    = '';
    public string $region  = '';
    public string $country = '';
    public ?string $postal_code = null;
}

final class Identification extends BaseModel
{
    public string $type   = '';
    public string $number = '';
    public ?string $expiry_date = null;
    public ?string $issue_date  = null;
}

// ---------------------------------------------------------------------------
// Customer models
// ---------------------------------------------------------------------------

final class Customer extends BaseModel
{
    public string $id         = '';
    public string $first_name = '';
    public string $last_name  = '';
    public string $phone      = '';
    public ?string $email         = null;
    public ?string $date_of_birth = null;
    public string $status         = CustomerStatus::Active->value;
    public ?Address $address      = null;
    public ?Identification $identification = null;
    /** @var array<string, mixed> */
    public array $metadata    = [];
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected function hydrate(array $data): void
    {
        parent::hydrate($data);

        if (isset($data['address']) && is_array($data['address'])) {
            $this->address = new Address($data['address']);
        }
        if (isset($data['identification']) && is_array($data['identification'])) {
            $this->identification = new Identification($data['identification']);
        }
    }
}

final class Balance extends BaseModel
{
    public string $customer_id = '';
    public string $currency    = 'GHS';
    public string $available   = '0.00';
    public string $ledger      = '0.00';
    public string $pending     = '0.00';
    public ?string $as_of      = null;
}

// ---------------------------------------------------------------------------
// Transaction models
// ---------------------------------------------------------------------------

final class Transaction extends BaseModel
{
    public string $id          = '';
    public string $customer_id = '';
    public string $type        = '';
    public string $amount      = '0.00';
    public string $currency    = 'GHS';
    public string $status      = '';
    public ?string $description  = null;
    public ?string $reference    = null;
    /** @var array<string, mixed> */
    public array $metadata     = [];
    public ?string $created_at   = null;
    public ?string $completed_at = null;
}

// ---------------------------------------------------------------------------
// Loan models
// ---------------------------------------------------------------------------

final class Collateral extends BaseModel
{
    public string $type        = CollateralType::Other->value;
    public ?string $description = null;
    public string $value       = '0.00';
}

final class Guarantor extends BaseModel
{
    public string $name         = '';
    public string $phone        = '';
    public string $relationship = '';
    public ?string $email       = null;
}

final class LoanScheduleItem extends BaseModel
{
    public int $installment_number = 0;
    public string $due_date        = '';
    public string $principal       = '0.00';
    public string $interest        = '0.00';
    public string $total           = '0.00';
    public string $outstanding_balance = '0.00';
    public string $status          = '';
}

final class Loan extends BaseModel
{
    public string $id           = '';
    public string $customer_id  = '';
    public string $amount       = '0.00';
    public string $currency     = 'GHS';
    public int    $term         = 0;
    public string $interest_rate = '0.00';
    public string $purpose      = '';
    public string $status       = LoanStatus::Pending->value;
    public ?string $disbursed_amount      = null;
    public ?string $outstanding_balance   = null;
    public ?Collateral $collateral        = null;
    /** @var Guarantor[] */
    public array $guarantors    = [];
    public ?string $created_at  = null;
    public ?string $disbursed_at = null;

    protected function hydrate(array $data): void
    {
        parent::hydrate($data);

        if (isset($data['collateral']) && is_array($data['collateral'])) {
            $this->collateral = new Collateral($data['collateral']);
        }

        if (isset($data['guarantors']) && is_array($data['guarantors'])) {
            $this->guarantors = array_map(
                static fn (array $g) => new Guarantor($g),
                $data['guarantors']
            );
        }
    }
}

// ---------------------------------------------------------------------------
// Savings models
// ---------------------------------------------------------------------------

final class SavingsAccount extends BaseModel
{
    public string $id          = '';
    public string $customer_id = '';
    public string $account_type = SavingsAccountType::Regular->value;
    public string $currency    = 'GHS';
    public ?string $interest_rate      = null;
    public string $minimum_balance     = '0.00';
    public string $balance             = '0.00';
    public string $status              = 'active';
    public ?string $created_at         = null;
}

final class SavingsGoal extends BaseModel
{
    public string $id           = '';
    public string $account_id   = '';
    public string $name         = '';
    public string $target_amount = '0.00';
    public string $current_amount = '0.00';
    public string $monthly_contribution = '0.00';
    public string $target_date  = '';
    public string $status       = 'active';
    public ?string $progress_percent = null;
    public ?string $created_at  = null;
}

// ---------------------------------------------------------------------------
// Analytics models
// ---------------------------------------------------------------------------

final class CustomerAnalytics extends BaseModel
{
    public string $customer_id        = '';
    public string $total_deposits     = '0.00';
    public string $total_withdrawals  = '0.00';
    public int    $total_loans        = 0;
    public int    $active_loans       = 0;
    public string $savings_balance    = '0.00';
    public int    $transaction_count  = 0;
    public string $period_start       = '';
    public string $period_end         = '';
}

final class TransactionSummary extends BaseModel
{
    public string $period           = '';
    public string $total_amount     = '0.00';
    public int    $transaction_count = 0;
    public string $average_amount   = '0.00';
    public string $currency         = 'GHS';
}

final class AnalyticsReport extends BaseModel
{
    public string $id          = '';
    public string $report_type = '';
    public string $format      = 'json';
    public string $status      = '';
    public ?string $download_url = null;
    public ?string $created_at   = null;
    public ?string $expires_at   = null;
}

// ---------------------------------------------------------------------------
// Pagination
// ---------------------------------------------------------------------------

/**
 * Generic paginated result returned by list endpoints.
 *
 * @template T of BaseModel
 */
final class PagedResult
{
    /** @var BaseModel[] */
    public array $data   = [];
    public int $total    = 0;
    public int $page     = 1;
    public int $limit    = 50;
    public bool $hasNext = false;
    public bool $hasPrev = false;

    public function getTotalPages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}

// ---------------------------------------------------------------------------
// Webhook event
// ---------------------------------------------------------------------------

final class WebhookEvent extends BaseModel
{
    public string $id          = '';
    public string $type        = '';
    public string $created_at  = '';
    /** @var array<string, mixed> */
    public array $data         = [];
    public string $api_version = 'v1';
}
