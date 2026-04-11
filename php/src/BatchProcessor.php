<?php

declare(strict_types=1);

namespace SusuDigital;

/**
 * Process SDK operations in configurable batches.
 *
 * Usage:
 *
 *   $processor = new BatchProcessor($client, batchSize: 100);
 *
 *   $results = $processor->customers->createBatch([
 *       ['first_name' => 'John', 'last_name' => 'Doe', 'phone' => '+233XXXXXXXXX'],
 *       // ...
 *   ]);
 *
 *   foreach ($results as $result) {
 *       if ($result->success) {
 *           echo "Created: " . $result->data->id . PHP_EOL;
 *       } else {
 *           echo "Failed: " . $result->error->getMessage() . PHP_EOL;
 *       }
 *   }
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
