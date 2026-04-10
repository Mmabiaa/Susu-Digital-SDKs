/**
 * Batch processing utilities for the Susu Digital JavaScript/TypeScript SDK.
 *
 * {@link BatchProcessor} allows high-volume operations to be split into
 * configurable batches and executed with configurable concurrency.
 *
 * @example
 * ```ts
 * import { SusuDigitalClient, BatchProcessor } from '@susudigital/sdk';
 *
 * const client = new SusuDigitalClient({ apiKey: '...' });
 * const processor = new BatchProcessor(client, { batchSize: 100, concurrency: 5 });
 *
 * const results = await processor.customers.createBatch([
 *   { firstName: 'John', lastName: 'Doe', phone: '+233XXXXXXXXX' },
 *   { firstName: 'Jane', lastName: 'Smith', phone: '+233YYYYYYYYY' },
 * ]);
 *
 * for (const result of results.results) {
 *   if (result.success) console.log('Created:', result.data?.id);
 *   else console.error('Failed:', result.error?.message);
 * }
 * ```
 */

import type { SusuDigitalError } from './errors.js';
import type { BatchResult, BatchResults } from './types.js';

export interface BatchProcessorConfig {
    /** Maximum items per batch (default: 100) */
    batchSize?: number;
    /** Max concurrent API calls (default: 5) */
    concurrency?: number;
}

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

async function runWithConcurrency<T>(
    tasks: Array<() => Promise<T>>,
    concurrency: number,
): Promise<T[]> {
    const results: T[] = [];
    const queue = [...tasks];

    async function worker(): Promise<void> {
        while (queue.length > 0) {
            const task = queue.shift();
            if (task) {
                results.push(await task());
            }
        }
    }

    await Promise.all(Array.from({ length: Math.min(concurrency, tasks.length) }, worker));
    return results;
}

function buildBatchResults<T>(results: BatchResult<T>[]): BatchResults<T> {
    return {
        results,
        get successCount() {
            return results.filter((r) => r.success).length;
        },
        get failureCount() {
            return results.filter((r) => !r.success).length;
        },
        get successful() {
            return results.filter((r) => r.success && r.data !== undefined).map((r) => r.data as T);
        },
        get failed() {
            return results.filter((r) => !r.success);
        },
    };
}

// ---------------------------------------------------------------------------
// BatchServiceWrapper
// ---------------------------------------------------------------------------

class BatchServiceWrapper<TService extends { create: (data: unknown) => Promise<unknown> }> {
    constructor(
        private readonly service: TService,
        private readonly batchSize: number,
        private readonly concurrency: number,
    ) { }

    /** Delegate all other service methods transparently. */
    get<K extends keyof TService>(key: K): TService[K] {
        return this.service[key];
    }

    /**
     * Create many resources in batches, collecting successes and failures.
     * Does not throw on individual item failures; check `results.failed`.
     */
    async createBatch<TInput, TOutput>(items: TInput[]): Promise<BatchResults<TOutput>> {
        const results: BatchResult<TOutput>[] = [];

        for (let i = 0; i < items.length; i += this.batchSize) {
            const chunk = items.slice(i, i + this.batchSize);

            const tasks = chunk.map(
                (item, j): (() => Promise<BatchResult<TOutput>>) =>
                    async () => {
                        const index = i + j;
                        try {
                            const data = await this.service.create(item);
                            return { success: true, data: data as TOutput, index };
                        } catch (err) {
                            return {
                                success: false,
                                error: err as SusuDigitalError,
                                index,
                            };
                        }
                    },
            );

            const chunkResults = await runWithConcurrency(tasks, this.concurrency);
            results.push(...chunkResults);
        }

        return buildBatchResults(results);
    }
}

// ---------------------------------------------------------------------------
// BatchProcessor
// ---------------------------------------------------------------------------

export class BatchProcessor {
    private readonly batchSize: number;
    private readonly concurrency: number;

    constructor(
        private readonly client: {
            customers: { create: (data: unknown) => Promise<unknown> };
            transactions: { create?: (data: unknown) => Promise<unknown>; deposit: (data: unknown) => Promise<unknown> };
            loans: { create?: (data: unknown) => Promise<unknown>; createApplication: (data: unknown) => Promise<unknown> };
        },
        config: BatchProcessorConfig = {},
    ) {
        this.batchSize = config.batchSize ?? 100;
        this.concurrency = config.concurrency ?? 5;
    }

    get customers() {
        return new BatchServiceWrapper(
            this.client.customers as { create: (data: unknown) => Promise<unknown> },
            this.batchSize,
            this.concurrency,
        );
    }

    get transactions() {
        // Wrap deposit as the primary "create" for batch transactions
        const svc = {
            create: (data: unknown) =>
                (this.client.transactions as { deposit: (d: unknown) => Promise<unknown> }).deposit(data),
        };
        return new BatchServiceWrapper(svc, this.batchSize, this.concurrency);
    }

    get loans() {
        const svc = {
            create: (data: unknown) =>
                (this.client.loans as { createApplication: (d: unknown) => Promise<unknown> }).createApplication(data),
        };
        return new BatchServiceWrapper(svc, this.batchSize, this.concurrency);
    }
}
