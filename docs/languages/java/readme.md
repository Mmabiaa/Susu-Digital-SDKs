# Susu Digital Java SDK

> **Enterprise-Grade Java Integration**  
> Production-ready Java SDK for seamless integration with Susu Digital's microfinance platform

---

## 📋 Table of Contents

- [Overview](#overview)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Authentication](#authentication)
- [Core Services](#core-services)
- [Configuration](#configuration)
- [Error Handling](#error-handling)
- [Best Practices](#best-practices)
- [Examples](#examples)
- [Testing](#testing)
- [Migration Guide](#migration-guide)
- [Support](#support)

---

## Overview

The Susu Digital Java SDK provides a robust, type-safe interface for integrating microfinance services into Java applications. Built with modern Java practices, it supports Java 11+ and offers comprehensive functionality for customer management, transactions, loans, and analytics.

### Key Features

- **Type Safety**: Full generic type support with compile-time validation
- **Async Support**: CompletableFuture-based asynchronous operations
- **Spring Integration**: Native Spring Boot starter and auto-configuration
- **Reactive Streams**: WebFlux reactive programming support
- **Enterprise Ready**: Connection pooling, retry logic, and circuit breakers
- **Security First**: Built-in authentication, request signing, and encryption
- **Observability**: Micrometer metrics and distributed tracing support

### Requirements

- Java 11 or higher
- Maven 3.6+ or Gradle 6.0+
- Spring Boot 2.7+ (optional, for Spring integration)

---

## Installation

### Maven

```xml
<dependency>
    <groupId>app.susudigital</groupId>
    <artifactId>susu-digital-sdk</artifactId>
    <version>0.8.5</version>
</dependency>
```

### Gradle

```gradle
implementation 'app.susudigital:susu-digital-sdk:0.8.5'
```### Spring
 Boot Starter

For Spring Boot applications, use the starter dependency:

```xml
<dependency>
    <groupId>app.susudigital</groupId>
    <artifactId>susu-digital-spring-boot-starter</artifactId>
    <version>0.8.5</version>
</dependency>
```

---

## Quick Start

### Basic Setup

```java
import app.susudigital.sdk.SusuDigitalClient;
import app.susudigital.sdk.config.SusuConfig;
import app.susudigital.sdk.model.Customer;
import app.susudigital.sdk.model.CreateCustomerRequest;

public class QuickStartExample {
    public static void main(String[] args) {
        // Initialize the client
        SusuConfig config = SusuConfig.builder()
            .apiKey("your-api-key")
            .environment(Environment.SANDBOX)
            .organizationId("your-org-id")
            .build();
            
        SusuDigitalClient client = new SusuDigitalClient(config);
        
        // Create a customer
        CreateCustomerRequest request = CreateCustomerRequest.builder()
            .firstName("John")
            .lastName("Doe")
            .phone("+233XXXXXXXXX")
            .email("john.doe@example.com")
            .build();
            
        try {
            Customer customer = client.customers().create(request);
            System.out.println("Customer created: " + customer.getId());
        } catch (SusuException e) {
            System.err.println("Error: " + e.getMessage());
        }
    }
}
```

### Spring Boot Integration

```java
@RestController
@RequestMapping("/api/customers")
public class CustomerController {
    
    private final SusuDigitalClient susuClient;
    
    public CustomerController(SusuDigitalClient susuClient) {
        this.susuClient = susuClient;
    }
    
    @PostMapping
    public ResponseEntity<Customer> createCustomer(@RequestBody CreateCustomerRequest request) {
        try {
            Customer customer = susuClient.customers().create(request);
            return ResponseEntity.ok(customer);
        } catch (SusuException e) {
            return ResponseEntity.badRequest().build();
        }
    }
}
```

---

## Authentication

### API Key Authentication

```java
SusuConfig config = SusuConfig.builder()
    .apiKey("sk_sandbox_your_api_key_here")
    .environment(Environment.SANDBOX)
    .organizationId("org_your_organization_id")
    .build();

SusuDigitalClient client = new SusuDigitalClient(config);
```### OA
uth 2.0 Authentication

```java
import app.susudigital.sdk.auth.OAuth2Config;

OAuth2Config oauthConfig = OAuth2Config.builder()
    .clientId("your-client-id")
    .clientSecret("your-client-secret")
    .scope("customers:read customers:write transactions:read")
    .build();

SusuConfig config = SusuConfig.builder()
    .oauth2Config(oauthConfig)
    .environment(Environment.PRODUCTION)
    .build();

SusuDigitalClient client = new SusuDigitalClient(config);
```

### JWT Token Authentication

```java
import app.susudigital.sdk.auth.JwtTokenProvider;

JwtTokenProvider tokenProvider = new JwtTokenProvider("your-jwt-token");

SusuConfig config = SusuConfig.builder()
    .tokenProvider(tokenProvider)
    .environment(Environment.PRODUCTION)
    .build();

SusuDigitalClient client = new SusuDigitalClient(config);
```

---

## Core Services

### Customer Service

```java
import app.susudigital.sdk.service.CustomerService;
import app.susudigital.sdk.model.*;

CustomerService customerService = client.customers();

// Create customer
CreateCustomerRequest createRequest = CreateCustomerRequest.builder()
    .firstName("Jane")
    .lastName("Smith")
    .phone("+233XXXXXXXXX")
    .email("jane.smith@example.com")
    .dateOfBirth(LocalDate.of(1990, 5, 15))
    .address(Address.builder()
        .street("123 Main St")
        .city("Accra")
        .region("Greater Accra")
        .country("Ghana")
        .build())
    .build();

Customer customer = customerService.create(createRequest);

// Get customer by ID
Customer retrievedCustomer = customerService.getById("CUST001");

// Update customer
UpdateCustomerRequest updateRequest = UpdateCustomerRequest.builder()
    .customerId("CUST001")
    .email("jane.newemail@example.com")
    .build();

Customer updatedCustomer = customerService.update(updateRequest);

// List customers with pagination
CustomerListRequest listRequest = CustomerListRequest.builder()
    .page(1)
    .limit(50)
    .status(CustomerStatus.ACTIVE)
    .build();

PagedResult<Customer> customers = customerService.list(listRequest);
```### Tra
nsaction Service

```java
import app.susudigital.sdk.service.TransactionService;
import app.susudigital.sdk.model.*;

TransactionService transactionService = client.transactions();

// Create a deposit transaction
CreateTransactionRequest depositRequest = CreateTransactionRequest.builder()
    .customerId("CUST001")
    .amount(new BigDecimal("100.00"))
    .currency("GHS")
    .type(TransactionType.DEPOSIT)
    .description("Monthly savings deposit")
    .reference("DEP-" + System.currentTimeMillis())
    .build();

Transaction deposit = transactionService.create(depositRequest);

// Create a withdrawal transaction
CreateTransactionRequest withdrawalRequest = CreateTransactionRequest.builder()
    .customerId("CUST001")
    .amount(new BigDecimal("50.00"))
    .currency("GHS")
    .type(TransactionType.WITHDRAWAL)
    .description("ATM withdrawal")
    .reference("WTH-" + System.currentTimeMillis())
    .build();

Transaction withdrawal = transactionService.create(withdrawalRequest);

// Get transaction by ID
Transaction transaction = transactionService.getById("TXN001");

// List transactions with filters
TransactionListRequest listRequest = TransactionListRequest.builder()
    .customerId("CUST001")
    .startDate(LocalDateTime.now().minusDays(30))
    .endDate(LocalDateTime.now())
    .type(TransactionType.DEPOSIT)
    .status(TransactionStatus.COMPLETED)
    .page(1)
    .limit(100)
    .build();

PagedResult<Transaction> transactions = transactionService.list(listRequest);
```

### Loan Service

```java
import app.susudigital.sdk.service.LoanService;
import app.susudigital.sdk.model.*;

LoanService loanService = client.loans();

// Create loan application
CreateLoanRequest loanRequest = CreateLoanRequest.builder()
    .customerId("CUST001")
    .amount(new BigDecimal("5000.00"))
    .currency("GHS")
    .termMonths(12)
    .interestRate(new BigDecimal("15.0"))
    .purpose("Business expansion")
    .collateral(Collateral.builder()
        .type(CollateralType.PROPERTY)
        .description("Residential property in Accra")
        .value(new BigDecimal("50000.00"))
        .build())
    .build();

Loan loan = loanService.create(loanRequest);

// Get loan by ID
Loan retrievedLoan = loanService.getById("LOAN001");

// Make loan payment
LoanPaymentRequest paymentRequest = LoanPaymentRequest.builder()
    .loanId("LOAN001")
    .amount(new BigDecimal("500.00"))
    .paymentDate(LocalDate.now())
    .reference("PAY-" + System.currentTimeMillis())
    .build();

LoanPayment payment = loanService.makePayment(paymentRequest);

// Get loan schedule
List<LoanScheduleItem> schedule = loanService.getSchedule("LOAN001");
```### Savi
ngs Service

```java
import app.susudigital.sdk.service.SavingsService;
import app.susudigital.sdk.model.*;

SavingsService savingsService = client.savings();

// Create savings account
CreateSavingsAccountRequest accountRequest = CreateSavingsAccountRequest.builder()
    .customerId("CUST001")
    .accountType(SavingsAccountType.REGULAR)
    .currency("GHS")
    .interestRate(new BigDecimal("8.0"))
    .minimumBalance(new BigDecimal("10.00"))
    .build();

SavingsAccount account = savingsService.createAccount(accountRequest);

// Get account balance
Balance balance = savingsService.getBalance("SACC001");

// Create savings goal
CreateSavingsGoalRequest goalRequest = CreateSavingsGoalRequest.builder()
    .accountId("SACC001")
    .name("Emergency Fund")
    .targetAmount(new BigDecimal("2000.00"))
    .targetDate(LocalDate.now().plusMonths(12))
    .monthlyContribution(new BigDecimal("200.00"))
    .build();

SavingsGoal goal = savingsService.createGoal(goalRequest);
```

### Analytics Service

```java
import app.susudigital.sdk.service.AnalyticsService;
import app.susudigital.sdk.model.*;

AnalyticsService analyticsService = client.analytics();

// Get customer analytics
CustomerAnalytics customerAnalytics = analyticsService.getCustomerAnalytics(
    "CUST001", 
    LocalDate.now().minusMonths(6), 
    LocalDate.now()
);

// Get transaction analytics
TransactionAnalyticsRequest analyticsRequest = TransactionAnalyticsRequest.builder()
    .startDate(LocalDate.now().minusMonths(3))
    .endDate(LocalDate.now())
    .groupBy(GroupBy.MONTH)
    .metrics(Arrays.asList(Metric.TOTAL_AMOUNT, Metric.TRANSACTION_COUNT))
    .build();

TransactionAnalytics transactionAnalytics = analyticsService.getTransactionAnalytics(analyticsRequest);

// Generate custom report
ReportRequest reportRequest = ReportRequest.builder()
    .reportType(ReportType.CUSTOMER_SUMMARY)
    .startDate(LocalDate.now().minusMonths(1))
    .endDate(LocalDate.now())
    .format(ReportFormat.PDF)
    .filters(Map.of("status", "ACTIVE", "region", "Greater Accra"))
    .build();

Report report = analyticsService.generateReport(reportRequest);
```

---

## Configuration

### Basic Configuration

```java
SusuConfig config = SusuConfig.builder()
    .apiKey("your-api-key")
    .environment(Environment.SANDBOX)
    .organizationId("your-org-id")
    .timeout(Duration.ofSeconds(30))
    .retryAttempts(3)
    .enableLogging(true)
    .build();
```### Adva
nced Configuration

```java
import app.susudigital.sdk.config.*;
import java.time.Duration;

SusuConfig config = SusuConfig.builder()
    .apiKey("your-api-key")
    .environment(Environment.PRODUCTION)
    .organizationId("your-org-id")
    
    // HTTP Configuration
    .httpConfig(HttpConfig.builder()
        .connectTimeout(Duration.ofSeconds(10))
        .readTimeout(Duration.ofSeconds(30))
        .writeTimeout(Duration.ofSeconds(30))
        .maxConnections(100)
        .keepAliveDuration(Duration.ofMinutes(5))
        .build())
    
    // Retry Configuration
    .retryConfig(RetryConfig.builder()
        .maxAttempts(3)
        .backoffMultiplier(2.0)
        .initialDelay(Duration.ofMillis(500))
        .maxDelay(Duration.ofSeconds(10))
        .retryableExceptions(Arrays.asList(
            IOException.class,
            SocketTimeoutException.class
        ))
        .build())
    
    // Circuit Breaker Configuration
    .circuitBreakerConfig(CircuitBreakerConfig.builder()
        .failureThreshold(5)
        .recoveryTimeout(Duration.ofMinutes(1))
        .enabled(true)
        .build())
    
    // Custom headers
    .customHeaders(Map.of(
        "X-Client-Version", "1.0.0",
        "X-Integration-Type", "java-sdk"
    ))
    
    .build();
```

### Spring Boot Configuration

```yaml
# application.yml
susu:
  digital:
    api-key: ${SUSU_API_KEY}
    environment: sandbox
    organization-id: ${SUSU_ORG_ID}
    timeout: 30s
    retry-attempts: 3
    enable-logging: true
    
    http:
      connect-timeout: 10s
      read-timeout: 30s
      max-connections: 100
      
    circuit-breaker:
      enabled: true
      failure-threshold: 5
      recovery-timeout: 60s
```

```java
@Configuration
@EnableSusuDigital
public class SusuConfiguration {
    
    @Bean
    @ConditionalOnMissingBean
    public SusuConfigCustomizer susuConfigCustomizer() {
        return config -> config
            .customHeaders(Map.of("X-App-Name", "MyApp"))
            .enableMetrics(true);
    }
}
```

---

## Error Handling

### Exception Hierarchy

```java
// Base exception
public class SusuException extends Exception {
    private final String code;
    private final String requestId;
    private final boolean retryable;
}

// Specific exceptions
public class SusuAuthenticationException extends SusuException { }
public class SusuValidationException extends SusuException { }
public class SusuRateLimitException extends SusuException { }
public class SusuServerException extends SusuException { }
public class SusuNetworkException extends SusuException { }
```### Error Handling Examples

```java
import app.susudigital.sdk.exception.*;

try {
    Customer customer = client.customers().create(request);
} catch (SusuValidationException e) {
    // Handle validation errors
    System.err.println("Validation error: " + e.getMessage());
    Map<String, String> fieldErrors = e.getFieldErrors();
    fieldErrors.forEach((field, error) -> 
        System.err.println(field + ": " + error));
        
} catch (SusuRateLimitException e) {
    // Handle rate limiting
    Duration retryAfter = e.getRetryAfter();
    System.err.println("Rate limited. Retry after: " + retryAfter);
    
} catch (SusuAuthenticationException e) {
    // Handle authentication errors
    System.err.println("Authentication failed: " + e.getMessage());
    // Refresh token or re-authenticate
    
} catch (SusuServerException e) {
    // Handle server errors
    if (e.isRetryable()) {
        // Implement retry logic
        System.err.println("Server error, retrying...");
    } else {
        System.err.println("Server error: " + e.getMessage());
    }
    
} catch (SusuException e) {
    // Handle all other Susu exceptions
    System.err.println("Susu API error: " + e.getMessage());
    System.err.println("Request ID: " + e.getRequestId());
}
```

### Async Error Handling

```java
CompletableFuture<Customer> future = client.customers().createAsync(request);

future.handle((customer, throwable) -> {
    if (throwable != null) {
        if (throwable instanceof SusuValidationException) {
            // Handle validation error
            return handleValidationError((SusuValidationException) throwable);
        } else if (throwable instanceof SusuRateLimitException) {
            // Handle rate limiting
            return handleRateLimit((SusuRateLimitException) throwable);
        } else {
            // Handle other errors
            return handleGror(throwable);
        }
    } else {
        // Handle success
        return handleSuccess(customer);
    }
});
```

---

## Best Practices

### Connection Management

```java
// Use a single client instance across your application
@Component
@Singleton
public class SusuClientProvider {
    
    private final SusuDigitalClient client;
    
    public SusuClientProvider(@Value("${susu.api-key}") String apiKey) {
        SusuConfig config = SusuConfig.builder()
            .apiKey(apiKey)
            .environment(Environment.PRODUCTION)
            .httpConfig(HttpConfig.builder()
                .maxConnections(50)
                .keepAliveDuration(Duration.ofMinutes(5))
                .build())
            .build();
            
        this.client = new SusuDigitalClient(config);
    }
    
    public SusuDigitalClient getClient() {
        return client;
    }
    
    @PreDestroy
    public void cleanup() {
        client.close();
    }
}
```##
# Async Programming

```java
// Use async methods for better performance
CompletableFuture<Customer> customerFuture = client.customers().createAsync(request);
CompletableFuture<Transaction> transactionFuture = client.transactions().createAsync(txnRequest);

// Combine multiple async operations
CompletableFuture<String> combinedResult = customerFuture
    .thenCompose(customer -> 
        client.transactions().createAsync(
            CreateTransactionRequest.builder()
                .customerId(customer.getId())
                .amount(new BigDecimal("100.00"))
                .type(TransactionType.DEPOSIT)
                .build()
        ))
    .thenApply(transaction -> 
        "Customer and transaction created: " + transaction.getId()
    );

combinedResult.thenAccept(System.out::println);
```

### Batch Operations

```java
// Process multiple operations efficiently
List<CreateCustomerRequest> customerRequests = Arrays.asList(
    CreateCustomerRequest.builder().firstName("John").lastName("Doe").build(),
    CreateCustomerRequest.builder().firstName("Jane").lastName("Smith").build()
);

BatchRequest<CreateCustomerRequest> batchRequest = BatchRequest.<CreateCustomerRequest>builder()
    .requests(customerRequests)
    .batchSize(10)
    .parallelism(3)
    .build();

BatchResult<Customer> batchResult = client.customers().createBatch(batchRequest);

// Handle results
batchResult.getSuccessful().forEach(customer -> 
    System.out.println("Created: " + customer.getId()));
    
batchResult.getFailed().forEach(failure -> 
    System.err.println("Failed: " + failure.getError().getMessage()));
```

### Caching

```java
@Service
public class CustomerCacheService {
    
    private final SusuDigitalClient client;
    private final Cache<String, Customer> customerCache;
    
    public CustomerCacheService(SusuDigitalClient client) {
        this.client = client;
        this.customerCache = Caffeine.newBuilder()
            .maximumSize(1000)
            .expireAfterWrite(Duration.ofMinutes(15))
            .build();
    }
    
    public Customer getCustomer(String customerId) throws SusuException {
        return customerCache.get(customerId, id -> {
            try {
                return client.customers().getById(id);
            } catch (SusuException e) {
                throw new RuntimeException(e);
            }
        });
    }
    
    public void invalidateCustomer(String customerId) {
        customerCache.invalidate(customerId);
    }
}
```

---

## Examples

### Complete Customer Management Example

```java
@Service
@Transactional
public class CustomerManagementService {
    
    private final SusuDigitalClient susuClient;
    private final CustomerRepository customerRepository;
    
    public CustomerManagementService(SusuDigitalClient susuClient, 
                                   CustomerRepository customerRepository) {
        this.susuClient = susuClient;
        this.customerRepository = customerRepository;
    }
    
    public CustomerDto createCustomerWithAccount(CreateCustomerDto dto) {
        try {
            // Create customer in Susu Digital
            CreateCustomerRequest susuRequest = CreateCustomerRequest.builder()
                .firstName(dto.getFirstName())
                .lastName(dto.getLastName())
                .phone(dto.getPhone())
                .email(dto.getEmail())
                .dateOfBirth(dto.getDateOfBirth())
                .address(mapToSusuAddress(dto.getAddress()))
                .build();
                
            Customer susuCustomer = susuClient.customers().create(susuRequest);
            
            // Create savings account
            CreateSavingsAccountRequest accountRequest = CreateSavingsAccountRequest.builder()
                .customerId(susuCustomer.getId())
                .accountType(SavingsAccountType.REGULAR)
                .currency("GHS")
                .interestRate(new BigDecimal("8.0"))
                .build();
                
            SavingsAccount account = susuClient.savings().createAccount(accountRequest);
            
            // Save to local database
            CustomerEntity entity = new CustomerEntity();
            entity.setSusuCustomerId(susuCustomer.getId());
            entity.setSusuAccountId(account.getId());
            entity.setFirstName(dto.getFirstName());
            entity.setLastName(dto.getLastName());
            entity.setEmail(dto.getEmail());
            entity.setPhone(dto.getPhone());
            entity.setCreatedAt(LocalDateTime.now());
            
            CustomerEntity savedEntity = customerRepository.save(entity);
            
            return mapToDto(savedEntity, susuCustomer, account);
            
        } catch (SusuException e) {
            throw new CustomerCreationException("Failed to create customer", e);
        }
    }
}
```### Webhoo
k Handling Example

```java
@RestController
@RequestMapping("/webhooks/susu")
public class SusuWebhookController {
    
    private final WebhookVerifier webhookVerifier;
    private final TransactionEventHandler transactionHandler;
    
    public SusuWebhookController(
            @Value("${susu.webhook.secret}") String webhookSecret,
            TransactionEventHandler transactionHandler) {
        this.webhookVerifier = new WebhookVerifier(webhookSecret);
        this.transactionHandler = transactionHandler;
    }
    
    @PostMapping("/events")
    public ResponseEntity<String> handleWebhook(
            @RequestBody String payload,
            @RequestHeader("X-Susu-Signature") String signature,
            @RequestHeader("X-Susu-Timestamp") String timestamp) {
        
        try {
            // Verify webhook signature
            if (!webhookVerifier.verify(payload, signature, timestamp)) {
                return ResponseEntity.status(HttpStatus.UNAUTHORIZED)
                    .body("Invalid signature");
            }
            
            // Parse webhook event
            WebhookEvent event = WebhookEvent.fromJson(payload);
            
            // Handle different event types
            switch (event.getType()) {
                case "transaction.completed":
                    transactionHandler.handleTransactionCompleted(
                        event.getData(Transaction.class));
                    break;
                    
                case "transaction.failed":
                    transactionHandler.handleTransactionFailed(
                        event.getData(Transaction.class));
                    break;
                    
                case "customer.created":
                    handleCustomerCreated(event.getData(Customer.class));
                    break;
                    
                default:
                    log.warn("Unhandled webhook event type: {}", event.getType());
            }
            
            return ResponseEntity.ok("Event processed");
            
        } catch (Exception e) {
            log.error("Error processing webhook", e);
            return ResponseEntity.status(HttpStatus.INTERNAL_SERVER_ERROR)
                .body("Error processing webhook");
        }
    }
    
    private void handleCustomerCreated(Customer customer) {
        // Send welcome email
        emailService.sendWelcomeEmail(customer.getEmail(), customer.getFirstName());
        
        // Create local customer record
        customerService.syncCustomerFromSusu(customer);
    }
}
```

### Reactive Programming Example

```java
@Service
public class ReactiveTransactionService {
    
    private final SusuDigitalReactiveClient susuClient;
    
    public ReactiveTransactionService(SusuDigitalReactiveClient susuClient) {
        this.susuClient = susuClient;
    }
    
    public Flux<TransactionSummary> getCustomerTransactionSummary(String customerId) {
        return susuClient.customers().getById(customerId)
            .flatMapMany(customer -> 
                susuClient.transactions().listByCustomer(customer.getId())
                    .map(this::mapToSummary)
            )
            .onErrorResume(SusuException.class, error -> {
                log.error("Error fetching transactions for customer: {}", customerId, error);
                return Flux.empty();
            });
    }
    
    public Mono<TransactionResult> processMultipleTransactions(
            List<CreateTransactionRequest> requests) {
        
        return Flux.fromIterable(requests)
            .flatMap(request -> 
                susuClient.transactions().create(request)
                    .onErrorResume(error -> {
                        log.error("Transaction failed: {}", request.getReference(), error);
                        return Mono.empty();
                    })
            )
            .collectList()
            .map(transactions -> TransactionResult.builder()
                .successful(transactions.size())
                .total(requests.size())
                .transactions(transactions)
                .build());
    }
}
```

---

## Testing

### Unit Testing

```java
@ExtendWith(MockitoExtension.class)
class CustomerServiceTest {
    
    @Mock
    private SusuDigitalClient susuClient;
    
    @Mock
    private CustomerService customerService;
    
    @InjectMocks
    private CustomerManagementService customerManagementService;
    
    @Test
    void shouldCreateCustomerSuccessfully() throws SusuException {
        // Given
        CreateCustomerDto dto = CreateCustomerDto.builder()
            .firstName("John")
            .lastName("Doe")
            .email("john.doe@example.com")
            .phone("+233XXXXXXXXX")
            .build();
            
        Customer expectedCustomer = Customer.builder()
            .id("CUST001")
            .firstName("John")
            .lastName("Doe")
            .email("john.doe@example.com")
            .phone("+233XXXXXXXXX")
            .status(CustomerStatus.ACTIVE)
            .build();
            
        when(susuClient.customers()).thenReturn(customerService);
        when(customerService.create(any(CreateCustomerRequest.class)))
            .thenReturn(expectedCustomer);
        
        // When
        CustomerDto result = customerManagementService.createCustomer(dto);
        
        // Then
        assertThat(result.getId()).isEqualTo("CUST001");
        assertThat(result.getFirstName()).isEqualTo("John");
        assertThat(result.getLastName()).isEqualTo("Doe");
        
        verify(customerService).create(argThat(request -> 
            request.getFirstName().equals("John") &&
            request.getLastName().equals("Doe")
        ));
    }
}
```### Int
egration Testing

```java
@SpringBootTest
@TestPropertySource(properties = {
    "susu.digital.api-key=sk_test_your_test_api_key",
    "susu.digital.environment=sandbox"
})
class SusuIntegrationTest {
    
    @Autowired
    private SusuDigitalClient susuClient;
    
    @Test
    void shouldCreateAndRetrieveCustomer() throws SusuException {
        // Create customer
        CreateCustomerRequest request = CreateCustomerRequest.builder()
            .firstName("Integration")
            .lastName("Test")
            .phone("+233" + System.currentTimeMillis() % 1000000000L)
            .email("integration.test+" + System.currentTimeMillis() + "@example.com")
            .build();
            
        Customer createdCustomer = susuClient.customers().create(request);
        
        assertThat(createdCustomer.getId()).isNotNull();
        assertThat(createdCustomer.getFirstName()).isEqualTo("Integration");
        
        // Retrieve customer
        Customer retrievedCustomer = susuClient.customers().getById(createdCustomer.getId());
        
        assertThat(retrievedCustomer.getId()).isEqualTo(createdCustomer.getId());
        assertThat(retrievedCustomer.getFirstName()).isEqualTo("Integration");
        assertThat(retrievedCustomer.getLastName()).isEqualTo("Test");
    }
    
    @Test
    void shouldHandleValidationErrors() {
        CreateCustomerRequest invalidRequest = CreateCustomerRequest.builder()
            .firstName("") // Invalid: empty first name
            .lastName("Test")
            .phone("invalid-phone") // Invalid: bad phone format
            .email("invalid-email") // Invalid: bad email format
            .build();
            
        assertThatThrownBy(() -> susuClient.customers().create(invalidRequest))
            .isInstanceOf(SusuValidationException.class)
            .hasMessageContaining("firstName")
            .hasMessageContaining("phone")
            .hasMessageContaining("email");
    }
}
```

### Test Configuration

```java
@TestConfiguration
public class SusuTestConfiguration {
    
    @Bean
    @Primary
    public SusuDigitalClient testSusuClient() {
        SusuConfig config = SusuConfig.builder()
            .apiKey("sk_test_your_test_api_key")
            .environment(Environment.SANDBOX)
            .timeout(Duration.ofSeconds(10))
            .retryAttempts(1) // Faster test execution
            .enableLogging(true)
            .build();
            
        return new SusuDigitalClient(config);
    }
    
    @Bean
    public WebhookVerifier testWebhookVerifier() {
        return new WebhookVerifier("test-webhook-secret");
    }
}
```

---

## Migration Guide

### From Version 0.7.x to 0.8.x

#### Breaking Changes

1. **Package Structure Changes**
```java
// Old (0.7.x)
import app.susudigital.sdk.SusuClient;
import app.susudigital.sdk.CustomerApi;

// New (0.8.x)
import app.susudigital.sdk.SusuDigitalClient;
import app.susudigital.sdk.service.CustomerService;
```

2. **Configuration Changes**
```java
// Old (0.7.x)
SusuClient client = SusuClient.builder()
    .apiKey("your-api-key")
    .sandbox(true)
    .build();

// New (0.8.x)
SusuConfig config = SusuConfig.builder()
    .apiKey("your-api-key")
    .environment(Environment.SANDBOX)
    .build();
    
SusuDigitalClient client = new SusuDigitalClient(config);
```

3. **Method Signature Changes**
```java
// Old (0.7.x)
Customer customer = client.createCustomer(firstName, lastName, phone, email);

// New (0.8.x)
CreateCustomerRequest request = CreateCustomerRequest.builder()
    .firstName(firstName)
    .lastName(lastName)
    .phone(phone)
    .email(email)
    .build();
    
Customer customer = client.customers().create(request);
```

#### Migration Steps

1. Update dependency version in `pom.xml` or `build.gradle`
2. Update import statements
3. Replace `SusuClient` with `SusuDigitalClient`
4. Update configuration code
5. Replace direct method calls with service-based calls
6. Update exception handling for new exception hierarchy
7. Run tests to ensure compatibility

---

## Support

### Documentation and Resources

- **API Documentation**: [developers.susudigital.app/java](https://developers.susudigital.app/java)
- **JavaDoc**: [javadoc.susudigital.app](https://javadoc.susudigital.app)
- **GitHub Repository**: [github.com/susudigital/java-sdk](https://github.com/susudigital/java-sdk)
- **Sample Applications**: [github.com/susudigital/java-examples](https://github.com/susudigital/java-examples)

### Getting Help

- **Technical Support**: [java-sdk-support@susudigital.app](mailto:java-sdk-support@susudigital.app)
- **Community Forum**: [community.susudigital.app](https://community.susudigital.app)
- **Stack Overflow**: Tag questions with `susu-digital-java`
- **Discord**: Join our developer community

### Contributing

We welcome contributions to the Java SDK! Please see our [Contributing Guide](https://github.com/susudigital/java-sdk/blob/main/CONTRIBUTING.md) for details.

---

**© 2026 Susu Digital. All rights reserved.**

*Last Updated: April 10, 2026*  
*Java SDK Version: 0.8.5*  
*Documentation Version: 1.0.0*