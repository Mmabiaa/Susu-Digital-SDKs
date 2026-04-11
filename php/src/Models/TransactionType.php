<?php

declare(strict_types=1);

namespace SusuDigital\Models;

enum TransactionType: string
{
    case Deposit    = 'deposit';
    case Withdrawal = 'withdrawal';
    case Transfer   = 'transfer';
}
