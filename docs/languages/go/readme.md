# Susu Digital Go SDK

> **Enterprise-Grade Go Integration**  
> Production-ready Go SDK for seamless integration with Susu Digital's microfinance platform

---

## 📋 Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [Core Services](#core-services)
- [Configuration](#configuration)
- [Error Handling](#error-handling)
- [Advanced Features](#advanced-features)
- [Best Practices](#best-practices)
- [Testing](#testing)
- [Migration Guide](#migration-guide)
- [Support](#support)

---

## Overview

The Susu Digital Go SDK provides a robust, idiomatic Go interface for integrating microfinance services into Go applications. Built with modern Go practices, it supports Go 1.21+ and offers comprehensive functionality for customer management, transactions, loans, savings, and analytics.

### Key Features

- **Idiomatic Go**: Clean, idiomatic Go API with proper error handling and interfaces
- **Concurrency Safe**: Fully goroutine-safe client with connection pooling
- **Context Support**: Full `context.Context` support for cancellation and deadlines
- **Type Safe**: Complete struct-based type system with compile-time validation
- **Retry Logic**: Built-in exponential backoff retry with configurable policies
- **Webhook Verification**: Secure HMAC-based webhook signature verification
- **Observability**: OpenTelemetry tracing and Prometheus metrics support
- **Zero Dependencies**: Minimal dependency footprint using the standard library

### Requirements

- Go 1.21 or higher
- A valid Susu Digital API key
- (Optional) Go modules enabled in your project

---

## Installation

### Go Modules

```bash
go get github.com/susudigital/go-sdk
```

### Specific Version

```bash
go get github.com/susudigital/go-sdk@v1.3.0
```

### Verify Installation

```bash
go list -m github.com/susudigital/go-sdk
```

---

## Quick Start

### Basic Setup

```go
package main

import (
	"context"
	"fmt"
	"log"
	"os"

	susu "github.com/susudigital/go-sdk"
)

func main() {
	// Initialize the client
	client, err := susu.NewClient(susu.Config{
		APIKey:      os.Getenv("SUSU_API_KEY"),
		Environment: susu.EnvironmentSandbox, // or susu.EnvironmentProduction
		OrgID:       os.Getenv("SUSU_ORGANIZATION_ID"),
		Timeout:     30,
		MaxRetries:  3,
	})
	if err != nil {
		log.Fatalf("Failed to create client: %v", err)
	}

	ctx := context.Background()

	// Create a customer
	customer, err := client.Customers.Create(ctx, susu.CreateCustomerRequest{
		FirstName: "John",
		LastName:  "Doe",
		Phone:     "+233XXXXXXXXX",
		Email:     "john.doe@example.com",
	})
	if err != nil {
		log.Fatalf("Failed to create customer: %v", err)
	}

	fmt.Printf("Customer created: %s\n", customer.ID)
}
```

### Environment Configuration

```bash
# .env file
SUSU_API_KEY=sk_live_your_secret_key_here
SUSU_ORGANIZATION_ID=org_your_organization_id
SUSU_ENVIRONMENT=production
SUSU_WEBHOOK_SECRET=whsec_your_webhook_secret
```

```go
// Load from environment using the built-in helper
client, err := susu.NewClientFromEnv()
if err != nil {
	log.Fatalf("Failed to create client: %v", err)
}
```

---

## Authentication

### API Key Authentication

```go
import susu "github.com/susudigital/go-sdk"

client, err := susu.NewClient(susu.Config{
	APIKey:      "sk_sandbox_your_api_key_here",
	Environment: susu.EnvironmentSandbox,
	OrgID:       "org_your_organization_id",
})
```

### OAuth 2.0 Authentication

```go
import (
	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/auth"
)

oauthProvider, err := auth.NewOAuth2Provider(auth.OAuth2Config{
	ClientID:     "your-client-id",
	ClientSecret: "your-client-secret",
	Scopes:       []string{"customers:read", "customers:write", "transactions:read"},
})
if err != nil {
	log.Fatal(err)
}

client, err := susu.NewClient(susu.Config{
	AuthProvider: oauthProvider,
	Environment:  susu.EnvironmentProduction,
})
```

### JWT Token Authentication

```go
import (
	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/auth"
)

tokenProvider := auth.NewJWTTokenProvider("your-jwt-token")

client, err := susu.NewClient(susu.Config{
	AuthProvider: tokenProvider,
	Environment:  susu.EnvironmentProduction,
})
```

---

## Core Services

### Customer Management

```go
import (
	"context"
	"time"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

// Create a new customer
customer, err := client.Customers.Create(ctx, susu.CreateCustomerRequest{
	FirstName:   "John",
	LastName:    "Doe",
	Phone:       "+233XXXXXXXXX",
	Email:       "john.doe@example.com",
	DateOfBirth: time.Date(1990, 1, 15, 0, 0, 0, 0, time.UTC),
	Address: &susu.Address{
		Street:  "123 Main Street",
		City:    "Accra",
		Region:  "Greater Accra",
		Country: "Ghana",
	},
	Identification: &susu.Identification{
		Type:       "national_id",
		Number:     "GHA-123456789-0",
		ExpiryDate: time.Date(2030, 12, 31, 0, 0, 0, 0, time.UTC),
	},
})
if err != nil {
	log.Fatal(err)
}

// Get customer details
customerDetails, err := client.Customers.Get(ctx, customer.ID)

// Update customer information
updatedCustomer, err := client.Customers.Update(ctx, customer.ID, susu.UpdateCustomerRequest{
	Email: "john.newemail@example.com",
	Phone: "+233YYYYYYYYY",
})

// Get customer balance
balance, err := client.Customers.GetBalance(ctx, customer.ID)

// List customers with pagination
customers, err := client.Customers.List(ctx, susu.ListCustomersRequest{
	Page:   1,
	Limit:  50,
	Search: "john",
	Status: susu.CustomerStatusActive,
})
if err != nil {
	log.Fatal(err)
}

fmt.Printf("Total customers: %d\n", customers.Pagination.Total)
for _, c := range customers.Data {
	fmt.Printf("Customer: %s %s\n", c.FirstName, c.LastName)
}
```

### Transaction Processing

```go
import (
	"context"
	"fmt"
	"time"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

// Process a deposit
deposit, err := client.Transactions.Deposit(ctx, susu.DepositRequest{
	CustomerID:  "cust_123456789",
	Amount:      100.00,
	Currency:    "GHS",
	Description: "Savings deposit",
	Reference:   fmt.Sprintf("DEP-%d", time.Now().Unix()),
	Metadata: map[string]any{
		"branch":    "Accra Main",
		"collector": "John Collector",
	},
})

// Process a withdrawal
withdrawal, err := client.Transactions.Withdraw(ctx, susu.WithdrawalRequest{
	CustomerID:  "cust_123456789",
	Amount:      50.00,
	Currency:    "GHS",
	Description: "Cash withdrawal",
	Reference:   fmt.Sprintf("WTH-%d", time.Now().Unix()),
})

// Transfer between customers
transfer, err := client.Transactions.Transfer(ctx, susu.TransferRequest{
	FromCustomerID: "cust_123456789",
	ToCustomerID:   "cust_987654321",
	Amount:         25.00,
	Currency:       "GHS",
	Description:    "P2P transfer",
	Reference:      fmt.Sprintf("TRF-%d", time.Now().Unix()),
})

// Get transaction history
transactions, err := client.Transactions.List(ctx, susu.ListTransactionsRequest{
	CustomerID: "cust_123456789",
	StartDate:  time.Date(2026, 1, 1, 0, 0, 0, 0, time.UTC),
	EndDate:    time.Date(2026, 3, 31, 0, 0, 0, 0, time.UTC),
	Type:       susu.TransactionTypeDeposit,
	Status:     susu.TransactionStatusCompleted,
})

// Get transaction details
transaction, err := client.Transactions.Get(ctx, "txn_123456789")
```

### Loan Management

```go
import (
	"context"
	"fmt"
	"time"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

// Create loan application
loanApplication, err := client.Loans.CreateApplication(ctx, susu.LoanApplicationRequest{
	CustomerID:   "cust_123456789",
	Amount:       5000.00,
	Currency:     "GHS",
	Purpose:      "business_expansion",
	Term:         12, // months
	InterestRate: 15.0,
	Collateral: &susu.Collateral{
		Type:        "property",
		Description: "Residential property in Accra",
		Value:       50000.00,
	},
	Guarantors: []susu.Guarantor{
		{
			Name:         "Jane Guarantor",
			Phone:        "+233XXXXXXXXX",
			Relationship: "spouse",
		},
	},
})

// Approve loan
approvedLoan, err := client.Loans.Approve(ctx, loanApplication.ID, susu.LoanApprovalRequest{
	ApprovedAmount: 4500.00,
	ApprovedTerm:   12,
	ApprovedRate:   14.0,
	Conditions:     []string{"Provide additional documentation"},
})

// Disburse loan
disbursement, err := client.Loans.Disburse(ctx, approvedLoan.ID, susu.DisbursementRequest{
	DisbursementMethod: "bank_transfer",
	AccountDetails: map[string]string{
		"bank_code":      "030",
		"account_number": "1234567890",
	},
})

// Record loan repayment
repayment, err := client.Loans.RecordRepayment(ctx, approvedLoan.ID, susu.RepaymentRequest{
	Amount:        450.00,
	PaymentDate:   time.Now(),
	PaymentMethod: "cash",
	Reference:     fmt.Sprintf("REP-%d", time.Now().Unix()),
})

// Get loan schedule
schedule, err := client.Loans.GetSchedule(ctx, approvedLoan.ID)

// List loans
loans, err := client.Loans.List(ctx, susu.ListLoansRequest{
	CustomerID: "cust_123456789",
	Status:     susu.LoanStatusActive,
	Page:       1,
	Limit:      20,
})
```

### Savings Management

```go
import (
	"context"
	"time"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

// Create savings account
savingsAccount, err := client.Savings.CreateAccount(ctx, susu.CreateSavingsAccountRequest{
	CustomerID:     "cust_123456789",
	AccountType:    susu.SavingsAccountTypeRegular,
	Currency:       "GHS",
	InterestRate:   8.0,
	MinimumBalance: 10.00,
})

// Get account balance
balance, err := client.Savings.GetBalance(ctx, "SACC001")

// Create savings goal
goal, err := client.Savings.CreateGoal(ctx, susu.CreateSavingsGoalRequest{
	AccountID:           "SACC001",
	Name:                "Emergency Fund",
	TargetAmount:        2000.00,
	TargetDate:          time.Now().AddDate(0, 12, 0),
	MonthlyContribution: 200.00,
})

// Get account statement
statement, err := client.Savings.GetStatement(ctx, savingsAccount.ID, susu.StatementRequest{
	StartDate: time.Date(2026, 1, 1, 0, 0, 0, 0, time.UTC),
	EndDate:   time.Date(2026, 3, 31, 0, 0, 0, 0, time.UTC),
	Format:    "json",
})
```

### Analytics Service

```go
import (
	"context"
	"time"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

// Get customer analytics
customerAnalytics, err := client.Analytics.GetCustomerAnalytics(ctx, susu.CustomerAnalyticsRequest{
	CustomerID: "cust_123456789",
	StartDate:  time.Now().AddDate(0, -6, 0),
	EndDate:    time.Now(),
})

// Get transaction analytics
transactionAnalytics, err := client.Analytics.GetTransactionAnalytics(ctx, susu.TransactionAnalyticsRequest{
	StartDate: time.Now().AddDate(0, -3, 0),
	EndDate:   time.Now(),
	GroupBy:   susu.GroupByMonth,
	Metrics:   []susu.Metric{susu.MetricTotalAmount, susu.MetricTransactionCount},
})

// Generate custom report
report, err := client.Analytics.GenerateReport(ctx, susu.ReportRequest{
	ReportType: susu.ReportTypeCustomerSummary,
	StartDate:  time.Now().AddDate(0, -1, 0),
	EndDate:    time.Now(),
	Format:     susu.ReportFormatPDF,
	Filters: map[string]string{
		"status": "active",
		"region": "Greater Accra",
	},
})
```

---

## Configuration

### Basic Configuration

```go
import (
	"time"

	susu "github.com/susudigital/go-sdk"
)

client, err := susu.NewClient(susu.Config{
	APIKey:        "your-api-key",
	Environment:   susu.EnvironmentSandbox,
	OrgID:         "your-org-id",
	Timeout:       30 * time.Second,
	MaxRetries:    3,
	EnableLogging: true,
})
```

### Advanced Configuration

```go
import (
	"time"

	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/config"
)

client, err := susu.NewClient(susu.Config{
	APIKey:      "your-api-key",
	Environment: susu.EnvironmentProduction,
	OrgID:       "your-org-id",

	// HTTP Configuration
	HTTP: config.HTTPConfig{
		ConnectTimeout:    10 * time.Second,
		ReadTimeout:       30 * time.Second,
		WriteTimeout:      30 * time.Second,
		MaxIdleConns:      100,
		MaxConnsPerHost:   20,
		IdleConnTimeout:   90 * time.Second,
		DisableKeepAlives: false,
	},

	// Retry Configuration
	Retry: config.RetryConfig{
		MaxAttempts:     3,
		BackoffMultiplier: 2.0,
		InitialDelay:    500 * time.Millisecond,
		MaxDelay:        10 * time.Second,
		RetryableStatus: []int{429, 500, 502, 503, 504},
	},

	// Custom Headers
	CustomHeaders: map[string]string{
		"X-Client-Version":   "1.0.0",
		"X-Integration-Type": "go-sdk",
	},

	// Logging
	EnableLogging: true,
	LogLevel:      susu.LogLevelInfo,
})
```

### Environment-Based Configuration

```go
// Uses SUSU_API_KEY, SUSU_ENVIRONMENT, SUSU_ORGANIZATION_ID, etc.
client, err := susu.NewClientFromEnv()
if err != nil {
	log.Fatalf("Configuration error: %v", err)
}
```

---

## Error Handling

### Error Types

```go
import (
	"errors"

	susu "github.com/susudigital/go-sdk"
	susuerrors "github.com/susudigital/go-sdk/errors"
)

customer, err := client.Customers.Create(ctx, createRequest)
if err != nil {
	var validationErr *susuerrors.ValidationError
	var authErr *susuerrors.AuthenticationError
	var rateLimitErr *susuerrors.RateLimitError
	var networkErr *susuerrors.NetworkError
	var notFoundErr *susuerrors.NotFoundError

	switch {
	case errors.As(err, &validationErr):
		fmt.Printf("Validation failed: %v\n", validationErr.Details)
		for field, msg := range validationErr.FieldErrors {
			fmt.Printf("  %s: %s\n", field, msg)
		}

	case errors.As(err, &authErr):
		fmt.Printf("Authentication failed: %s\n", authErr.Message)
		// Refresh token or re-authenticate

	case errors.As(err, &rateLimitErr):
		fmt.Printf("Rate limit exceeded. Retry after: %v\n", rateLimitErr.RetryAfter)
		time.Sleep(rateLimitErr.RetryAfter)

	case errors.As(err, &networkErr):
		fmt.Printf("Network error: %s\n", networkErr.Message)
		// Handle network issues

	case errors.As(err, &notFoundErr):
		fmt.Printf("Resource not found: %s\n", notFoundErr.Message)
		// Handle not found errors

	default:
		fmt.Printf("Susu Digital error: %v\n", err)
	}
}
```

### Error with Request ID

```go
var susuErr *susuerrors.SusuError
if errors.As(err, &susuErr) {
	fmt.Printf("Error code: %s\n", susuErr.Code)
	fmt.Printf("Request ID: %s\n", susuErr.RequestID)
	fmt.Printf("Retryable: %v\n", susuErr.Retryable)
}
```

### Context-Based Timeout Handling

```go
// Set a deadline for an operation
ctx, cancel := context.WithTimeout(context.Background(), 15*time.Second)
defer cancel()

customer, err := client.Customers.Create(ctx, createRequest)
if err != nil {
	if errors.Is(err, context.DeadlineExceeded) {
		fmt.Println("Request timed out")
		return
	}
	if errors.Is(err, context.Canceled) {
		fmt.Println("Request was cancelled")
		return
	}
	log.Fatal(err)
}
```

---

## Advanced Features

### Batch Operations

```go
import (
	"context"

	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/batch"
)

ctx := context.Background()

// Batch customer creation
batchProcessor := batch.NewProcessor(client, batch.Config{
	BatchSize:   100,
	Concurrency: 5,
})

customersData := []susu.CreateCustomerRequest{
	{FirstName: "John", LastName: "Doe", Phone: "+233XXXXXXXXX"},
	{FirstName: "Jane", LastName: "Smith", Phone: "+233YYYYYYYYY"},
	// ... more customers
}

results, err := batchProcessor.Customers.CreateBatch(ctx, customersData)
if err != nil {
	log.Fatal(err)
}

for _, result := range results {
	if result.Success {
		fmt.Printf("Created customer: %s\n", result.Data.ID)
	} else {
		fmt.Printf("Failed to create customer: %v\n", result.Error)
	}
}
```

### Concurrent Operations with Goroutines

```go
import (
	"context"
	"sync"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

var (
	wg       sync.WaitGroup
	mu       sync.Mutex
	customer *susu.Customer
	txns     *susu.TransactionList
	loans    *susu.LoanList
	errs     []error
)

wg.Add(3)

go func() {
	defer wg.Done()
	c, err := client.Customers.Get(ctx, "cust_123")
	mu.Lock()
	defer mu.Unlock()
	if err != nil {
		errs = append(errs, err)
		return
	}
	customer = c
}()

go func() {
	defer wg.Done()
	t, err := client.Transactions.List(ctx, susu.ListTransactionsRequest{CustomerID: "cust_123"})
	mu.Lock()
	defer mu.Unlock()
	if err != nil {
		errs = append(errs, err)
		return
	}
	txns = t
}()

go func() {
	defer wg.Done()
	l, err := client.Loans.List(ctx, susu.ListLoansRequest{CustomerID: "cust_123"})
	mu.Lock()
	defer mu.Unlock()
	if err != nil {
		errs = append(errs, err)
		return
	}
	loans = l
}()

wg.Wait()

if len(errs) > 0 {
	log.Fatalf("Errors occurred: %v", errs)
}
```

### Webhook Handling

```go
import (
	"fmt"
	"io"
	"log"
	"net/http"
	"os"

	"github.com/susudigital/go-sdk/webhook"
)

func main() {
	webhookHandler := webhook.NewHandler(webhook.Config{
		Secret:    os.Getenv("SUSU_WEBHOOK_SECRET"),
		Tolerance: 300, // 5 minutes tolerance
	})

	http.HandleFunc("/webhooks/susu", func(w http.ResponseWriter, r *http.Request) {
		body, err := io.ReadAll(r.Body)
		if err != nil {
			http.Error(w, "Failed to read body", http.StatusBadRequest)
			return
		}

		signature := r.Header.Get("Susu-Signature")

		event, err := webhookHandler.ConstructEvent(body, signature)
		if err != nil {
			log.Printf("Webhook signature verification failed: %v", err)
			http.Error(w, "Invalid signature", http.StatusUnauthorized)
			return
		}

		switch event.Type {
		case "transaction.completed":
			handleTransactionCompleted(event)
		case "loan.approved":
			handleLoanApproved(event)
		case "customer.created":
			handleCustomerCreated(event)
		default:
			log.Printf("Unhandled webhook event type: %s", event.Type)
		}

		w.WriteHeader(http.StatusOK)
		fmt.Fprint(w, "OK")
	})

	log.Fatal(http.ListenAndServe(":8080", nil))
}

func handleTransactionCompleted(event *webhook.Event) {
	log.Printf("Transaction completed: %s", event.Data["transaction_id"])
}

func handleLoanApproved(event *webhook.Event) {
	log.Printf("Loan approved: %s", event.Data["loan_id"])
}

func handleCustomerCreated(event *webhook.Event) {
	log.Printf("Customer created: %s", event.Data["customer_id"])
}
```

### Pagination Iterator

```go
import (
	"context"
	"fmt"

	susu "github.com/susudigital/go-sdk"
)

ctx := context.Background()

// Iterate through all customers using the built-in iterator
iter := client.Customers.Iter(ctx, susu.ListCustomersRequest{
	Limit:  100,
	Status: susu.CustomerStatusActive,
})

for iter.Next() {
	customer := iter.Customer()
	fmt.Printf("Customer: %s %s\n", customer.FirstName, customer.LastName)
}

if err := iter.Err(); err != nil {
	log.Fatalf("Iteration error: %v", err)
}
```

---

## Performance Optimization

### Connection Pooling

```go
import (
	"net/http"
	"time"

	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/config"
)

// Custom HTTP transport with connection pooling
transport := &http.Transport{
	MaxIdleConns:        100,
	MaxIdleConnsPerHost: 20,
	MaxConnsPerHost:     20,
	IdleConnTimeout:     90 * time.Second,
	DisableCompression:  false,
}

client, err := susu.NewClient(susu.Config{
	APIKey:      os.Getenv("SUSU_API_KEY"),
	Environment: susu.EnvironmentProduction,
	HTTP: config.HTTPConfig{
		Transport: transport,
	},
})
```

### Caching with Redis

```go
import (
	"context"
	"time"

	"github.com/redis/go-redis/v9"
	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/cache"
)

rdb := redis.NewClient(&redis.Options{
	Addr: "localhost:6379",
	DB:   0,
})

redisCache := cache.NewRedisCache(rdb, cache.Config{
	DefaultTTL: 5 * time.Minute,
})

client, err := susu.NewClient(susu.Config{
	APIKey: os.Getenv("SUSU_API_KEY"),
	Cache:  redisCache,
})

// Cached operations
customer, err := client.Customers.Get(ctx, "cust_123", susu.GetOptions{
	Cache: true,
	TTL:   10 * time.Minute,
})
```

---

## Best Practices

### 1. **Singleton Client**

```go
// client.go
package sususervice

import (
	"sync"

	susu "github.com/susudigital/go-sdk"
)

var (
	instance *susu.Client
	once     sync.Once
)

func GetClient() *susu.Client {
	once.Do(func() {
		var err error
		instance, err = susu.NewClientFromEnv()
		if err != nil {
			panic("Failed to initialize Susu Digital client: " + err.Error())
		}
	})
	return instance
}
```

### 2. **Service Layer Pattern**

```go
// services/customer_service.go
package services

import (
	"context"
	"errors"
	"log/slog"

	susu "github.com/susudigital/go-sdk"
	susuerrors "github.com/susudigital/go-sdk/errors"
)

type CustomerService struct {
	client *susu.Client
	logger *slog.Logger
}

func NewCustomerService(client *susu.Client, logger *slog.Logger) *CustomerService {
	return &CustomerService{client: client, logger: logger}
}

func (s *CustomerService) CreateCustomer(ctx context.Context, data susu.CreateCustomerRequest) (*susu.Customer, error) {
	customer, err := s.client.Customers.Create(ctx, data)
	if err != nil {
		s.logger.ErrorContext(ctx, "Failed to create customer",
			slog.String("error", err.Error()),
			slog.String("phone", "[REDACTED]"),
		)
		return nil, err
	}
	s.logger.InfoContext(ctx, "Customer created successfully",
		slog.String("customer_id", customer.ID),
	)
	return customer, nil
}

func (s *CustomerService) GetCustomer(ctx context.Context, id string) (*susu.Customer, error) {
	customer, err := s.client.Customers.Get(ctx, id)
	if err != nil {
		var notFoundErr *susuerrors.NotFoundError
		if errors.As(err, &notFoundErr) {
			return nil, nil // Return nil, nil for not found
		}
		return nil, err
	}
	return customer, nil
}
```

### 3. **Retry Strategy**

```go
import (
	"context"
	"errors"
	"math/rand"
	"time"

	susu "github.com/susudigital/go-sdk"
	susuerrors "github.com/susudigital/go-sdk/errors"
)

func retryWithBackoff[T any](
	ctx context.Context,
	maxRetries int,
	operation func() (T, error),
) (T, error) {
	var zero T
	baseDelay := time.Second

	for attempt := 0; attempt < maxRetries; attempt++ {
		result, err := operation()
		if err == nil {
			return result, nil
		}

		var rateLimitErr *susuerrors.RateLimitError
		var networkErr *susuerrors.NetworkError

		if !errors.As(err, &rateLimitErr) && !errors.As(err, &networkErr) {
			return zero, err // Non-retryable error
		}

		if attempt == maxRetries-1 {
			return zero, err // Last attempt failed
		}

		delay := time.Duration(1<<uint(attempt)) * baseDelay
		jitter := time.Duration(rand.Int63n(int64(baseDelay)))
		delay = min(delay+jitter, 60*time.Second)

		select {
		case <-time.After(delay):
		case <-ctx.Done():
			return zero, ctx.Err()
		}
	}

	return zero, errors.New("max retries exceeded")
}

// Usage
customer, err := retryWithBackoff(ctx, 3, func() (*susu.Customer, error) {
	return client.Customers.Create(ctx, createRequest)
})
```

### 4. **Configuration Management**

```go
// config/susu.go
package config

import (
	"fmt"
	"os"
	"strconv"
	"time"

	susu "github.com/susudigital/go-sdk"
)

type SusuConfig struct {
	APIKey      string
	Environment susu.Environment
	OrgID       string
	Timeout     time.Duration
	MaxRetries  int
	Logging     bool
}

func LoadSusuConfig() (*SusuConfig, error) {
	apiKey := os.Getenv("SUSU_API_KEY")
	if apiKey == "" {
		return nil, fmt.Errorf("SUSU_API_KEY is required")
	}

	env := susu.EnvironmentSandbox
	if os.Getenv("SUSU_ENVIRONMENT") == "production" {
		env = susu.EnvironmentProduction
	}

	maxRetries, _ := strconv.Atoi(os.Getenv("SUSU_MAX_RETRIES"))
	if maxRetries == 0 {
		maxRetries = 3
	}

	return &SusuConfig{
		APIKey:      apiKey,
		Environment: env,
		OrgID:       os.Getenv("SUSU_ORGANIZATION_ID"),
		Timeout:     30 * time.Second,
		MaxRetries:  maxRetries,
		Logging:     os.Getenv("SUSU_ENABLE_LOGGING") == "true",
	}, nil
}
```

---

## Testing

### Unit Testing with Mocks

```go
// customer_service_test.go
package services_test

import (
	"context"
	"testing"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/mock"
	susu "github.com/susudigital/go-sdk"
	"github.com/susudigital/go-sdk/mocks"
)

func TestCreateCustomerSuccess(t *testing.T) {
	// Arrange
	mockClient := mocks.NewMockClient(t)
	expectedCustomer := &susu.Customer{
		ID:        "cust_123",
		FirstName: "John",
		LastName:  "Doe",
		Phone:     "+233XXXXXXXXX",
	}

	mockClient.Customers.On("Create", mock.Anything, mock.MatchedBy(func(req susu.CreateCustomerRequest) bool {
		return req.FirstName == "John" && req.LastName == "Doe"
	})).Return(expectedCustomer, nil)

	service := NewCustomerService(mockClient, nil)

	// Act
	result, err := service.CreateCustomer(context.Background(), susu.CreateCustomerRequest{
		FirstName: "John",
		LastName:  "Doe",
		Phone:     "+233XXXXXXXXX",
	})

	// Assert
	assert.NoError(t, err)
	assert.Equal(t, "cust_123", result.ID)
	assert.Equal(t, "John", result.FirstName)
	mockClient.AssertExpectations(t)
}
```

### Integration Testing

```go
// integration_test.go
package integration_test

import (
	"context"
	"os"
	"testing"
	"time"

	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	susu "github.com/susudigital/go-sdk"
)

func setupClient(t *testing.T) *susu.Client {
	t.Helper()
	apiKey := os.Getenv("SUSU_TEST_API_KEY")
	if apiKey == "" {
		t.Skip("SUSU_TEST_API_KEY not set, skipping integration tests")
	}

	client, err := susu.NewClient(susu.Config{
		APIKey:      apiKey,
		Environment: susu.EnvironmentSandbox,
	})
	require.NoError(t, err)
	return client
}

func TestCustomerLifecycle(t *testing.T) {
	client := setupClient(t)
	ctx := context.Background()

	// Create customer
	customerData := susu.CreateCustomerRequest{
		FirstName: "Test",
		LastName:  "Customer",
		Phone:     "+233" + fmt.Sprintf("%09d", time.Now().UnixNano()%1000000000),
		Email:     fmt.Sprintf("test+%d@example.com", time.Now().UnixNano()),
	}

	createdCustomer, err := client.Customers.Create(ctx, customerData)
	require.NoError(t, err)
	assert.NotEmpty(t, createdCustomer.ID)

	t.Cleanup(func() {
		_ = client.Customers.Delete(context.Background(), createdCustomer.ID)
	})

	// Retrieve customer
	retrievedCustomer, err := client.Customers.Get(ctx, createdCustomer.ID)
	require.NoError(t, err)
	assert.Equal(t, createdCustomer.ID, retrievedCustomer.ID)
	assert.Equal(t, customerData.FirstName, retrievedCustomer.FirstName)

	// Update customer
	updatedCustomer, err := client.Customers.Update(ctx, createdCustomer.ID, susu.UpdateCustomerRequest{
		Email: "updated@example.com",
	})
	require.NoError(t, err)
	assert.Equal(t, "updated@example.com", updatedCustomer.Email)
}

func TestHandleValidationErrors(t *testing.T) {
	client := setupClient(t)
	ctx := context.Background()

	_, err := client.Customers.Create(ctx, susu.CreateCustomerRequest{
		FirstName: "", // Invalid: empty first name
		LastName:  "Test",
		Phone:     "invalid-phone", // Invalid: bad phone format
	})

	var validationErr *susuerrors.ValidationError
	assert.True(t, errors.As(err, &validationErr))
}
```

### Test Configuration

```go
// testutil/client.go
package testutil

import (
	"testing"

	susu "github.com/susudigital/go-sdk"
)

func NewTestClient(t *testing.T) *susu.Client {
	t.Helper()
	client, err := susu.NewClient(susu.Config{
		APIKey:      "sk_test_your_test_api_key",
		Environment: susu.EnvironmentSandbox,
		Timeout:     10,
		MaxRetries:  1, // Faster test execution
		EnableLogging: true,
	})
	if err != nil {
		t.Fatalf("Failed to create test client: %v", err)
	}
	return client
}
```

---

## Migration Guide

### From Version 1.2.x to 1.3.x

#### Breaking Changes

1. **Package Structure Changes**

```go
// Old (1.2.x)
import "github.com/susudigital/go-sdk/client"

c := client.New("your-api-key")

// New (1.3.x)
import susu "github.com/susudigital/go-sdk"

c, err := susu.NewClient(susu.Config{APIKey: "your-api-key"})
```

2. **Configuration Changes**

```go
// Old (1.2.x)
client := sdk.NewClient(sdk.WithAPIKey("key"), sdk.WithSandbox(true))

// New (1.3.x)
client, err := susu.NewClient(susu.Config{
	APIKey:      "key",
	Environment: susu.EnvironmentSandbox,
})
```

3. **Error Handling Changes**

```go
// Old (1.2.x)
if err, ok := err.(*sdk.ValidationError); ok {
	fmt.Println(err.Details)
}

// New (1.3.x)
var validationErr *susuerrors.ValidationError
if errors.As(err, &validationErr) {
	fmt.Println(validationErr.Details)
}
```

#### Migration Steps

1. Update dependency version in `go.mod`
2. Run `go mod tidy` to resolve dependencies
3. Update import paths to use new module path
4. Replace `sdk.NewClient` with `susu.NewClient` accepting a `susu.Config` struct
5. Update error handling to use `errors.As` with the new typed error types
6. Replace functional options pattern with struct-based configuration
7. Run tests to ensure compatibility

---

## Support

### Documentation and Resources

- **API Documentation**: [developers.susudigital.app/go](https://developers.susudigital.app/go)
- **GoDoc**: [pkg.go.dev/github.com/susudigital/go-sdk](https://pkg.go.dev/github.com/susudigital/go-sdk)
- **GitHub Repository**: [github.com/susudigital/go-sdk](https://github.com/susudigital/go-sdk)
- **Sample Applications**: [github.com/susudigital/go-examples](https://github.com/susudigital/go-examples)

### Getting Help

- **Technical Support**: [go-sdk-support@susudigital.app](mailto:go-sdk-support@susudigital.app)
- **Community Forum**: [community.susudigital.app](https://community.susudigital.app)
- **Stack Overflow**: Tag questions with `susu-digital-go`
- **Discord**: Join our developer community at [discord.gg/susudigital](https://discord.gg/susudigital)

### Contributing

We welcome contributions to the Go SDK! Please see our [Contributing Guide](https://github.com/susudigital/go-sdk/blob/main/CONTRIBUTING.md) for details.

---

**© 2026 Susu Digital. All rights reserved.**

*Last Updated: April 10, 2026*  
*Go SDK Version: 1.3.0*  
*Documentation Version: 1.0.0*
