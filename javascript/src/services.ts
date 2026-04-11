/**
 * Service classes for the Susu Digital JavaScript/TypeScript SDK.
 *
 * Each service wraps a specific API domain and exposes typed methods.
 */

// eslint-disable-next-line @typescript-eslint/no-explicit-any
type RequestBody = Record<string, any>;

import type { HttpClient } from './http.js';
import type {
    AnalyticsReport,
    Balance,
    CreateCustomerRequest,
    CreateSavingsAccountRequest,
    CreateSavingsGoalRequest,
    Customer,
    CustomerAnalytics,
    CustomerAnalyticsRequest,
    DepositRequest,
    GenerateReportRequest,
    ListCustomersRequest,
    ListLoansRequest,
    ListTransactionsRequest,
    Loan,
    LoanApplicationRequest,
    LoanApprovalRequest,
    LoanDisbursementRequest,
    LoanScheduleItem,
    PaginatedResponse,
    RepaymentRequest,
    SavingsAccount,
    SavingsGoal,
    StatementRequest,
    Transaction,
    TransactionAnalyticsRequest,
    TransactionSummary,
    TransferRequest,
    UpdateCustomerRequest,
    WithdrawalRequest,
} from './types.js';

// ---------------------------------------------------------------------------
// Internal helper – build raw paginated response
// ---------------------------------------------------------------------------

function buildPaginated<T>(
    raw: Record<string, unknown>,
    mapItem: (item: unknown) => T,
): PaginatedResponse<T> {
    const rawData = Array.isArray(raw['data']) ? (raw['data'] as unknown[]) : [];
    const pagination = (raw['pagination'] as Record<string, unknown> | undefined) ?? {};
    return {
        data: rawData.map(mapItem),
        pagination: {
            page: Number(pagination['page'] ?? raw['page'] ?? 1),
            limit: Number(pagination['limit'] ?? raw['limit'] ?? 50),
            total: Number(pagination['total'] ?? raw['total'] ?? rawData.length),
            totalPages: Number(pagination['totalPages'] ?? raw['total_pages'] ?? 1),
            hasNext: Boolean(pagination['hasNext'] ?? raw['has_next'] ?? false),
            hasPrevious: Boolean(pagination['hasPrevious'] ?? raw['has_prev'] ?? false),
        },
        success: Boolean(raw['success'] ?? true),
        requestId: String(raw['requestId'] ?? raw['request_id'] ?? ''),
        timestamp: String(raw['timestamp'] ?? new Date().toISOString()),
    };
}

// ---------------------------------------------------------------------------
// CustomerService
// ---------------------------------------------------------------------------

export class CustomerService {
    private static readonly PATH = '/customers';

    constructor(private readonly http: HttpClient) { }

    /** Create a new customer. */
    async create(data: CreateCustomerRequest): Promise<Customer> {
        const raw = await this.http.post<Record<string, unknown>>(CustomerService.PATH, data as RequestBody);
        return raw as unknown as Customer;
    }

    /** Retrieve a customer by their ID. */
    async get(customerId: string): Promise<Customer> {
        const raw = await this.http.get<Record<string, unknown>>(`${CustomerService.PATH}/${customerId}`);
        return raw as unknown as Customer;
    }

    /** Update customer fields (partial update). */
    async update(customerId: string, data: UpdateCustomerRequest): Promise<Customer> {
        const raw = await this.http.patch<Record<string, unknown>>(
            `${CustomerService.PATH}/${customerId}`,
            data as RequestBody,
        );
        return raw as unknown as Customer;
    }

    /** Delete (deactivate) a customer record. */
    async delete(customerId: string): Promise<void> {
        await this.http.delete(`${CustomerService.PATH}/${customerId}`);
    }

    /** Retrieve a customer's current balance. */
    async getBalance(customerId: string): Promise<Balance> {
        const raw = await this.http.get<Record<string, unknown>>(`${CustomerService.PATH}/${customerId}/balance`);
        return raw as unknown as Balance;
    }

    /** List customers with optional filtering and pagination. */
    async list(params: ListCustomersRequest = {}): Promise<PaginatedResponse<Customer>> {
        const raw = await this.http.get<Record<string, unknown>>(
            CustomerService.PATH,
            params as RequestBody,
        );
        return buildPaginated(raw, (item) => item as Customer);
    }
}

// ---------------------------------------------------------------------------
// TransactionService
// ---------------------------------------------------------------------------

export class TransactionService {
    private static readonly PATH = '/transactions';

    constructor(private readonly http: HttpClient) { }

    /** Create a deposit transaction. */
    async deposit(data: DepositRequest): Promise<Transaction> {
        const payload = { ...data, type: 'deposit' } as RequestBody;
        const raw = await this.http.post<Record<string, unknown>>(
            TransactionService.PATH,
            payload,
        );
        return raw as unknown as Transaction;
    }

    /** Create a withdrawal transaction. */
    async withdraw(data: WithdrawalRequest): Promise<Transaction> {
        const payload = { ...data, type: 'withdrawal' } as RequestBody;
        const raw = await this.http.post<Record<string, unknown>>(
            TransactionService.PATH,
            payload,
        );
        return raw as unknown as Transaction;
    }

    /** Create a peer-to-peer transfer. */
    async transfer(data: TransferRequest): Promise<Transaction> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${TransactionService.PATH}/transfer`,
            data as RequestBody,
        );
        return raw as unknown as Transaction;
    }

    /** Retrieve a transaction by its ID. */
    async get(transactionId: string): Promise<Transaction> {
        const raw = await this.http.get<Record<string, unknown>>(
            `${TransactionService.PATH}/${transactionId}`,
        );
        return raw as unknown as Transaction;
    }

    /** List transactions with optional filters. */
    async list(params: ListTransactionsRequest = {}): Promise<PaginatedResponse<Transaction>> {
        const raw = await this.http.get<Record<string, unknown>>(
            TransactionService.PATH,
            params as RequestBody,
        );
        return buildPaginated(raw, (item) => item as Transaction);
    }
}

// ---------------------------------------------------------------------------
// LoanService
// ---------------------------------------------------------------------------

export class LoanService {
    private static readonly PATH = '/loans';

    constructor(private readonly http: HttpClient) { }

    /** Submit a new loan application. */
    async createApplication(data: LoanApplicationRequest): Promise<Loan> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${LoanService.PATH}/applications`,
            data as RequestBody,
        );
        return raw as unknown as Loan;
    }

    /** Approve a loan application with negotiated terms. */
    async approve(loanId: string, data: LoanApprovalRequest): Promise<Loan> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${LoanService.PATH}/${loanId}/approve`,
            data as RequestBody,
        );
        return raw as unknown as Loan;
    }

    /** Disburse an approved loan. */
    async disburse(loanId: string, data: LoanDisbursementRequest): Promise<Loan> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${LoanService.PATH}/${loanId}/disburse`,
            data as RequestBody,
        );
        return raw as unknown as Loan;
    }

    /** Record a repayment against a loan. */
    async recordRepayment(loanId: string, data: RepaymentRequest): Promise<Record<string, unknown>> {
        return this.http.post<Record<string, unknown>>(
            `${LoanService.PATH}/${loanId}/repayments`,
            data as RequestBody,
        );
    }

    /** Retrieve loan details by ID. */
    async get(loanId: string): Promise<Loan> {
        const raw = await this.http.get<Record<string, unknown>>(`${LoanService.PATH}/${loanId}`);
        return raw as unknown as Loan;
    }

    /** Retrieve the full repayment schedule for a loan. */
    async getSchedule(loanId: string): Promise<LoanScheduleItem[]> {
        const raw = await this.http.get<Record<string, unknown>>(`${LoanService.PATH}/${loanId}/schedule`);
        const data = Array.isArray(raw['data']) ? (raw['data'] as unknown[]) : [];
        return data as LoanScheduleItem[];
    }

    /** List loans with optional filters. */
    async list(params: ListLoansRequest = {}): Promise<PaginatedResponse<Loan>> {
        const raw = await this.http.get<Record<string, unknown>>(
            LoanService.PATH,
            params as RequestBody,
        );
        return buildPaginated(raw, (item) => item as Loan);
    }
}

// ---------------------------------------------------------------------------
// SavingsService
// ---------------------------------------------------------------------------

export class SavingsService {
    private static readonly PATH = '/savings';

    constructor(private readonly http: HttpClient) { }

    /** Open a new savings account for a customer. */
    async createAccount(data: CreateSavingsAccountRequest): Promise<SavingsAccount> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${SavingsService.PATH}/accounts`,
            data as RequestBody,
        );
        return raw as unknown as SavingsAccount;
    }

    /** Retrieve savings account details. */
    async getAccount(accountId: string): Promise<SavingsAccount> {
        const raw = await this.http.get<Record<string, unknown>>(
            `${SavingsService.PATH}/accounts/${accountId}`,
        );
        return raw as unknown as SavingsAccount;
    }

    /** Retrieve the balance for a savings account. */
    async getBalance(accountId: string): Promise<Balance> {
        const raw = await this.http.get<Record<string, unknown>>(
            `${SavingsService.PATH}/accounts/${accountId}/balance`,
        );
        return raw as unknown as Balance;
    }

    /** Create a savings goal linked to an account. */
    async createGoal(data: CreateSavingsGoalRequest): Promise<SavingsGoal> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${SavingsService.PATH}/goals`,
            data as RequestBody,
        );
        return raw as unknown as SavingsGoal;
    }

    /** Retrieve a savings goal. */
    async getGoal(goalId: string): Promise<SavingsGoal> {
        const raw = await this.http.get<Record<string, unknown>>(
            `${SavingsService.PATH}/goals/${goalId}`,
        );
        return raw as unknown as SavingsGoal;
    }

    /** Get an account statement. */
    async getStatement(accountId: string, params: StatementRequest): Promise<Record<string, unknown>> {
        return this.http.get<Record<string, unknown>>(
            `${SavingsService.PATH}/accounts/${accountId}/statement`,
            params as RequestBody,
        );
    }

    /** List savings accounts, optionally filtered by customer. */
    async listAccounts(params: { customerId?: string; page?: number; limit?: number } = {}): Promise<PaginatedResponse<SavingsAccount>> {
        const raw = await this.http.get<Record<string, unknown>>(
            `${SavingsService.PATH}/accounts`,
            params as RequestBody,
        );
        return buildPaginated(raw, (item) => item as SavingsAccount);
    }
}

// ---------------------------------------------------------------------------
// AnalyticsService
// ---------------------------------------------------------------------------

export class AnalyticsService {
    private static readonly PATH = '/analytics';

    constructor(private readonly http: HttpClient) { }

    /** Retrieve analytics for a specific customer over a period. */
    async getCustomerAnalytics(params: CustomerAnalyticsRequest): Promise<CustomerAnalytics> {
        const { customerId, ...rest } = params;
        const raw = await this.http.get<Record<string, unknown>>(
            `${AnalyticsService.PATH}/customers/${customerId}`,
            rest as RequestBody,
        );
        return raw as unknown as CustomerAnalytics;
    }

    /** Retrieve aggregated transaction summaries. */
    async getTransactionAnalytics(params: TransactionAnalyticsRequest): Promise<TransactionSummary[]> {
        const raw = await this.http.get<Record<string, unknown>>(
            `${AnalyticsService.PATH}/transactions`,
            params as RequestBody,
        );
        const data = Array.isArray(raw['data']) ? (raw['data'] as unknown[]) : [];
        return data as TransactionSummary[];
    }

    /** Request generation of an analytics report. */
    async generateReport(data: GenerateReportRequest): Promise<AnalyticsReport> {
        const raw = await this.http.post<Record<string, unknown>>(
            `${AnalyticsService.PATH}/reports`,
            data as RequestBody,
        );
        return raw as unknown as AnalyticsReport;
    }

    /** Get portfolio-level metrics. */
    async getPortfolioMetrics(params: Record<string, unknown> = {}): Promise<Record<string, unknown>> {
        return this.http.get<Record<string, unknown>>(
            `${AnalyticsService.PATH}/portfolio`,
            params,
        );
    }

    /** Get customer-level insights. */
    async getCustomerInsights(params: { customerId: string; metrics?: string[] }): Promise<Record<string, unknown>> {
        const { customerId, ...rest } = params;
        return this.http.get<Record<string, unknown>>(
            `${AnalyticsService.PATH}/customers/${customerId}/insights`,
            rest as Record<string, unknown>,
        );
    }

    /** Export data in a given format. */
    async exportData(params: {
        entity: string;
        format: 'csv' | 'json' | 'xlsx';
        filters?: Record<string, string>;
    }): Promise<Record<string, unknown>> {
        return this.http.post<Record<string, unknown>>(
            `${AnalyticsService.PATH}/exports`,
            params as Record<string, unknown>,
        );
    }
}
