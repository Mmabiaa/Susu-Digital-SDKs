<?php

declare(strict_types=1);

namespace SusuDigital\Models;

enum TransactionStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Completed  = 'completed';
    case Failed     = 'failed';
    case Reversed   = 'reversed';
}
