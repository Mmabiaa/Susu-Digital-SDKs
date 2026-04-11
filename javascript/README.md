# Susu Digital JavaScript/TypeScript SDK

> **Enterprise-Grade JavaScript / TypeScript SDK for the Susu Digital microfinance platform.**

[![npm version](https://img.shields.io/npm/v/@susudigital/sdk)](https://www.npmjs.com/package/@susudigital/sdk)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Node.js](https://img.shields.io/badge/node-%3E%3D16.0.0-brightgreen)](https://nodejs.org)
[![TypeScript](https://img.shields.io/badge/TypeScript-5.x-blue)](https://www.typescriptlang.org/)

---

## Table of Contents

- [Overview](#overview)
- [Requirements](#requirements)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Configuration](#configuration)
  - [Constructor options](#constructor-options)
  - [Environment variables](#environment-variables)
- [Services](#services)
  - [Customers](#customers)
  - [Transactions](#transactions)
  - [Loans](#loans)
  - [Savings](#savings)
  - [Analytics](#analytics)
- [Webhooks](#webhooks)
- [Batch Processing](#batch-processing)
- [Error Handling](#error-handling)
  - [Error hierarchy](#error-hierarchy)
  - [Retryable errors](#retryable-errors)
- [TypeScript](#typescript)
- [Development](#development)
- [License](#license)

---

## Overview

`@susudigital/sdk` is a fully-typed, dual-format (ESM + CJS) JavaScript/TypeScript SDK for the [Susu Digital](https://susudigital.app) API. It provides:

- **Automatic retry** with exponential back-off for transient failures.
- **Five domain services** — Customers, Transactions, Loans, Savings, Analytics.
- **HMAC-SHA256 webhook verification** with replay-attack protection.
- **Concurrency-aware batch processing** for high-volume operations.
- **Rich, structured error types** for precise error handling.
- **Zero production dependencies** beyond `node-fetch`.

---

## Requirements

| Requirement | Version |
|-------------|---------|
| Node.js     | ≥ 16.0.0 |
| TypeScript  | ≥ 5.0 (optional, for type-checking) |

---

## Installation

```bash
npm install @susudigital/sdk
# or
yarn add @susudigital/sdk
# or
pnpm add @susudigital/sdk
```

---

## Quick Start

```typescript
import { SusuDigitalClient } from '@susudigital/sdk';

const client = new SusuDigitalClient({
  apiKey: process.env.SUSU_API_KEY!,
  environment: 'production',
});

// Create a customer
const customer = await client.customers.create({
  firstName: 'Ama',
  lastName: 'Owusu',
  phone: '+233501234567',
  email: 'ama@example.com',
});

// Make a deposit
const txn = await client.transactions.deposit({
  customerId: customer.id,
  amount: 100.00,
  currency: 'GHS',
  description: 'Initial deposit',
});

console.log(`Transaction ${txn.id} — status: ${txn.status}`);
```

---

## Configuration

### Constructor options

```typescript
import { SusuDigitalClient } from '@susudigital/sdk';

const client = new SusuDigitalClient({
  apiKey: 'sk_live_...',          // Required. Your Susu Digital API key.
  environment: 'production',      // 'sandbox' | 'production'. Default: 'sandbox'
  organization: 'org_abc123',     // Optional. Organization ID for multi-tenant setups.
  timeout: 30,                    // Request timeout in seconds. Default: 30
  retryAttempts: 3,               // Max retries on transient failures. Default: 3
  enableLogging: false,           // Log HTTP requests/responses. Default: false
});
```

### Environment variables

Use `SusuDigitalClient.fromEnv()` to configure from environment variables — ideal for 12-factor applications:

```bash
SUSU_API_KEY=sk_live_...
SUSU_ENVIRONMENT=production
SUSU_ORGANIZATION_ID=org_abc123
SUSU_TIMEOUT=30
SUSU_MAX_RETRIES=3
SUSU_ENABLE_LOGGING=false
```

```typescript
const client = SusuDigitalClient.fromEnv();
```

---

## Services

All services are exposed as properties of the `SusuDigitalClient` instance.

### Customers

```typescript
const svc = client.customers;

// Create
const customer = await svc.create({
  firstName: 'Kwame',
  lastName: 'Mensah',
  phone: '+233201234567',
  email: 'kwame@example.com',
  // address, identification, etc.
});

// Retrieve
const fetched = await svc.get('cust_abc123');

// Update (partial)
const updated = await svc.update('cust_abc123', { email: 'new@example.com' });

// Delete / deactivate
await svc.delete('cust_abc123');

// Balance
const balance = await svc.getBalance('cust_abc123');
console.log(balance.available, balance.currency);

// Paginated list
const page = await svc.list({ page: 1, limit: 50, status: 'active' });
console.log(`${page.pagination.total} customers total`);
for (const c of page.data) {
  console.log(c.id, c.firstName, c.lastName);
}
```

---

### Transactions

```typescript
const svc = client.transactions;

// Deposit
const deposit = await svc.deposit({
  customerId: 'cust_abc123',
  amount: 250.00,
  currency: 'GHS',
  description: 'Mobile money deposit',
});

// Withdrawal
const withdrawal = await svc.withdraw({
  customerId: 'cust_abc123',
  amount: 50.00,
  currency: 'GHS',
});

// Peer-to-peer transfer
const transfer = await svc.transfer({
  fromCustomerId: 'cust_abc123',
  toCustomerId: 'cust_xyz789',
  amount: 30.00,
  currency: 'GHS',
});

// Retrieve single transaction
const txn = await svc.get('txn_id123');

// List with filters
const page = await svc.list({
  customerId: 'cust_abc123',
  type: 'deposit',
  startDate: '2024-01-01',
  endDate: '2024-12-31',
  page: 1,
  limit: 25,
});
```

---

### Loans

```typescript
const svc = client.loans;

// Submit a loan application
const loan = await svc.createApplication({
  customerId: 'cust_abc123',
  amount: 5000.00,
  currency: 'GHS',
  termMonths: 12,
  purpose: 'Business expansion',
});

// Approve the loan
const approved = await svc.approve(loan.id, {
  approvedAmount: 4500.00,
  interestRate: 0.15,
  termMonths: 12,
});

// Disburse funds
const disbursed = await svc.disburse(loan.id, {
  disbursementDate: '2024-06-01',
  channelId: 'ch_momo',
});

// Record a repayment
await svc.recordRepayment(loan.id, {
  amount: 420.00,
  currency: 'GHS',
  paymentDate: '2024-07-01',
});

// Retrieve loan details
const details = await svc.get(loan.id);

// Get full repayment schedule
const schedule = await svc.getSchedule(loan.id);
schedule.forEach((item) => {
  console.log(`${item.dueDate}: ${item.amount} ${item.currency}`);
});

// List loans
const page = await svc.list({ status: 'active', page: 1, limit: 20 });
```

---

### Savings

```typescript
const svc = client.savings;

// Open a savings account
const account = await svc.createAccount({
  customerId: 'cust_abc123',
  accountType: 'susu',
  currency: 'GHS',
});

// Retrieve account details and balance
const details = await svc.getAccount(account.id);
const balance = await svc.getBalance(account.id);

// Create a savings goal
const goal = await svc.createGoal({
  accountId: account.id,
  name: 'New fridge',
  targetAmount: 1500.00,
  currency: 'GHS',
  targetDate: '2025-03-01',
});

const fetchedGoal = await svc.getGoal(goal.id);

// Account statement
const statement = await svc.getStatement(account.id, {
  startDate: '2024-01-01',
  endDate: '2024-12-31',
  format: 'json',
});

// List accounts for a customer
const accounts = await svc.listAccounts({ customerId: 'cust_abc123' });
```

---

### Analytics

```typescript
const svc = client.analytics;

// Customer analytics
const customerAnalytics = await svc.getCustomerAnalytics({
  customerId: 'cust_abc123',
  startDate: '2024-01-01',
  endDate: '2024-12-31',
});

// Transaction summaries
const summaries = await svc.getTransactionAnalytics({
  groupBy: 'month',
  startDate: '2024-01-01',
  endDate: '2024-12-31',
});

// Generate a report (async)
const report = await svc.generateReport({
  reportType: 'portfolio_summary',
  startDate: '2024-01-01',
  endDate: '2024-12-31',
  format: 'pdf',
});

// Portfolio-level metrics
const portfolio = await svc.getPortfolioMetrics();

// Per-customer insights
const insights = await svc.getCustomerInsights({
  customerId: 'cust_abc123',
  metrics: ['loan_utilisation', 'repayment_rate'],
});

// Export data
const exportJob = await svc.exportData({
  entity: 'transactions',
  format: 'csv',
  filters: { status: 'completed' },
});
```

---

## Webhooks

Susu Digital sends webhook events signed with HMAC-SHA256. `WebhookHandler` verifies the signature and routes events to typed handlers.

### Signature format

```
Susu-Signature: t=<unix_timestamp>,v1=<hex_hmac>
```

### Express.js example

```typescript
import express from 'express';
import { WebhookHandler, WebhookSignatureError } from '@susudigital/sdk';

const handler = new WebhookHandler({
  secret: process.env.SUSU_WEBHOOK_SECRET!,
  tolerance: 300, // seconds, default 300
});

// Register event handlers (chainable)
handler
  .on('transaction.completed', async (event) => {
    console.log('Transaction done:', event.data.transactionId);
  })
  .on('loan.approved', async (event) => {
    console.log('Loan approved:', event.data.loanId);
  })
  .on('*', (event) => {
    // Wildcard — fires for every event type
    console.log('Received event:', event.type);
  });

const app = express();

app.post(
  '/webhooks/susu',
  express.raw({ type: 'application/json' }), // raw body required for HMAC
  async (req, res) => {
    try {
      const event = handler.constructEvent(
        req.body,
        req.headers['susu-signature'] as string,
      );
      await handler.dispatch(event);
      res.status(200).send('OK');
    } catch (err) {
      if (err instanceof WebhookSignatureError) {
        return res.status(400).send(`Webhook error: ${err.message}`);
      }
      throw err;
    }
  },
);
```

### Disabling signature verification (development only)

```typescript
const handler = new WebhookHandler({
  secret: 'any_string',
  verifySignatures: false, // ⚠️ never use in production
});
```

---

## Batch Processing

`BatchProcessor` handles high-volume operations by chunking items into batches and executing them with configurable concurrency. Individual item failures are collected rather than thrown, so one bad record does not abort the entire batch.

```typescript
import { SusuDigitalClient, BatchProcessor } from '@susudigital/sdk';

const client = new SusuDigitalClient({ apiKey: process.env.SUSU_API_KEY! });

const processor = new BatchProcessor(client, {
  batchSize: 100,   // items per chunk, default 100
  concurrency: 5,   // parallel API calls per chunk, default 5
});

// Bulk-create customers
const results = await processor.customers.createBatch([
  { firstName: 'Ama', lastName: 'Owusu', phone: '+233501111111' },
  { firstName: 'Kofi', lastName: 'Asante', phone: '+233502222222' },
  // ...thousands more
]);

console.log(`Created: ${results.successCount}, Failed: ${results.failureCount}`);

for (const item of results.successful) {
  console.log('✅', item.id);
}

for (const item of results.failed) {
  console.error('❌ index', item.index, item.error?.message);
}
```

`BatchResults<T>` properties:

| Property | Type | Description |
|----------|------|-------------|
| `results` | `BatchResult<T>[]` | All individual results (success + failure) |
| `successCount` | `number` | Number of successful operations |
| `failureCount` | `number` | Number of failed operations |
| `successful` | `T[]` | Data from successful operations |
| `failed` | `BatchResult<T>[]` | Failed result objects including `.error` |

---

## Error Handling

All SDK errors inherit from `SusuDigitalError` so you can catch broadly or specifically.

```typescript
import {
  SusuDigitalError,
  AuthenticationError,
  ValidationError,
  NotFoundError,
  RateLimitError,
  ServerError,
  NetworkError,
  WebhookSignatureError,
} from '@susudigital/sdk';

try {
  const customer = await client.customers.get('does_not_exist');
} catch (err) {
  if (err instanceof NotFoundError) {
    console.error('Customer not found');
  } else if (err instanceof ValidationError) {
    console.error('Validation failed:', err.fieldErrors);
  } else if (err instanceof RateLimitError) {
    console.warn(`Rate limited. Retry after ${err.retryAfter}s`);
  } else if (err instanceof AuthenticationError) {
    console.error('Invalid API key');
  } else if (err instanceof NetworkError) {
    console.error('Network issue. Retryable:', err.retryable);
  } else if (err instanceof SusuDigitalError) {
    console.error(`[${err.code}] ${err.message} (requestId: ${err.requestId})`);
  }
}
```

### Error hierarchy

```
SusuDigitalError
├── AuthenticationError   HTTP 401/403 — invalid or missing API key
├── ValidationError       HTTP 400/422 — includes .fieldErrors map
├── NotFoundError         HTTP 404 — resource does not exist
├── RateLimitError        HTTP 429 — includes .retryAfter (seconds)
├── ServerError           HTTP 5xx — transient, retryable
├── NetworkError          Connection/timeout issues — retryable
└── WebhookSignatureError Bad or missing HMAC signature
```

### Retryable errors

`err.retryable === true` for `NetworkError`, `ServerError`, and `RateLimitError`. The HTTP client retries these automatically up to `retryAttempts` times using exponential back-off.

---

## TypeScript

The SDK ships with bundled type declarations (`dist/types/index.d.ts`). No `@types/*` package is needed.

Key exported types:

```typescript
import type {
  // Config
  SusuDigitalClientConfig,
  RetryConfig,
  Environment,

  // Domain models
  Customer,
  Transaction,
  Loan,
  SavingsAccount,
  SavingsGoal,
  Balance,

  // Request shapes
  CreateCustomerRequest,
  UpdateCustomerRequest,
  DepositRequest,
  WithdrawalRequest,
  TransferRequest,
  LoanApplicationRequest,
  LoanApprovalRequest,
  LoanDisbursementRequest,
  RepaymentRequest,

  // Responses
  PaginatedResponse,
  ApiResponse,
  BatchResults,

  // Webhooks
  WebhookEvent,
  WebhookHandlerConfig,
} from '@susudigital/sdk';
```

---

## Development

```bash
# Install dependencies
npm install

# Type-check without emitting
npm run typecheck

# Run all tests
npm test

# Run tests in watch mode
npm run test:watch

# Run tests with coverage report
npm run test:coverage

# Lint source code
npm run lint

# Auto-fix lint issues
npm run lint:fix

# Format all source and test files
npm run format

# Build all outputs (CJS, ESM, type declarations)
npm run build
```

Build outputs:

| Format | Path |
|--------|------|
| CommonJS | `dist/cjs/index.js` |
| ES Modules | `dist/esm/index.js` |
| TypeScript declarations | `dist/types/index.d.ts` |

---

## License

MIT © [Susu Digital](https://susudigital.app) — see [LICENSE](./LICENSE) for details.

**Support:** [sdk-support@susudigital.app](mailto:sdk-support@susudigital.app)  
**Docs:** [developers.susudigital.app/js-sdk](https://developers.susudigital.app/js-sdk)  
**Issues:** [github.com/Mmabiaa/Susu-Digital-SDKs/issues](https://github.com/Mmabiaa/Susu-Digital-SDKs/issues)
