<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class WebhookEvent extends BaseModel
{
    public string $id          = '';
    public string $type        = '';
    public string $created_at  = '';
    /** @var array<string, mixed> */
    public array $data         = [];
    public string $api_version = 'v1';
}
