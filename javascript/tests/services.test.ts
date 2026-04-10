/**
 * Tests for all service classes (CustomerService, TransactionService, etc.)
 * Fetch is globally mocked so no real HTTP calls are made.
 */

import { HttpClient } from '../src/http';
import {
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
} from '../src/services';
import {
    mockBalance,
    mockCustomer,
    mockCustomerListResponse,
    mockLoan,
    mockSavingsAccount,
    mockTransaction,
    mockTransactionListResponse,
} from './fixtures';

// ---------------------------------------------------------------------------
// Helper
// ---------------------------------------------------------------------------

function mockFetch(status: number, body: unknown): void {
    global.fetch = jest.fn().mockResolvedValue({
        ok: status >= 200 && status < 300,
        status,
        statusText: 'OK',
        headers: { get: () => null },
        json: () => Promise.resolve(body),
    } as unknown as Response);
}

function makeHttp(): HttpClient {
    return new HttpClient({ apiKey: 'sk_test_abc', environment: 'sandbox', retryAttempts: 0 });
}

// ---------------------------------------------------------------------------
// CustomerService
// ---------------------------------------------------------------------------

describe('CustomerService', () => {
    let service: CustomerService;

    beforeEach(() => {
        service = new CustomerService(makeHttp());
    });

    it('create() POSTs to /customers and returns Customer', async () => {
        mockFetch(201, mockCustomer);
        const result = await service.create({
            firstName: 'John',
            lastName: 'Doe',
            phone: '+233244123456',
        });
        expect(result.id).toBe(mockCustomer.id);
        expect(result.firstName).toBe('John');
    });

    it('get() GETs /customers/:id', async () => {
        mockFetch(200, mockCustomer);
        const result = await service.get('cust_testABC123');
        expect(result.id).toBe('cust_testABC123');
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/customers/cust_testABC123');
    });

    it('update() PATCHes /customers/:id', async () => {
        const updated = { ...mockCustomer, email: 'new@example.com' };
        mockFetch(200, updated);
        const result = await service.update('cust_testABC123', { email: 'new@example.com' });
        expect(result.email).toBe('new@example.com');
    });

    it('delete() sends DELETE /customers/:id', async () => {
        global.fetch = jest.fn().mockResolvedValue({
            ok: true, status: 204,
            headers: { get: () => '0' },
            json: () => Promise.reject(new Error('no body')),
        } as unknown as Response);
        await expect(service.delete('cust_testABC123')).resolves.toBeUndefined();
    });

    it('getBalance() GETs /customers/:id/balance', async () => {
        mockFetch(200, mockBalance);
        const result = await service.getBalance('cust_testABC123');
        expect(result.available).toBe(500.0);
        expect(result.currency).toBe('GHS');
    });

    it('list() returns PaginatedResponse with pagination shape', async () => {
        mockFetch(200, mockCustomerListResponse);
        const result = await service.list({ page: 1, limit: 50 });
        expect(result.data).toHaveLength(1);
        expect(result.pagination.total).toBe(1);
        expect(result.pagination.page).toBe(1);
        expect(result.pagination.hasNext).toBe(false);
    });

    it('list() passes filters as query params', async () => {
        mockFetch(200, mockCustomerListResponse);
        await service.list({ status: 'active', search: 'john' });
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('status=active');
        expect(url).toContain('search=john');
    });
});

// ---------------------------------------------------------------------------
// TransactionService
// ---------------------------------------------------------------------------

describe('TransactionService', () => {
    let service: TransactionService;

    beforeEach(() => {
        service = new TransactionService(makeHttp());
    });

    it('deposit() POSTs with type=deposit', async () => {
        mockFetch(201, mockTransaction);
        const result = await service.deposit({
            customerId: 'cust_testABC123',
            amount: 100.0,
            currency: 'GHS',
            reference: 'DEP-001',
        });
        expect(result.id).toBe(mockTransaction.id);

        const body = JSON.parse((global.fetch as jest.Mock).mock.calls[0]?.[1]?.body as string) as Record<string, unknown>;
        expect(body['type']).toBe('deposit');
    });

    it('withdraw() POSTs with type=withdrawal', async () => {
        const withdrawal = { ...mockTransaction, type: 'withdrawal' as const };
        mockFetch(201, withdrawal);
        const result = await service.withdraw({
            customerId: 'cust_testABC123',
            amount: 50.0,
        });
        expect(result.type).toBe('withdrawal');

        const body = JSON.parse((global.fetch as jest.Mock).mock.calls[0]?.[1]?.body as string) as Record<string, unknown>;
        expect(body['type']).toBe('withdrawal');
    });

    it('transfer() POSTs to /transactions/transfer', async () => {
        const transfer = { ...mockTransaction, type: 'transfer' as const };
        mockFetch(201, transfer);
        await service.transfer({
            fromCustomerId: 'cust_A',
            toCustomerId: 'cust_B',
            amount: 25.0,
        });
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/transactions/transfer');
    });

    it('get() GETs /transactions/:id', async () => {
        mockFetch(200, mockTransaction);
        const result = await service.get('txn_testABC123');
        expect(result.id).toBe('txn_testABC123');
    });

    it('list() returns paginated transactions', async () => {
        mockFetch(200, mockTransactionListResponse);
        const result = await service.list({ customerId: 'cust_testABC123' });
        expect(result.data).toHaveLength(1);
        expect(result.data[0]?.id).toBe(mockTransaction.id);
    });
});

// ---------------------------------------------------------------------------
// LoanService
// ---------------------------------------------------------------------------

describe('LoanService', () => {
    let service: LoanService;

    beforeEach(() => {
        service = new LoanService(makeHttp());
    });

    it('createApplication() POSTs to /loans/applications', async () => {
        mockFetch(201, mockLoan);
        await service.createApplication({
            customerId: 'cust_testABC123',
            amount: 5000,
            term: 12,
            interestRate: 15,
            purpose: 'business',
        });
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/loans/applications');
    });

    it('approve() POSTs to /loans/:id/approve', async () => {
        mockFetch(200, { ...mockLoan, status: 'approved' });
        await service.approve('loan_testABC123', { approvedAmount: 5000, approvedTerm: 12, approvedRate: 14 });
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/loans/loan_testABC123/approve');
    });

    it('disburse() POSTs to /loans/:id/disburse', async () => {
        mockFetch(200, { ...mockLoan, status: 'disbursed' });
        await service.disburse('loan_testABC123', { disbursementMethod: 'bank_transfer' });
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/loans/loan_testABC123/disburse');
    });

    it('getSchedule() returns array from data[]', async () => {
        mockFetch(200, { data: [{ installmentNumber: 1, dueDate: '2026-02-01', principal: 416.67, interest: 62.5, total: 479.17, outstandingBalance: 4583.33, status: 'pending' }] });
        const schedule = await service.getSchedule('loan_testABC123');
        expect(schedule).toHaveLength(1);
        expect(schedule[0]).toHaveProperty('installmentNumber', 1);
    });

    it('list() returns paginated loans', async () => {
        mockFetch(200, { data: [mockLoan], pagination: { page: 1, limit: 20, total: 1, totalPages: 1, hasNext: false, hasPrevious: false }, success: true, requestId: 'r1', timestamp: '' });
        const result = await service.list({ customerId: 'cust_testABC123' });
        expect(result.data).toHaveLength(1);
    });
});

// ---------------------------------------------------------------------------
// SavingsService
// ---------------------------------------------------------------------------

describe('SavingsService', () => {
    let service: SavingsService;

    beforeEach(() => {
        service = new SavingsService(makeHttp());
    });

    it('createAccount() POSTs to /savings/accounts', async () => {
        mockFetch(201, mockSavingsAccount);
        await service.createAccount({ customerId: 'cust_testABC123', accountType: 'regular' });
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/savings/accounts');
    });

    it('getBalance() GETs /savings/accounts/:id/balance', async () => {
        mockFetch(200, mockBalance);
        await service.getBalance('sacc_testABC123');
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/savings/accounts/sacc_testABC123/balance');
    });

    it('createGoal() POSTs to /savings/goals', async () => {
        const goal = { id: 'goal_1', accountId: 'sacc_testABC123', name: 'Emergency', targetAmount: 2000, currentAmount: 0, monthlyContribution: 200, targetDate: '2027-01-01', status: 'active', createdAt: '' };
        mockFetch(201, goal);
        const result = await service.createGoal({ accountId: 'sacc_testABC123', name: 'Emergency', targetAmount: 2000, targetDate: '2027-01-01', monthlyContribution: 200 });
        expect(result.name).toBe('Emergency');
    });
});

// ---------------------------------------------------------------------------
// AnalyticsService
// ---------------------------------------------------------------------------

describe('AnalyticsService', () => {
    let service: AnalyticsService;

    beforeEach(() => {
        service = new AnalyticsService(makeHttp());
    });

    it('getCustomerAnalytics() GETs /analytics/customers/:id', async () => {
        const analytics = { customerId: 'cust_testABC123', totalDeposits: 500, totalWithdrawals: 100, totalLoans: 1, activeLoans: 1, savingsBalance: 250, transactionCount: 5, periodStart: '2026-01-01', periodEnd: '2026-04-10' };
        mockFetch(200, analytics);
        const result = await service.getCustomerAnalytics({ customerId: 'cust_testABC123', startDate: '2026-01-01', endDate: '2026-04-10' });
        expect(result.customerId).toBe('cust_testABC123');
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/analytics/customers/cust_testABC123');
    });

    it('getTransactionAnalytics() returns array', async () => {
        mockFetch(200, { data: [{ period: '2026-01', totalAmount: 500, transactionCount: 5, averageAmount: 100, currency: 'GHS' }] });
        const result = await service.getTransactionAnalytics({ startDate: '2026-01-01', endDate: '2026-03-31' });
        expect(result).toHaveLength(1);
        expect(result[0]).toHaveProperty('period', '2026-01');
    });

    it('generateReport() POSTs to /analytics/reports', async () => {
        const report = { id: 'rep_1', reportType: 'financial_summary', format: 'json', status: 'pending', createdAt: '' };
        mockFetch(202, report);
        const result = await service.generateReport({ type: 'financial_summary', startDate: '2026-01-01', endDate: '2026-03-31' });
        expect(result.id).toBe('rep_1');
        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('/analytics/reports');
    });
});
