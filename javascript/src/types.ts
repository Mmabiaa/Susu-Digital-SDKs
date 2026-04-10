/**
 * Type definitions for the Susu Digital JavaScript/TypeScript SDK.
 *
 * All types use camelCase to follow JavaScript conventions.
 * The API uses camelCase so no aliasing is required.
 */

// ---------------------------------------------------------------------------
// Enums / Union Types
// ---------------------------------------------------------------------------

export type Environment = 'sandbox' | 'production';

export type CustomerStatus = 'active' | 'inactive' | 'suspended' | 'pending';

export type TransactionType = 'deposit' | 'withdrawal' | 'transfer';

export type TransactionStatus =
    | 'pending'
    | 'processing'
    | 'completed'
    | 'failed'
    | 'reversed';

export type LoanStatus =
    | 'pending'
    | 'under_review'
    | 'approved'
    | 'disbursed'
    | 'active'
    | 'closed'
    | 'defaulted'
    | 'rejected';

export type SavingsAccountType = 'regular' | 'fixed' | 'susu';

export type CollateralType =
    | 'property'
    | 'vehicle'
    | 'equipment'
    | 'savings'
    | 'other';

// ---------------------------------------------------------------------------
// Shared / Common
// ---------------------------------------------------------------------------

export interface Pagination {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
    hasNext: boolean;
    hasPrevious: boolean;
}

export interface PaginatedResponse<T> {
    data: T[];
    pagination: Pagination;
    success: boolean;
    requestId: string;
    timestamp: string;
}

export interface ApiResponse<T> {
    data: T;
    success: boolean;
    message?: string;
    requestId: string;
    timestamp: string;
}

// ---------------------------------------------------------------------------
// Customer types
// ---------------------------------------------------------------------------

export interface Address {
    street: string;
    city: string;
    region: string;
    country: string;
    postalCode?: string;
}

export interface Identification {
    type: 'national_id' | 'passport' | 'drivers_license' | 'voter_id';
    number: string;
    expiryDate?: string;
    issueDate?: string;
}

export interface Customer {
    id: string;
    firstName: string;
    lastName: string;
    phone: string;
    email?: string;
    dateOfBirth?: string;
    status: CustomerStatus;
    address?: Address;
    identification?: Identification;
    metadata: Record<string, unknown>;
    createdAt: string;
    updatedAt: string;
}

export interface Balance {
    customerId: string;
    currency: string;
    available: number;
    ledger: number;
    pending: number;
    asOf: string;
}

export interface CreateCustomerRequest {
    firstName: string;
    lastName: string;
    phone: string;
    email?: string;
    dateOfBirth?: string;
    address?: Address;
    identification?: Identification;
    metadata?: Record<string, unknown>;
}

export interface UpdateCustomerRequest {
    firstName?: string;
    lastName?: string;
    phone?: string;
    email?: string;
    address?: Address;
    metadata?: Record<string, unknown>;
}

export interface ListCustomersRequest {
    page?: number;
    limit?: number;
    search?: string;
    status?: CustomerStatus;
}

// ---------------------------------------------------------------------------
// Transaction types
// ---------------------------------------------------------------------------

export interface Transaction {
    id: string;
    customerId: string;
    type: TransactionType;
    amount: number;
    currency: string;
    status: TransactionStatus;
    description?: string;
    reference?: string;
    metadata: Record<string, unknown>;
    createdAt: string;
    completedAt?: string;
}

export interface DepositRequest {
    customerId: string;
    amount: number;
    currency?: string;
    description?: string;
    reference?: string;
    metadata?: Record<string, unknown>;
}

export interface WithdrawalRequest {
    customerId: string;
    amount: number;
    currency?: string;
    description?: string;
    reference?: string;
    metadata?: Record<string, unknown>;
}

export interface TransferRequest {
    fromCustomerId: string;
    toCustomerId: string;
    amount: number;
    currency?: string;
    description?: string;
    reference?: string;
    metadata?: Record<string, unknown>;
}

export interface ListTransactionsRequest {
    customerId?: string;
    startDate?: string;
    endDate?: string;
    type?: TransactionType;
    status?: TransactionStatus;
    page?: number;
    limit?: number;
}

// ---------------------------------------------------------------------------
// Loan types
// ---------------------------------------------------------------------------

export interface Collateral {
    type: CollateralType;
    description?: string;
    value: number;
}

export interface Guarantor {
    name: string;
    phone: string;
    relationship: string;
    email?: string;
}

export interface LoanScheduleItem {
    installmentNumber: number;
    dueDate: string;
    principal: number;
    interest: number;
    total: number;
    outstandingBalance: number;
    status: string;
}

export interface Loan {
    id: string;
    customerId: string;
    amount: number;
    currency: string;
    term: number;
    interestRate: number;
    purpose: string;
    status: LoanStatus;
    disbursedAmount?: number;
    outstandingBalance?: number;
    collateral?: Collateral;
    guarantors: Guarantor[];
    createdAt: string;
    disbursedAt?: string;
}

export interface LoanApplicationRequest {
    customerId: string;
    amount: number;
    currency?: string;
    purpose: string;
    term: number;
    interestRate: number;
    collateral?: Collateral;
    guarantors?: Guarantor[];
    metadata?: Record<string, unknown>;
}

export interface LoanApprovalRequest {
    approvedAmount: number;
    approvedTerm: number;
    approvedRate: number;
    conditions?: string[];
}

export interface LoanDisbursementRequest {
    disbursementMethod: string;
    accountDetails?: Record<string, string>;
}

export interface RepaymentRequest {
    amount: number;
    paymentDate: string;
    paymentMethod: string;
    reference?: string;
}

export interface ListLoansRequest {
    customerId?: string;
    status?: LoanStatus;
    page?: number;
    limit?: number;
}

// ---------------------------------------------------------------------------
// Savings types
// ---------------------------------------------------------------------------

export interface SavingsAccount {
    id: string;
    customerId: string;
    accountType: SavingsAccountType;
    currency: string;
    interestRate?: number;
    minimumBalance: number;
    balance: number;
    status: string;
    createdAt: string;
}

export interface SavingsGoal {
    id: string;
    accountId: string;
    name: string;
    targetAmount: number;
    currentAmount: number;
    monthlyContribution: number;
    targetDate: string;
    status: string;
    progressPercent?: number;
    createdAt: string;
}

export interface CreateSavingsAccountRequest {
    customerId: string;
    accountType?: SavingsAccountType;
    currency?: string;
    interestRate?: number;
    minimumBalance?: number;
}

export interface CreateSavingsGoalRequest {
    accountId: string;
    name: string;
    targetAmount: number;
    targetDate: string;
    monthlyContribution: number;
}

export interface StatementRequest {
    startDate: string;
    endDate: string;
    format?: 'json' | 'pdf';
}

// ---------------------------------------------------------------------------
// Analytics types
// ---------------------------------------------------------------------------

export interface CustomerAnalytics {
    customerId: string;
    totalDeposits: number;
    totalWithdrawals: number;
    totalLoans: number;
    activeLoans: number;
    savingsBalance: number;
    transactionCount: number;
    periodStart: string;
    periodEnd: string;
}

export interface TransactionSummary {
    period: string;
    totalAmount: number;
    transactionCount: number;
    averageAmount: number;
    currency: string;
}

export interface AnalyticsReport {
    id: string;
    reportType: string;
    format: string;
    status: string;
    downloadUrl?: string;
    createdAt: string;
    expiresAt?: string;
}

export interface CustomerAnalyticsRequest {
    customerId: string;
    startDate: string;
    endDate: string;
}

export interface TransactionAnalyticsRequest {
    startDate: string;
    endDate: string;
    groupBy?: 'day' | 'week' | 'month' | 'quarter' | 'year';
    metrics?: string[];
}

export interface GenerateReportRequest {
    type: string;
    period?: string;
    startDate: string;
    endDate: string;
    format?: 'json' | 'pdf' | 'csv';
    filters?: Record<string, string>;
    includeCharts?: boolean;
}

// ---------------------------------------------------------------------------
// Webhook types
// ---------------------------------------------------------------------------

export interface WebhookEvent {
    id: string;
    type: string;
    createdAt: string;
    data: Record<string, unknown>;
    apiVersion: string;
}

// ---------------------------------------------------------------------------
// Batch types
// ---------------------------------------------------------------------------

export interface BatchResult<T> {
    success: boolean;
    data?: T;
    error?: SusuError;
    index: number;
}

export interface BatchResults<T> {
    results: BatchResult<T>[];
    successCount: number;
    failureCount: number;
    successful: T[];
    failed: BatchResult<T>[];
}

// ---------------------------------------------------------------------------
// SDK Config
// ---------------------------------------------------------------------------

export interface SusuDigitalClientConfig {
    apiKey: string;
    environment?: Environment;
    organization?: string;
    timeout?: number;
    retryAttempts?: number;
    enableLogging?: boolean;
    customHeaders?: Record<string, string>;
    baseUrl?: string;
    retryConfig?: RetryConfig;
}

export interface RetryConfig {
    attempts?: number;
    delay?: number;
    backoff?: 'exponential' | 'linear';
    retryCondition?: (error: SusuError) => boolean;
}

// ---------------------------------------------------------------------------
// Error types
// ---------------------------------------------------------------------------

export interface SusuError extends Error {
    code: string;
    message: string;
    requestId?: string;
    statusCode?: number;
    retryable: boolean;
    details?: unknown;
}

export interface ValidationErrorDetails {
    fieldErrors: Record<string, string[]>;
}
