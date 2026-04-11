<?php

declare(strict_types=1);

namespace SusuDigital\Models;

/**
 * Generic paginated result returned by list endpoints.
 *
 * @template T of BaseModel
 */
final class PagedResult
{
    /** @var BaseModel[] */
    public array $data   = [];
    public int $total    = 0;
    public int $page     = 1;
    public int $limit    = 50;
    public bool $hasNext = false;
    public bool $hasPrev = false;

    public function getTotalPages(): int
    {
        return $this->limit > 0 ? (int) ceil($this->total / $this->limit) : 0;
    }
}
