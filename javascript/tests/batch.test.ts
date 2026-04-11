/**
 * Tests for BatchProcessor.
 */

import { BatchProcessor } from '../src/batch';
import { SusuDigitalError } from '../src/errors';
import { mockCustomer, mockTransaction } from './fixtures';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeClient(overrides: {
    customersCreate?: jest.Mock;
    transactionsDeposit?: jest.Mock;
    loansCreateApplication?: jest.Mock;
} = {}) {
    return {
        customers: {
            create: overrides.customersCreate ?? jest.fn().mockResolvedValue(mockCustomer),
        },
        transactions: {
            deposit: overrides.transactionsDeposit ?? jest.fn().mockResolvedValue(mockTransaction),
        },
        loans: {
            createApplication: overrides.loansCreateApplication ?? jest.fn().mockResolvedValue({ id: 'loan_1' }),
        },
    };
}

// ---------------------------------------------------------------------------
// BatchProcessor
// ---------------------------------------------------------------------------

describe('BatchProcessor – customers.createBatch()', () => {
    it('creates all customers successfully', async () => {
        const client = makeClient();
        const processor = new BatchProcessor(client, { batchSize: 100, concurrency: 5 });

        const items = [
            { firstName: 'John', lastName: 'Doe', phone: '+233244111111' },
            { firstName: 'Jane', lastName: 'Smith', phone: '+233244222222' },
        ];

        const results = await processor.customers.createBatch(items);

        expect(results.successCount).toBe(2);
        expect(results.failureCount).toBe(0);
        expect(results.successful).toHaveLength(2);
        expect(results.failed).toHaveLength(0);
        expect(client.customers.create).toHaveBeenCalledTimes(2);
    });

    it('collects individual failures without throwing', async () => {
        const error = new SusuDigitalError('Duplicate phone', { code: 'DUPLICATE' });
        const mockCreate = jest.fn()
            .mockResolvedValueOnce(mockCustomer) // first succeeds
            .mockRejectedValueOnce(error);        // second fails

        const client = makeClient({ customersCreate: mockCreate });
        const processor = new BatchProcessor(client, { batchSize: 100 });

        const results = await processor.customers.createBatch([
            { firstName: 'John', phone: '+233244111111' },
            { firstName: 'Jane', phone: '+233244222222' },
        ]);

        expect(results.successCount).toBe(1);
        expect(results.failureCount).toBe(1);
        expect(results.successful[0]).toMatchObject({ id: mockCustomer.id });
        expect(results.failed[0]?.error).toBe(error);
    });

    it('respects batchSize and processes all items', async () => {
        const mockCreate = jest.fn().mockResolvedValue(mockCustomer);
        const client = makeClient({ customersCreate: mockCreate });
        const processor = new BatchProcessor(client, { batchSize: 3, concurrency: 2 });

        const items = Array.from({ length: 7 }, (_, i) => ({
            firstName: `Customer${i}`,
            phone: `+23324400000${i}`,
        }));

        const results = await processor.customers.createBatch(items);
        expect(results.successCount).toBe(7);
        expect(mockCreate).toHaveBeenCalledTimes(7);
    });

    it('tracks correct index on each result', async () => {
        const client = makeClient();
        const processor = new BatchProcessor(client, { batchSize: 100 });
        const results = await processor.customers.createBatch([
            { firstName: 'A', phone: '+233244111111' },
            { firstName: 'B', phone: '+233244222222' },
            { firstName: 'C', phone: '+233244333333' },
        ]);

        const indices = results.results.map((r) => r.index);
        expect(indices).toEqual([0, 1, 2]);
    });

    it('handles empty input', async () => {
        const client = makeClient();
        const processor = new BatchProcessor(client);
        const results = await processor.customers.createBatch([]);
        expect(results.successCount).toBe(0);
        expect(results.results).toHaveLength(0);
    });
});

describe('BatchProcessor – transactions.createBatch()', () => {
    it('uses deposit() as create method', async () => {
        const mockDeposit = jest.fn().mockResolvedValue(mockTransaction);
        const client = makeClient({ transactionsDeposit: mockDeposit });
        const processor = new BatchProcessor(client);

        await processor.transactions.createBatch([
            { customerId: 'cust_A', amount: 100 },
            { customerId: 'cust_B', amount: 200 },
        ]);

        expect(mockDeposit).toHaveBeenCalledTimes(2);
    });
});

describe('BatchProcessor – loans.createBatch()', () => {
    it('uses createApplication() as create method', async () => {
        const mockCreate = jest.fn().mockResolvedValue({ id: 'loan_1' });
        const client = makeClient({ loansCreateApplication: mockCreate });
        const processor = new BatchProcessor(client);

        await processor.loans.createBatch([
            { customerId: 'cust_A', amount: 5000, term: 12, interestRate: 15, purpose: 'biz' },
        ]);

        expect(mockCreate).toHaveBeenCalledTimes(1);
    });
});

describe('BatchResults computed properties', () => {
    it('successful returns only data from successful results', async () => {
        const err = new SusuDigitalError('fail');
        const mockCreate = jest.fn()
            .mockResolvedValueOnce(mockCustomer)
            .mockRejectedValueOnce(err);

        const client = makeClient({ customersCreate: mockCreate });
        const processor = new BatchProcessor(client);
        const results = await processor.customers.createBatch([
            { phone: '+233244111111' },
            { phone: '+233244222222' },
        ]);

        expect(results.successful).toHaveLength(1);
        expect(results.failed).toHaveLength(1);
        expect(results.failed[0]?.success).toBe(false);
        expect(results.failed[0]?.error).toBe(err);
    });
});
