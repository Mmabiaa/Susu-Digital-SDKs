/**
 * Test fixtures shared across all test files.
 */

import type {
    Customer,
    Transaction,
    Loan,
    SavingsAccount,
    Balance,
    WebhookEvent,
} from '../src/types.js';

// ---------------------------------------------------------------------------
// Customer fixtures
// ---------------------------------------------------------------------------

export const mockCustomer: Customer = {
    id: 'cust_testABC123',
    firstName: 'John',
    lastName: 'Doe',
    phone: '+233244123456',
    email: 'john.doe@example.com',
    dateOfBirth: '1990-01-15',
    status: 'active',
    address: {
        street: '123 Main Street',
        city: 'Accra',
        region: 'Greater Accra',
        country: 'Ghana',
    },
    identification: {
        type: 'national_id',
        number: 'GHA-123456789-0',
        expiryDate: '2030-12-31',
    },
    metadata: {},
    createdAt: '2026-01-01T00:00:00Z',
    updatedAt: '2026-01-01T00:00:00Z',
};

export const mockCustomerListResponse = {
    data: [mockCustomer],
    pagination: {
        page: 1,
        limit: 50,
        total: 1,
        totalPages: 1,
        hasNext: false,
        hasPrevious: false,
    },
    success: true,
    requestId: 'req_test123',
    timestamp: '2026-04-10T00:00:00Z',
};

export const mockBalance: Balance = {
    customerId: 'cust_testABC123',
    currency: 'GHS',
    available: 500.0,
    ledger: 500.0,
    pending: 0.0,
    asOf: '2026-04-10T00:00:00Z',
};

// ---------------------------------------------------------------------------
// Transaction fixtures
// ---------------------------------------------------------------------------

export const mockTransaction: Transaction = {
    id: 'txn_testABC123',
    customerId: 'cust_testABC123',
    type: 'deposit',
    amount: 100.0,
    currency: 'GHS',
    status: 'completed',
    description: 'Savings deposit',
    reference: 'DEP-1234567890',
    metadata: {},
    createdAt: '2026-04-10T00:00:00Z',
    completedAt: '2026-04-10T00:00:01Z',
};

export const mockTransactionListResponse = {
    data: [mockTransaction],
    pagination: {
        page: 1,
        limit: 50,
        total: 1,
        totalPages: 1,
        hasNext: false,
        hasPrevious: false,
    },
    success: true,
    requestId: 'req_test456',
    timestamp: '2026-04-10T00:00:00Z',
};

// ---------------------------------------------------------------------------
// Loan fixtures
// ---------------------------------------------------------------------------

export const mockLoan: Loan = {
    id: 'loan_testABC123',
    customerId: 'cust_testABC123',
    amount: 5000.0,
    currency: 'GHS',
    term: 12,
    interestRate: 15.0,
    purpose: 'business_expansion',
    status: 'active',
    disbursedAmount: 5000.0,
    outstandingBalance: 4550.0,
    guarantors: [],
    createdAt: '2026-01-01T00:00:00Z',
    disbursedAt: '2026-01-05T00:00:00Z',
};

// ---------------------------------------------------------------------------
// Savings fixtures
// ---------------------------------------------------------------------------

export const mockSavingsAccount: SavingsAccount = {
    id: 'sacc_testABC123',
    customerId: 'cust_testABC123',
    accountType: 'regular',
    currency: 'GHS',
    interestRate: 8.0,
    minimumBalance: 10.0,
    balance: 250.0,
    status: 'active',
    createdAt: '2026-01-01T00:00:00Z',
};

// ---------------------------------------------------------------------------
// Webhook fixtures
// ---------------------------------------------------------------------------

export const mockWebhookEvent: WebhookEvent = {
    id: 'evt_testABC123',
    type: 'transaction.completed',
    createdAt: '2026-04-10T00:00:00Z',
    data: {
        transaction: { id: 'txn_testABC123', amount: 100.0, currency: 'GHS' },
        customerId: 'cust_testABC123',
    },
    apiVersion: 'v1',
};

// ---------------------------------------------------------------------------
// Error response fixtures
// ---------------------------------------------------------------------------

export const mockValidationErrorResponse = {
    message: 'Validation failed',
    code: 'VALIDATION_ERROR',
    field_errors: {
        phone: ['Phone must be in E.164 format'],
        firstName: ['First name is required'],
    },
};

export const mockAuthErrorResponse = {
    message: 'Invalid API key',
    code: 'AUTH_FAILED',
};

export const mockNotFoundResponse = {
    message: 'Customer not found',
    code: 'NOT_FOUND',
};

export const mockRateLimitResponse = {
    message: 'Rate limit exceeded',
    code: 'RATE_LIMITED',
};

export const mockServerErrorResponse = {
    message: 'Internal server error',
    code: 'SERVER_ERROR',
};
