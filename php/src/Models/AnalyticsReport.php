<?php

declare(strict_types=1);

namespace SusuDigital\Models;

final class AnalyticsReport extends BaseModel
{
    public string $id           = '';
    public string $report_type  = '';
    public string $format       = 'json';
    public string $status       = '';
    public ?string $download_url = null;
    public ?string $created_at   = null;
    public ?string $expires_at   = null;
}
