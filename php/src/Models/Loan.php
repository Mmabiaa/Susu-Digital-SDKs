<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Loan extends BaseModel
{
    public string $id            = '';
    public string $customer_id   = '';
    public string $amount        = '0.00';
    public string $currency      = 'GHS';
    public int    $term          = 0;
    public string $interest_rate = '0.00';
    public string $purpose       = '';
    public string $status        = LoanStatus::Pending->value;
    public ?string $disbursed_amount      = null;
    public ?string $outstanding_balance   = null;
    public ?Collateral $collateral        = null;
    /** @var Guarantor[] */
    public array $guarantors     = [];
    public ?string $created_at   = null;
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
