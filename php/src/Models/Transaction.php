<?php

declare(strict_types=1);

namespace SusuDigital\Models;

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
