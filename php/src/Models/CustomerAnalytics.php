<?php

declare(strict_types=1);

namespace SusuDigital\Models;

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
