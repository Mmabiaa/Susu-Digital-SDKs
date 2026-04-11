<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class Customer extends BaseModel
{
    public string $id         = '';
    public string $first_name = '';
    public string $last_name  = '';
    public string $phone      = '';
    public ?string $email         = null;
    public ?string $date_of_birth = null;
    public string $status         = CustomerStatus::Active->value;
    public ?Address $address      = null;
    public ?Identification $identification = null;
    /** @var array<string, mixed> */
    public array $metadata    = [];
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public function getFullName(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    protected function hydrate(array $data): void
    {
        parent::hydrate($data);

        if (isset($data['address']) && is_array($data['address'])) {
            $this->address = new Address($data['address']);
        }
        if (isset($data['identification']) && is_array($data['identification'])) {
            $this->identification = new Identification($data['identification']);
        }
    }
}
