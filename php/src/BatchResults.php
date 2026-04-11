<?php

declare(strict_types=1);

namespace SusuDigital;

/**
 * Aggregate result of a full batch operation.
 *
 * @template T
 */
final class BatchResults implements \Countable, \IteratorAggregate
{
    /** @var BatchResult<T>[] */
    private array $results = [];

    /**
     * @param BatchResult<T> $result
     */
    public function add(BatchResult $result): void
    {
        $this->results[] = $result;
    }

    /** @return BatchResult<T>[] */
    public function all(): array
    {
        return $this->results;
    }

    /** @return T[] */
    public function successful(): array
    {
        return array_values(array_map(
            static fn (BatchResult $r) => $r->data,
            array_filter($this->results, static fn (BatchResult $r) => $r->success && $r->data !== null),
        ));
    }

    /** @return BatchResult<T>[] */
    public function failed(): array
    {
        return array_values(array_filter(
            $this->results,
            static fn (BatchResult $r) => !$r->success,
        ));
    }

    public function successCount(): int
    {
        return count(array_filter($this->results, static fn (BatchResult $r) => $r->success));
    }

    public function failureCount(): int
    {
        return count(array_filter($this->results, static fn (BatchResult $r) => !$r->success));
    }

    public function count(): int
    {
        return count($this->results);
    }

    /** @return \ArrayIterator<int, BatchResult<T>> */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->results);
    }
}
