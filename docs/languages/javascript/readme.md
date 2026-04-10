# JavaScript/TypeScript SDK

> **Production-Ready SDK for Node.js and Browser Applications**  
> Full TypeScript support with comprehensive type definitions and modern async/await patterns

---

## Installation

### NPM
```bash
npm install @susudigital/sdk
```

### Yarn
```bash
yarn add @susudigital/sdk
```

### CDN (Browser)
```html
<script src="https://cdn.susudigital.app/sdk/v2.1.0/susu-digital.min.js"></script>
```

---

## Quick Start

### Basic Setup

```typescript
import { SusuDigitalClient } from '@susudigital/sdk';

const client = new SusuDigitalClient({
  apiKey: process.env.SUSU_API_KEY,
  environment: 'production', // or 'sandbox'
  organization: 'your-org-id',
  timeout: 30000,
  retryAttempts: 3
});
```

### Environment Configuration

```typescript
// .env file
SUSU_API_KEY=sk_live_your_secret_key_here
SUSU_ORGANIZATION_ID=org_your_organization_id
SUSU_ENVIRONMENT=production
SUSU_WEBHOOK_SECRET=whsec_your_webhook_secret
```

---

## Core Services

### Customer Management

```typescript
// Create a new customer
const customer = await client.customers.create({
  firstName: 'John',
  lastName: 'Doe',
  phone: '+233XXXXXXXXX',
  email: 'john.doe@example.com',
  dateOfBirth: '1990-01-15',
  address: {
    street: '123 Main Street',
    city: 'Accra',
    region: 'Greater Accra',
    country: 'Ghana'
  },
  identification: {
    type: 'national_id',
    number: 'GHA-123456789-0',
    expiryDate: '2030-12-31'
  }
});

// Get customer details
const customerDetails = await client.customers.get(customer.id);

// Update customer information
const updatedCustomer = await client.customers.update(customer.id, {
  email: 'john.newemail@example.com',
  phone: '+233YYYYYYYYY'
});

// Get customer balance
const balance = await client.customers.getBalance(customer.id);

// List customers with pagination
const customers = await client.customers.list({
  page: 1,
  limit: 50,
  search: 'john',
  status: 'active'
});
```

### Transaction Processing

```typescript
// Process a deposit
const deposit = await client.transactions.deposit({
  customerId: 'cust_123456789',
  amount: 100.00,
  currency: 'GHS',
  description: 'Savings deposit',
  reference: 'DEP-' + Date.now(),
  metadata: {
    branch: 'Accra Main',
    collector: 'John Collector'
  }
});

// Process a withdrawal
const withdrawal = await client.transactions.withdraw({
  customerId: 'cust_123456789',
  amount: 50.00,
  currency: 'GHS',
  description: 'Cash withdrawal',
  reference: 'WTH-' + Date.now()
});

// Transfer between customers
const transfer = await client.transactions.transfer({
  fromCustomerId: 'cust_123456789',
  toCustomerId: 'cust_987654321',
  amount: 25.00,
  currency: 'GHS',
  description: 'P2P transfer',
  reference: 'TRF-' + Date.now()
});

// Get transaction history
const transactions = await client.transactions.list({
  customerId: 'cust_123456789',
  startDate: '2026-01-01',
  endDate: '2026-03-31',
  type: 'deposit',
  status: 'completed'
});

// Get transaction details
const transaction = await client.transactions.get('txn_123456789');
```

### Loan Management

```typescript
// Create loan application
const loanApplication = await client.loans.createApplication({
  customerId: 'cust_123456789',
  amount: 5000.00,
  currency: 'GHS',
  purpose: 'business_expansion',
  term: 12, // months
  interestRate: 15.0, // annual percentage
  collateral: {
    type: 'property',
    description: 'Residential property in Accra',
    value: 50000.00
  },
  guarantors: [
    {
      name: 'Jane Guarantor',
      phone: '+233XXXXXXXXX',
      relationship: 'spouse'
    }
  ]
});

// Approve loan
const approvedLoan = await client.loans.approve(loanApplication.id, {
  approvedAmount: 4500.00,
  approvedTerm: 12,
  approvedRate: 14.0,
  conditions: ['Provide additional documentation']
});

// Disburse loan
const disbursement = await client.loans.disburse(approvedLoan.id, {
  disbursementMethod: 'bank_transfer',
  accountDetails: {
    bankCode: '030',
    accountNumber: '1234567890'
  }
});

// Record loan repayment
const repayment = await client.loans.recordRepayment(approvedLoan.id, {
  amount: 450.00,
  paymentDate: '2026-04-10',
  paymentMethod: 'cash',
  reference: 'REP-' + Date.now()
});

// Get loan schedule
const schedule = await client.loans.getSchedule(approvedLoan.id);

// List loans
const loans = await client.loans.list({
  customerId: 'cust_123456789',
  status: 'active',
  page: 1,
  limit: 20
});
```

### Savings Management

```typescript
// Create savings account
const savingsAccount = await client.savings.createAccount({
  customerId: 'cust_123456789',
  accountType: 'regular_savings',
  interestRate: 8.0,
  minimumBalance: 10.00,
  currency: 'GHS'
});

// Calculate interest
const interest = await client.savings.calculateInterest(savingsAccount.id, {
  startDate: '2026-01-01',
  endDate: '2026-03-31'
});

// Apply interest
const interestApplication = await client.savings.applyInterest(savingsAccount.id, {
  amount: interest.amount,
  period: '2026-Q1'
});

// Get account statement
const statement = await client.savings.getStatement(savingsAccount.id, {
  startDate: '2026-01-01',
  endDate: '2026-03-31',
  format: 'json' // or 'pdf'
});
```

---

## Advanced Features

### Batch Operations

```typescript
// Batch customer creation
const batchCustomers = await client.batch.customers.create([
  {
    firstName: 'John',
    lastName: 'Doe',
    phone: '+233XXXXXXXXX',
    email: 'john@example.com'
  },
  {
    firstName: 'Jane',
    lastName: 'Smith',
    phone: '+233YYYYYYYYY',
    email: 'jane@example.com'
  }
  // ... up to 100 customers per batch
]);

// Batch transaction processing
const batchTransactions = await client.batch.transactions.create([
  {
    customerId: 'cust_123',
    type: 'deposit',
    amount: 100.00,
    reference: 'BATCH-DEP-001'
  },
  {
    customerId: 'cust_456',
    type: 'withdrawal',
    amount: 50.00,
    reference: 'BATCH-WTH-001'
  }
  // ... up to 500 transactions per batch
]);

// Monitor batch status
const batchStatus = await client.batch.getStatus('batch_789');
```

### Webhook Handling

```typescript
import { WebhookHandler } from '@susudigital/sdk';

const webhookHandler = new WebhookHandler({
  secret: process.env.SUSU_WEBHOOK_SECRET,
  tolerance: 300 // 5 minutes tolerance for timestamp validation
});

// Express.js webhook endpoint
app.post('/webhooks/susu', express.raw({type: 'application/json'}), (req, res) => {
  const signature = req.headers['susu-signature'];
  
  try {
    const event = webhookHandler.constructEvent(req.body, signature);
    
    switch (event.type) {
      case 'transaction.completed':
        handleTransactionCompleted(event.data);
        break;
      case 'loan.approved':
        handleLoanApproved(event.data);
        break;
      case 'customer.created':
        handleCustomerCreated(event.data);
        break;
      default:
        console.log(`Unhandled event type: ${event.type}`);
    }
    
    res.status(200).send('OK');
  } catch (error) {
    console.error('Webhook error:', error);
    res.status(400).send('Invalid signature');
  }
});

// Event handlers
function handleTransactionCompleted(data: any) {
  console.log('Transaction completed:', data.transaction.id);
  // Update local database, send notifications, etc.
}

function handleLoanApproved(data: any) {
  console.log('Loan approved:', data.loan.id);
  // Trigger disbursement process, notify customer, etc.
}
```

### Analytics and Reporting

```typescript
// Generate financial reports
const report = await client.analytics.generateReport({
  type: 'financial_summary',
  period: 'monthly',
  startDate: '2026-01-01',
  endDate: '2026-03-31',
  format: 'json',
  includeCharts: true
});

// Get portfolio metrics
const portfolio = await client.analytics.getPortfolioMetrics({
  dateRange: 'last_30_days',
  groupBy: 'product_type'
});

// Customer analytics
const customerAnalytics = await client.analytics.getCustomerInsights({
  customerId: 'cust_123456789',
  metrics: ['transaction_frequency', 'average_balance', 'loan_performance']
});

// Export data
const exportData = await client.analytics.exportData({
  entity: 'transactions',
  format: 'csv',
  filters: {
    startDate: '2026-01-01',
    endDate: '2026-03-31',
    status: 'completed'
  }
});
```

---

## Error Handling

### Error Types

```typescript
import { 
  SusuError, 
  ValidationError, 
  AuthenticationError, 
  RateLimitError,
  NetworkError 
} from '@susudigital/sdk';

try {
  const customer = await client.customers.create(customerData);
} catch (error) {
  if (error instanceof ValidationError) {
    console.error('Validation failed:', error.details);
    // Handle validation errors
  } else if (error instanceof AuthenticationError) {
    console.error('Authentication failed:', error.message);
    // Handle auth errors - refresh tokens, etc.
  } else if (error instanceof RateLimitError) {
    console.error('Rate limit exceeded:', error.retryAfter);
    // Wait and retry
    setTimeout(() => {
      // Retry the operation
    }, error.retryAfter * 1000);
  } else if (error instanceof NetworkError) {
    console.error('Network error:', error.message);
    // Handle network issues
  } else {
    console.error('Unknown error:', error);
  }
}
```

### Retry Configuration

```typescript
const client = new SusuDigitalClient({
  apiKey: process.env.SUSU_API_KEY,
  retryConfig: {
    attempts: 3,
    delay: 1000, // Initial delay in ms
    backoff: 'exponential', // or 'linear'
    retryCondition: (error) => {
      // Custom retry logic
      return error.status >= 500 || error.code === 'NETWORK_ERROR';
    }
  }
});
```

---

## TypeScript Support

### Type Definitions

```typescript
// Customer types
interface Customer {
  id: string;
  firstName: string;
  lastName: string;
  phone: string;
  email?: string;
  dateOfBirth?: string;
  status: 'active' | 'inactive' | 'suspended';
  createdAt: string;
  updatedAt: string;
  address?: Address;
  identification?: Identification;
  metadata?: Record<string, any>;
}

interface Address {
  street: string;
  city: string;
  region: string;
  country: string;
  postalCode?: string;
}

interface Identification {
  type: 'national_id' | 'passport' | 'drivers_license';
  number: string;
  expiryDate?: string;
  issueDate?: string;
}

// Transaction types
interface Transaction {
  id: string;
  customerId: string;
  type: 'deposit' | 'withdrawal' | 'transfer';
  amount: number;
  currency: string;
  status: 'pending' | 'completed' | 'failed' | 'cancelled';
  description?: string;
  reference: string;
  createdAt: string;
  completedAt?: string;
  metadata?: Record<string, any>;
}

// Loan types
interface Loan {
  id: string;
  customerId: string;
  amount: number;
  currency: string;
  interestRate: number;
  term: number; // in months
  status: 'pending' | 'approved' | 'active' | 'completed' | 'defaulted';
  purpose: string;
  disbursedAt?: string;
  maturityDate?: string;
  totalRepaid: number;
  outstandingBalance: number;
  nextPaymentDate?: string;
  nextPaymentAmount?: number;
}
```

### Generic Response Types

```typescript
interface ApiResponse<T> {
  data: T;
  success: boolean;
  message?: string;
  requestId: string;
  timestamp: string;
}

interface PaginatedResponse<T> {
  data: T[];
  pagination: {
    page: number;
    limit: number;
    total: number;
    totalPages: number;
    hasNext: boolean;
    hasPrevious: boolean;
  };
  success: boolean;
  requestId: string;
  timestamp: string;
}

// Usage with generics
const customers: PaginatedResponse<Customer> = await client.customers.list();
const transaction: ApiResponse<Transaction> = await client.transactions.get('txn_123');
```

---

## Testing

### Unit Testing with Jest

```typescript
import { SusuDigitalClient } from '@susudigital/sdk';
import { jest } from '@jest/globals';

// Mock the SDK for testing
jest.mock('@susudigital/sdk');

describe('Customer Service', () => {
  let client: SusuDigitalClient;
  
  beforeEach(() => {
    client = new SusuDigitalClient({
      apiKey: 'test_key',
      environment: 'sandbox'
    });
  });

  test('should create customer successfully', async () => {
    const mockCustomer = {
      id: 'cust_123',
      firstName: 'John',
      lastName: 'Doe',
      phone: '+233XXXXXXXXX'
    };

    (client.customers.create as jest.Mock).mockResolvedValue(mockCustomer);

    const result = await client.customers.create({
      firstName: 'John',
      lastName: 'Doe',
      phone: '+233XXXXXXXXX'
    });

    expect(result).toEqual(mockCustomer);
    expect(client.customers.create).toHaveBeenCalledWith({
      firstName: 'John',
      lastName: 'Doe',
      phone: '+233XXXXXXXXX'
    });
  });
});
```

### Integration Testing

```typescript
import { SusuDigitalClient } from '@susudigital/sdk';

describe('Integration Tests', () => {
  let client: SusuDigitalClient;
  
  beforeAll(() => {
    client = new SusuDigitalClient({
      apiKey: process.env.SUSU_TEST_API_KEY,
      environment: 'sandbox'
    });
  });

  test('should create and retrieve customer', async () => {
    // Create customer
    const customerData = {
      firstName: 'Test',
      lastName: 'Customer',
      phone: '+233XXXXXXXXX',
      email: 'test@example.com'
    };

    const createdCustomer = await client.customers.create(customerData);
    expect(createdCustomer.id).toBeDefined();

    // Retrieve customer
    const retrievedCustomer = await client.customers.get(createdCustomer.id);
    expect(retrievedCustomer.firstName).toBe(customerData.firstName);
    expect(retrievedCustomer.lastName).toBe(customerData.lastName);

    // Cleanup
    await client.customers.delete(createdCustomer.id);
  });
});
```

---

## Performance Optimization

### Connection Pooling

```typescript
const client = new SusuDigitalClient({
  apiKey: process.env.SUSU_API_KEY,
  httpConfig: {
    maxConnections: 100,
    keepAlive: true,
    timeout: 30000,
    compression: true
  }
});
```

### Caching

```typescript
import { SusuDigitalClient, CacheProvider } from '@susudigital/sdk';

// Redis cache provider
const cacheProvider = new CacheProvider({
  type: 'redis',
  url: process.env.REDIS_URL,
  ttl: 300 // 5 minutes default TTL
});

const client = new SusuDigitalClient({
  apiKey: process.env.SUSU_API_KEY,
  cache: cacheProvider
});

// Cached requests
const customer = await client.customers.get('cust_123', { 
  cache: true, 
  ttl: 600 // 10 minutes
});
```

### Request Optimization

```typescript
// Batch multiple operations
const results = await Promise.all([
  client.customers.get('cust_123'),
  client.transactions.list({ customerId: 'cust_123', limit: 10 }),
  client.loans.list({ customerId: 'cust_123' })
]);

const [customer, transactions, loans] = results;
```

---

## Browser Usage

### ES Modules

```html
<!DOCTYPE html>
<html>
<head>
  <title>Susu Digital Integration</title>
</head>
<body>
  <script type="module">
    import { SusuDigitalClient } from 'https://cdn.susudigital.app/sdk/v2.1.0/esm/index.js';
    
    const client = new SusuDigitalClient({
      apiKey: 'pk_live_your_public_key', // Use public key for browser
      environment: 'production'
    });
    
    // Browser-safe operations only
    async function getCustomerBalance(customerId) {
      try {
        const balance = await client.customers.getBalance(customerId);
        document.getElementById('balance').textContent = `GHS ${balance.amount}`;
      } catch (error) {
        console.error('Error fetching balance:', error);
      }
    }
  </script>
</body>
</html>
```

### UMD Build

```html
<script src="https://cdn.susudigital.app/sdk/v2.1.0/umd/susu-digital.min.js"></script>
<script>
  const client = new SusuDigital.Client({
    apiKey: 'pk_live_your_public_key',
    environment: 'production'
  });
  
  // Use the client
  client.customers.getBalance('cust_123')
    .then(balance => console.log('Balance:', balance))
    .catch(error => console.error('Error:', error));
</script>
```

---

## Migration Guide

### From v1.x to v2.x

```typescript
// v1.x (deprecated)
const susu = require('susu-digital-sdk');
const client = susu.createClient('your-api-key');

client.createCustomer({
  name: 'John Doe',
  phone: '+233XXXXXXXXX'
}, (error, customer) => {
  if (error) {
    console.error(error);
  } else {
    console.log(customer);
  }
});

// v2.x (current)
import { SusuDigitalClient } from '@susudigital/sdk';

const client = new SusuDigitalClient({
  apiKey: 'your-api-key',
  environment: 'production'
});

try {
  const customer = await client.customers.create({
    firstName: 'John',
    lastName: 'Doe',
    phone: '+233XXXXXXXXX'
  });
  console.log(customer);
} catch (error) {
  console.error(error);
}
```

---

## Best Practices

### 1. **Environment Management**
```typescript
// Use environment-specific configurations
const config = {
  development: {
    apiKey: process.env.SUSU_DEV_API_KEY,
    environment: 'sandbox',
    timeout: 60000, // Longer timeout for debugging
    enableLogging: true
  },
  production: {
    apiKey: process.env.SUSU_PROD_API_KEY,
    environment: 'production',
    timeout: 30000,
    enableLogging: false
  }
};

const client = new SusuDigitalClient(config[process.env.NODE_ENV]);
```

### 2. **Error Handling Strategy**
```typescript
class SusuService {
  private client: SusuDigitalClient;
  
  constructor() {
    this.client = new SusuDigitalClient({
      apiKey: process.env.SUSU_API_KEY,
      environment: 'production'
    });
  }
  
  async createCustomerSafely(customerData: any) {
    try {
      return await this.client.customers.create(customerData);
    } catch (error) {
      // Log error for monitoring
      console.error('Customer creation failed:', {
        error: error.message,
        requestId: error.requestId,
        customerData: { ...customerData, phone: '[REDACTED]' }
      });
      
      // Re-throw with user-friendly message
      throw new Error('Unable to create customer. Please try again.');
    }
  }
}
```

### 3. **Rate Limiting Handling**
```typescript
import { RateLimitError } from '@susudigital/sdk';

async function processWithRateLimit<T>(
  operation: () => Promise<T>,
  maxRetries: number = 3
): Promise<T> {
  let retries = 0;
  
  while (retries < maxRetries) {
    try {
      return await operation();
    } catch (error) {
      if (error instanceof RateLimitError && retries < maxRetries - 1) {
        const delay = error.retryAfter * 1000;
        console.log(`Rate limited. Retrying in ${delay}ms...`);
        await new Promise(resolve => setTimeout(resolve, delay));
        retries++;
      } else {
        throw error;
      }
    }
  }
  
  throw new Error('Max retries exceeded');
}

// Usage
const customer = await processWithRateLimit(() => 
  client.customers.create(customerData)
);
```

---

## Support

### Getting Help

- **Documentation**: [developers.susudigital.app/js-sdk](https://developers.susudigital.app/js-sdk)
- **GitHub Issues**: [github.com/susudigital/js-sdk/issues](https://github.com/susudigital/js-sdk/issues)
- **Email Support**: [js-sdk@susudigital.app](mailto:js-sdk@susudigital.app)
- **Discord Community**: [discord.gg/susudigital](https://discord.gg/susudigital)

### Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/susudigital/js-sdk/blob/main/CONTRIBUTING.md) for details.

---

**© 2026 Susu Digital. All rights reserved.**

*Last Updated: April 10, 2026*  
*SDK Version: 2.1.0*