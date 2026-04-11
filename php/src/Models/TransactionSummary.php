<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class TransactionSummary extends BaseModel
{
    public string $period            = '';
    public string $total_amount      = '0.00';
    public int    $transaction_count = 0;
    public string $average_amount    = '0.00';
    public string $currency          = 'GHS';
}
