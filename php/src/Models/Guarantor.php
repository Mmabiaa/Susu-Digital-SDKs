<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Guarantor extends BaseModel
{
    public string $name         = '';
    public string $phone        = '';
    public string $relationship = '';
    public ?string $email       = null;
}
