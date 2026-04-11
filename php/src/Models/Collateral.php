<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Collateral extends BaseModel
{
    public string $type        = CollateralType::Other->value;
    public ?string $description = null;
    public string $value       = '0.00';
}
