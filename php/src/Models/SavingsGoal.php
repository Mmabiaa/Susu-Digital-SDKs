<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class SavingsGoal extends BaseModel
{
    public string $id                   = '';
    public string $account_id           = '';
    public string $name                 = '';
    public string $target_amount        = '0.00';
    public string $current_amount       = '0.00';
    public string $monthly_contribution = '0.00';
    public string $target_date          = '';
    public string $status               = 'active';
    public ?string $progress_percent    = null;
    public ?string $created_at          = null;
}
