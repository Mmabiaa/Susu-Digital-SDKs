<?php

declare(strict_types=1);

namespace SusuDigital\Models;

enum CustomerStatus: string
{
    case Active    = 'active';
    case Inactive  = 'inactive';
    case Suspended = 'suspended';
    case Pending   = 'pending';
}
