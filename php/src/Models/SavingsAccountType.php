<?php

declare(strict_types=1);

namespace SusuDigital\Models;

enum SavingsAccountType: string
{
    case Regular = 'regular';
    case Fixed   = 'fixed';
    case Susu    = 'susu';
}
