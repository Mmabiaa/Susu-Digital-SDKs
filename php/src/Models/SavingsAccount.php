<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class SavingsAccount extends BaseModel
{
    public string $id           = '';
    public string $customer_id  = '';
    public string $account_type = SavingsAccountType::Regular->value;
    public string $currency     = 'GHS';
    public ?string $interest_rate      = null;
    public string $minimum_balance     = '0.00';
    public string $balance             = '0.00';
    public string $status              = 'active';
    public ?string $created_at         = null;
}
