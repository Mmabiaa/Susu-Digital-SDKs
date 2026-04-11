<?php

declare(strict_types=1);

namespace SusuDigital;

use SusuDigital\Exceptions\SusuDigitalException;

/**
 * Batch processing utilities for the Susu Digital PHP SDK.
 *
 * BatchProcessor allows high-volume operations to be split into configurable
 * chunks and executed with error isolation – a failure on one item does not
 * abort the remaining items.
 *
 * Usage:
 *
 *   $processor = new BatchProcessor($client, batchSize: 100);
 *
 *   $results = $processor->customers->createBatch([
 *       ['first_name' => 'John', 'last_name' => 'Doe', 'phone' => '+233XXXXXXXXX'],
 *       // …
 *   ]);
 *
 *   foreach ($results as $result) {
 *       if ($result->success) {
 *           echo "Created: " . $result->data->id . PHP_EOL;
 *       } else {
 *           echo "Failed: "  . $result->error->getMessage() . PHP_EOL;
 *       }
 *   }
 */

// ---------------------------------------------------------------------------
// Value objects
// ---------------------------------------------------------------------------

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

// ---------------------------------------------------------------------------
// Internal service wrapper
// ---------------------------------------------------------------------------

/**
 * Wraps a service and adds createBatch support.
 *
 * @internal
 */
final class BatchServiceWrapper
{
    public function __construct(
        private readonly object $service,
        private readonly int $batchSize,
    ) {}

    /**
     * Proxy unknown methods to the wrapped service.
     *
     * @param mixed[] $args
     */
    public function __call(string $name, array $args): mixed
    {
        return $this->service->$name(...$args);
    }

    /**
     * Create many resources in batches, collecting successes and failures.
     *
     * @param  array<int, array<string, mixed>> $items
     * @return BatchResults<mixed>
     */
    public function createBatch(array $items): BatchResults
    {
        $results = new BatchResults();

        foreach (array_chunk($items, $this->batchSize, preserve_keys: true) as $chunk) {
            foreach ($chunk as $index => $item) {
                try {
                    $data = $this->service->create($item);
                    $results->add(new BatchResult(success: true, data: $data, index: $index));
                } catch (\Throwable $e) {
                    error_log(sprintf(
                        '[SusuDigital] Batch create failed for item %d: %s',
                        $index,
                        $e->getMessage(),
                    ));
                    $results->add(new BatchResult(success: false, error: $e, index: $index));
                }
            }
        }

        return $results;
    }
}

// ---------------------------------------------------------------------------
// Public entry-point
// ---------------------------------------------------------------------------

/**
 * Process SDK operations in configurable batches.
 */
final class BatchProcessor
{
    public function __construct(
        private readonly SusuDigitalClient $client,
        private readonly int $batchSize = 100,
    ) {}

    public function getCustomers(): BatchServiceWrapper
    {
        return new BatchServiceWrapper($this->client->customers, $this->batchSize);
    }

    public function getTransactions(): BatchServiceWrapper
    {
        return new BatchServiceWrapper($this->client->transactions, $this->batchSize);
    }

    public function getLoans(): BatchServiceWrapper
    {
        return new BatchServiceWrapper($this->client->loans, $this->batchSize);
    }

    /**
     * Magic property access: $processor->customers, $processor->loans, etc.
     */
    public function __get(string $name): BatchServiceWrapper
    {
        return match ($name) {
            'customers'    => $this->getCustomers(),
            'transactions' => $this->getTransactions(),
            'loans'        => $this->getLoans(),
            default        => throw new \InvalidArgumentException(
                "BatchProcessor has no service '{$name}'"
            ),
        };
    }
}
