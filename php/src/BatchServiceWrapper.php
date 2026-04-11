<?php

declare(strict_types=1);

namespace SusuDigital;

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
