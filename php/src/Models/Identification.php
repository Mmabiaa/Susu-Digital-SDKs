<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Identification extends BaseModel
{
    public string $type   = '';
    public string $number = '';
    public ?string $expiry_date = null;
    public ?string $issue_date  = null;
}
