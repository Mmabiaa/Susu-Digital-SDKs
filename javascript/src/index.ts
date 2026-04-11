/**
 * Public API surface for @susudigital/sdk
 *
 * Import from this module to access everything in the SDK:
 *
 * ```ts
 * import {
 *   SusuDigitalClient,
 *   WebhookHandler,
 *   BatchProcessor,
 *   SusuDigitalError,
 *   ValidationError,
 *   // ... all types
 * } from '@susudigital/sdk';
 * ```
 */

// Main client
export { SusuDigitalClient } from './client.js';

// Services (re-exported for consumers who want to type service references)
export {
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
} from './services.js';

// Webhook handler
export { WebhookHandler } from './webhooks.js';
export type { WebhookHandlerConfig } from './webhooks.js';

// Batch processor
export { BatchProcessor } from './batch.js';
export type { BatchProcessorConfig } from './batch.js';

// Error classes
export {
    AuthenticationError,
    NetworkError,
    NotFoundError,
    RateLimitError,
    ServerError,
    SusuDigitalError,
    ValidationError,
    WebhookSignatureError,
} from './errors.js';

// All types
export type {
    Address,
    AnalyticsReport,
    ApiResponse,
    Balance,
    BatchResult,
    BatchResults,
    Collateral,
    CollateralType,
    CreateCustomerRequest,
    CreateSavingsAccountRequest,
    CreateSavingsGoalRequest,
    Customer,
    CustomerAnalytics,
    CustomerAnalyticsRequest,
    CustomerStatus,
    DepositRequest,
    Environment,
    GenerateReportRequest,
    Guarantor,
    Identification,
    Loan,
    LoanApplicationRequest,
    LoanApprovalRequest,
    LoanDisbursementRequest,
    LoanScheduleItem,
    LoanStatus,
    ListCustomersRequest,
    ListLoansRequest,
    ListTransactionsRequest,
    PaginatedResponse,
    Pagination,
    RepaymentRequest,
    RetryConfig,
    SavingsAccount,
    SavingsAccountType,
    SavingsGoal,
    StatementRequest,
    SusuDigitalClientConfig,
    SusuError,
    Transaction,
    TransactionAnalyticsRequest,
    TransactionStatus,
    TransactionSummary,
    TransactionType,
    TransferRequest,
    UpdateCustomerRequest,
    ValidationErrorDetails,
    WebhookEvent,
    WithdrawalRequest,
} from './types.js';
