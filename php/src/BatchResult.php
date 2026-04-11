<?php

declare(strict_types=1);

namespace SusuDigital;

/**
 * Result of a single item within a batch operation.
 *
 * @template T
 */
final class BatchResult
{
    /**
     * @param T|null $data
     */
    public function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly ?\Throwable $error = null,
        public readonly int $index = 0,
    ) {}
}
