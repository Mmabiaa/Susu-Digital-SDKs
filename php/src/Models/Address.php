<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Address extends BaseModel
{
    public string $street  = '';
    public string $city    = '';
    public string $region  = '';
    public string $country = '';
    public ?string $postal_code = null;
}
