<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Balance extends BaseModel
{
    public string $customer_id = '';
    public string $currency    = 'GHS';
    public string $available   = '0.00';
    public string $ledger      = '0.00';
    public string $pending     = '0.00';
    public ?string $as_of      = null;
}
