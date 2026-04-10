# Python SDK

> **Enterprise-Grade Python SDK for Django, Flask, and FastAPI Applications**  
> Full type hints support with async/await patterns and comprehensive error handling

---

## Installation

### PyPI
```bash
pip install susudigital-python
```

### Poetry
```bash
poetry add susudigital-python
```

### Conda
```bash
conda install -c conda-forge susudigital-python
```

---

## Quick Start

### Basic Setup

```python
from susudigital import SusuDigitalClient
import os

client = SusuDigitalClient(
    api_key=os.environ['SUSU_API_KEY'],
    environment='production',  # or 'sandbox'
    organization=os.environ.get('SUSU_ORGANIZATION_ID'),
    timeout=30,
    max_retries=3
)
```

### Environment Configuration

```python
# .env file
SUSU_API_KEY=sk_live_your_secret_key_here
SUSU_ORGANIZATION_ID=org_your_organization_id
SUSU_ENVIRONMENT=production
SUSU_WEBHOOK_SECRET=whsec_your_webhook_secret
```

---

## Core Services

### Customer Management

```python
from susudigital.types import CustomerCreate, Address, Identification
from datetime import date

# Create a new customer
customer_data = CustomerCreate(
    first_name='John',
    last_name='Doe',
    phone='+233XXXXXXXXX',
    email='john.doe@example.com',
    date_of_birth=date(1990, 1, 15),
    address=Address(
        street='123 Main Street',
        city='Accra',
        region='Greater Accra',
        country='Ghana'
    ),
    identification=Identification(
        type='national_id',
        number='GHA-123456789-0',
        expiry_date=date(2030, 12, 31)
    )
)

customer = client.customers.create(customer_data)

# Get customer details
customer_details = client.customers.get(customer.id)

# Update customer information
updated_customer = client.customers.update(
    customer.id,
    email='john.newemail@example.com',
    phone='+233YYYYYYYYY'
)

# Get customer balance
balance = client.customers.get_balance(customer.id)

# List customers with pagination
customers = client.customers.list(
    page=1,
    limit=50,
    search='john',
    status='active'
)
```

### Transaction Processing

```python
from susudigital.types import DepositRequest, WithdrawalRequest, TransferRequest
from decimal import Decimal

# Process a deposit
deposit = client.transactions.deposit(
    DepositRequest(
        customer_id='cust_123456789',
        amount=Decimal('100.00'),
        currency='GHS',
        description='Savings deposit',
        reference=f'DEP-{int(time.time())}',
        metadata={
            'branch': 'Accra Main',
            'collector': 'John Collector'
        }
    )
)

# Process a withdrawal
withdrawal = client.transactions.withdraw(
    WithdrawalRequest(
        customer_id='cust_123456789',
        amount=Decimal('50.00'),
        currency='GHS',
        description='Cash withdrawal',
        reference=f'WTH-{int(time.time())}'
    )
)

# Transfer between customers
transfer = client.transactions.transfer(
    TransferRequest(
        from_customer_id='cust_123456789',
        to_customer_id='cust_987654321',
        amount=Decimal('25.00'),
        currency='GHS',
        description='P2P transfer',
        reference=f'TRF-{int(time.time())}'
    )
)

# Get transaction history
transactions = client.transactions.list(
    customer_id='cust_123456789',
    start_date=date(2026, 1, 1),
    end_date=date(2026, 3, 31),
    transaction_type='deposit',
    status='completed'
)

# Get transaction details
transaction = client.transactions.get('txn_123456789')
```

### Loan Management

```python
from susudigital.types import LoanApplicationRequest, Collateral, Guarantor

# Create loan application
loan_application = client.loans.create_application(
    LoanApplicationRequest(
        customer_id='cust_123456789',
        amount=Decimal('5000.00'),
        currency='GHS',
        purpose='business_expansion',
        term=12,  # months
        interest_rate=Decimal('15.0'),  # annual percentage
        collateral=Collateral(
            type='property',
            description='Residential property in Accra',
            value=Decimal('50000.00')
        ),
        guarantors=[
            Guarantor(
                name='Jane Guarantor',
                phone='+233XXXXXXXXX',
                relationship='spouse'
            )
        ]
    )
)

# Approve loan
approved_loan = client.loans.approve(
    loan_application.id,
    approved_amount=Decimal('4500.00'),
    approved_term=12,
    approved_rate=Decimal('14.0'),
    conditions=['Provide additional documentation']
)

# Disburse loan
disbursement = client.loans.disburse(
    approved_loan.id,
    disbursement_method='bank_transfer',
    account_details={
        'bank_code': '030',
        'account_number': '1234567890'
    }
)

# Record loan repayment
repayment = client.loans.record_repayment(
    approved_loan.id,
    amount=Decimal('450.00'),
    payment_date=date(2026, 4, 10),
    payment_method='cash',
    reference=f'REP-{int(time.time())}'
)

# Get loan schedule
schedule = client.loans.get_schedule(approved_loan.id)

# List loans
loans = client.loans.list(
    customer_id='cust_123456789',
    status='active',
    page=1,
    limit=20
)
```

---

## Async Support

### Async Client

```python
import asyncio
from susudigital import AsyncSusuDigitalClient

async def main():
    async_client = AsyncSusuDigitalClient(
        api_key=os.environ['SUSU_API_KEY'],
        environment='production'
    )
    
    # Async operations
    customer = await async_client.customers.create(customer_data)
    balance = await async_client.customers.get_balance(customer.id)
    
    # Concurrent operations
    results = await asyncio.gather(
        async_client.customers.get('cust_123'),
        async_client.transactions.list(customer_id='cust_123'),
        async_client.loans.list(customer_id='cust_123')
    )
    
    customer, transactions, loans = results
    
    await async_client.close()

# Run async code
asyncio.run(main())
```

### Context Manager

```python
async with AsyncSusuDigitalClient(api_key=api_key) as client:
    customer = await client.customers.create(customer_data)
    transactions = await client.transactions.list(customer_id=customer.id)
```

---

## Django Integration

### Settings Configuration

```python
# settings.py
SUSU_DIGITAL = {
    'API_KEY': os.environ.get('SUSU_API_KEY'),
    'ENVIRONMENT': os.environ.get('SUSU_ENVIRONMENT', 'sandbox'),
    'ORGANIZATION': os.environ.get('SUSU_ORGANIZATION_ID'),
    'WEBHOOK_SECRET': os.environ.get('SUSU_WEBHOOK_SECRET'),
    'TIMEOUT': 30,
    'MAX_RETRIES': 3
}

# Add to INSTALLED_APPS
INSTALLED_APPS = [
    # ... other apps
    'susudigital.contrib.django',
]
```

### Django Service Class

```python
# services.py
from django.conf import settings
from susudigital import SusuDigitalClient
from susudigital.contrib.django import DjangoSusuService

class CustomerService(DjangoSusuService):
    def __init__(self):
        super().__init__()
        self.client = SusuDigitalClient(**settings.SUSU_DIGITAL)
    
    def create_customer_from_user(self, user):
        """Create Susu customer from Django user"""
        customer_data = {
            'first_name': user.first_name,
            'last_name': user.last_name,
            'email': user.email,
            'phone': user.profile.phone,
        }
        
        try:
            customer = self.client.customers.create(customer_data)
            # Save customer ID to user profile
            user.profile.susu_customer_id = customer.id
            user.profile.save()
            return customer
        except Exception as e:
            self.log_error('Customer creation failed', e, user_id=user.id)
            raise
```

### Django Views

```python
# views.py
from django.http import JsonResponse
from django.views.decorators.csrf import csrf_exempt
from django.views.decorators.http import require_http_methods
from susudigital.contrib.django import webhook_handler
import json

@csrf_exempt
@require_http_methods(["POST"])
def susu_webhook(request):
    """Handle Susu Digital webhooks"""
    try:
        event = webhook_handler.construct_event(
            request.body,
            request.META.get('HTTP_SUSU_SIGNATURE'),
            settings.SUSU_DIGITAL['WEBHOOK_SECRET']
        )
        
        if event.type == 'transaction.completed':
            handle_transaction_completed(event.data)
        elif event.type == 'loan.approved':
            handle_loan_approved(event.data)
        
        return JsonResponse({'status': 'success'})
    
    except Exception as e:
        return JsonResponse({'error': str(e)}, status=400)

def handle_transaction_completed(data):
    """Handle completed transaction"""
    from .models import Transaction
    
    Transaction.objects.filter(
        susu_transaction_id=data['transaction']['id']
    ).update(status='completed')
```

### Django Models Integration

```python
# models.py
from django.db import models
from susudigital.contrib.django import SusuModelMixin

class Customer(SusuModelMixin, models.Model):
    user = models.OneToOneField('auth.User', on_delete=models.CASCADE)
    susu_customer_id = models.CharField(max_length=100, unique=True)
    phone = models.CharField(max_length=20)
    
    def sync_with_susu(self):
        """Sync local customer with Susu Digital"""
        if self.susu_customer_id:
            susu_customer = self.susu_client.customers.get(self.susu_customer_id)
            # Update local fields from Susu data
            self.phone = susu_customer.phone
            self.save()

class Transaction(models.Model):
    customer = models.ForeignKey(Customer, on_delete=models.CASCADE)
    susu_transaction_id = models.CharField(max_length=100, unique=True)
    amount = models.DecimalField(max_digits=10, decimal_places=2)
    status = models.CharField(max_length=20)
    created_at = models.DateTimeField(auto_now_add=True)
```

---

## Flask Integration

### Flask Application Setup

```python
# app.py
from flask import Flask, request, jsonify
from susudigital import SusuDigitalClient, WebhookHandler
import os

app = Flask(__name__)

# Initialize Susu client
susu_client = SusuDigitalClient(
    api_key=os.environ['SUSU_API_KEY'],
    environment='production'
)

webhook_handler = WebhookHandler(
    secret=os.environ['SUSU_WEBHOOK_SECRET']
)

@app.route('/api/customers', methods=['POST'])
def create_customer():
    try:
        data = request.get_json()
        customer = susu_client.customers.create(data)
        return jsonify(customer.dict()), 201
    except Exception as e:
        return jsonify({'error': str(e)}), 400

@app.route('/webhooks/susu', methods=['POST'])
def handle_webhook():
    try:
        signature = request.headers.get('Susu-Signature')
        event = webhook_handler.construct_event(
            request.data, 
            signature
        )
        
        # Handle different event types
        if event.type == 'transaction.completed':
            handle_transaction_completed(event.data)
        
        return jsonify({'status': 'success'})
    except Exception as e:
        return jsonify({'error': str(e)}), 400

def handle_transaction_completed(data):
    # Process completed transaction
    print(f"Transaction {data['transaction']['id']} completed")
```

---

## FastAPI Integration

### FastAPI Application

```python
# main.py
from fastapi import FastAPI, HTTPException, Header, Depends
from susudigital import SusuDigitalClient, WebhookHandler
from susudigital.types import CustomerCreate, Customer
from typing import Optional
import os

app = FastAPI(title="Susu Digital Integration")

# Dependency injection
def get_susu_client():
    return SusuDigitalClient(
        api_key=os.environ['SUSU_API_KEY'],
        environment='production'
    )

@app.post("/api/customers", response_model=Customer)
async def create_customer(
    customer_data: CustomerCreate,
    client: SusuDigitalClient = Depends(get_susu_client)
):
    try:
        customer = client.customers.create(customer_data)
        return customer
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))

@app.get("/api/customers/{customer_id}/balance")
async def get_customer_balance(
    customer_id: str,
    client: SusuDigitalClient = Depends(get_susu_client)
):
    try:
        balance = client.customers.get_balance(customer_id)
        return balance
    except Exception as e:
        raise HTTPException(status_code=404, detail=str(e))

@app.post("/webhooks/susu")
async def handle_webhook(
    request: Request,
    susu_signature: Optional[str] = Header(None)
):
    webhook_handler = WebhookHandler(
        secret=os.environ['SUSU_WEBHOOK_SECRET']
    )
    
    try:
        body = await request.body()
        event = webhook_handler.construct_event(body, susu_signature)
        
        # Process event
        await process_webhook_event(event)
        
        return {"status": "success"}
    except Exception as e:
        raise HTTPException(status_code=400, detail=str(e))
```

---

## Error Handling

### Exception Types

```python
from susudigital.exceptions import (
    SusuDigitalError,
    ValidationError,
    AuthenticationError,
    RateLimitError,
    NetworkError,
    NotFoundError
)

try:
    customer = client.customers.create(customer_data)
except ValidationError as e:
    print(f"Validation failed: {e.details}")
    # Handle validation errors
except AuthenticationError as e:
    print(f"Authentication failed: {e.message}")
    # Handle auth errors
except RateLimitError as e:
    print(f"Rate limit exceeded. Retry after: {e.retry_after} seconds")
    # Wait and retry
    time.sleep(e.retry_after)
except NetworkError as e:
    print(f"Network error: {e.message}")
    # Handle network issues
except NotFoundError as e:
    print(f"Resource not found: {e.message}")
    # Handle not found errors
except SusuDigitalError as e:
    print(f"Susu Digital error: {e.message}")
    # Handle general Susu errors
```

### Custom Error Handler

```python
import logging
from functools import wraps

def handle_susu_errors(func):
    @wraps(func)
    def wrapper(*args, **kwargs):
        try:
            return func(*args, **kwargs)
        except SusuDigitalError as e:
            logging.error(f"Susu Digital error in {func.__name__}: {e}")
            # Custom error handling logic
            raise
        except Exception as e:
            logging.error(f"Unexpected error in {func.__name__}: {e}")
            raise
    return wrapper

@handle_susu_errors
def create_customer_safely(customer_data):
    return client.customers.create(customer_data)
```

---

## Type Hints and Validation

### Pydantic Models

```python
from pydantic import BaseModel, validator
from typing import Optional, List
from decimal import Decimal
from datetime import date

class CustomerCreate(BaseModel):
    first_name: str
    last_name: str
    phone: str
    email: Optional[str] = None
    date_of_birth: Optional[date] = None
    
    @validator('phone')
    def validate_phone(cls, v):
        if not v.startswith('+233'):
            raise ValueError('Phone must start with +233')
        return v

class TransactionCreate(BaseModel):
    customer_id: str
    amount: Decimal
    currency: str = 'GHS'
    description: Optional[str] = None
    
    @validator('amount')
    def validate_amount(cls, v):
        if v <= 0:
            raise ValueError('Amount must be positive')
        return v

# Usage with type hints
def create_customer_typed(data: CustomerCreate) -> Customer:
    return client.customers.create(data)
```

### Type Checking with mypy

```python
# mypy configuration in setup.cfg
[mypy]
python_version = 3.8
warn_return_any = True
warn_unused_configs = True
disallow_untyped_defs = True

[mypy-susudigital.*]
ignore_missing_imports = False
```

---

## Testing

### Unit Testing with pytest

```python
# test_customer_service.py
import pytest
from unittest.mock import Mock, patch
from susudigital import SusuDigitalClient
from susudigital.types import Customer

@pytest.fixture
def mock_client():
    return Mock(spec=SusuDigitalClient)

@pytest.fixture
def customer_data():
    return {
        'first_name': 'John',
        'last_name': 'Doe',
        'phone': '+233XXXXXXXXX',
        'email': 'john@example.com'
    }

def test_create_customer_success(mock_client, customer_data):
    # Arrange
    expected_customer = Customer(
        id='cust_123',
        first_name='John',
        last_name='Doe',
        phone='+233XXXXXXXXX'
    )
    mock_client.customers.create.return_value = expected_customer
    
    # Act
    result = mock_client.customers.create(customer_data)
    
    # Assert
    assert result.id == 'cust_123'
    assert result.first_name == 'John'
    mock_client.customers.create.assert_called_once_with(customer_data)

@patch('susudigital.SusuDigitalClient')
def test_customer_service_integration(mock_client_class):
    # Integration test with mocked client
    mock_client = Mock()
    mock_client_class.return_value = mock_client
    
    service = CustomerService()
    result = service.create_customer({'first_name': 'John'})
    
    mock_client.customers.create.assert_called_once()
```

### Integration Testing

```python
# test_integration.py
import pytest
import os
from susudigital import SusuDigitalClient

@pytest.fixture
def client():
    return SusuDigitalClient(
        api_key=os.environ['SUSU_TEST_API_KEY'],
        environment='sandbox'
    )

@pytest.mark.integration
def test_customer_lifecycle(client):
    # Create customer
    customer_data = {
        'first_name': 'Test',
        'last_name': 'Customer',
        'phone': '+233XXXXXXXXX',
        'email': 'test@example.com'
    }
    
    customer = client.customers.create(customer_data)
    assert customer.id is not None
    
    # Retrieve customer
    retrieved = client.customers.get(customer.id)
    assert retrieved.first_name == customer_data['first_name']
    
    # Update customer
    updated = client.customers.update(
        customer.id,
        email='updated@example.com'
    )
    assert updated.email == 'updated@example.com'
    
    # Cleanup
    client.customers.delete(customer.id)
```

---

## Performance Optimization

### Connection Pooling

```python
from susudigital import SusuDigitalClient
import requests

# Custom session with connection pooling
session = requests.Session()
adapter = requests.adapters.HTTPAdapter(
    pool_connections=20,
    pool_maxsize=20,
    max_retries=3
)
session.mount('https://', adapter)

client = SusuDigitalClient(
    api_key=os.environ['SUSU_API_KEY'],
    session=session
)
```

### Caching with Redis

```python
import redis
from susudigital import SusuDigitalClient
from susudigital.cache import RedisCache

# Redis cache setup
redis_client = redis.Redis(host='localhost', port=6379, db=0)
cache = RedisCache(redis_client, default_ttl=300)

client = SusuDigitalClient(
    api_key=os.environ['SUSU_API_KEY'],
    cache=cache
)

# Cached operations
customer = client.customers.get('cust_123', cache=True, ttl=600)
```

### Batch Operations

```python
from susudigital.batch import BatchProcessor

# Process operations in batches
batch_processor = BatchProcessor(client, batch_size=100)

# Batch customer creation
customers_data = [
    {'first_name': 'John', 'last_name': 'Doe', 'phone': '+233XXXXXXXXX'},
    {'first_name': 'Jane', 'last_name': 'Smith', 'phone': '+233YYYYYYYYY'},
    # ... more customers
]

results = batch_processor.customers.create_batch(customers_data)

# Check batch status
for result in results:
    if result.success:
        print(f"Created customer: {result.data.id}")
    else:
        print(f"Failed to create customer: {result.error}")
```

---

## Logging and Monitoring

### Structured Logging

```python
import logging
import structlog
from susudigital import SusuDigitalClient

# Configure structured logging
structlog.configure(
    processors=[
        structlog.stdlib.filter_by_level,
        structlog.stdlib.add_logger_name,
        structlog.stdlib.add_log_level,
        structlog.stdlib.PositionalArgumentsFormatter(),
        structlog.processors.TimeStamper(fmt="iso"),
        structlog.processors.StackInfoRenderer(),
        structlog.processors.format_exc_info,
        structlog.processors.UnicodeDecoder(),
        structlog.processors.JSONRenderer()
    ],
    context_class=dict,
    logger_factory=structlog.stdlib.LoggerFactory(),
    wrapper_class=structlog.stdlib.BoundLogger,
    cache_logger_on_first_use=True,
)

logger = structlog.get_logger()

# Client with logging
client = SusuDigitalClient(
    api_key=os.environ['SUSU_API_KEY'],
    enable_logging=True,
    logger=logger
)

# Operations will be automatically logged
customer = client.customers.create(customer_data)
```

### Metrics Collection

```python
from prometheus_client import Counter, Histogram, start_http_server
import time

# Metrics
api_requests_total = Counter('susu_api_requests_total', 'Total API requests', ['method', 'endpoint'])
api_request_duration = Histogram('susu_api_request_duration_seconds', 'API request duration')

class MetricsMiddleware:
    def __init__(self, client):
        self.client = client
    
    def __getattr__(self, name):
        attr = getattr(self.client, name)
        if hasattr(attr, '__call__'):
            return self._wrap_method(attr, name)
        return attr
    
    def _wrap_method(self, method, name):
        def wrapper(*args, **kwargs):
            start_time = time.time()
            try:
                result = method(*args, **kwargs)
                api_requests_total.labels(method='POST', endpoint=name).inc()
                return result
            finally:
                api_request_duration.observe(time.time() - start_time)
        return wrapper

# Usage
client = SusuDigitalClient(api_key=os.environ['SUSU_API_KEY'])
monitored_client = MetricsMiddleware(client)

# Start metrics server
start_http_server(8000)
```

---

## Best Practices

### 1. **Configuration Management**

```python
# config.py
from pydantic import BaseSettings

class SusuConfig(BaseSettings):
    api_key: str
    environment: str = 'sandbox'
    organization: str = None
    timeout: int = 30
    max_retries: int = 3
    enable_logging: bool = False
    
    class Config:
        env_prefix = 'SUSU_'
        env_file = '.env'

# Usage
config = SusuConfig()
client = SusuDigitalClient(**config.dict())
```

### 2. **Service Layer Pattern**

```python
# services/customer_service.py
from abc import ABC, abstractmethod
from typing import List, Optional

class CustomerServiceInterface(ABC):
    @abstractmethod
    def create_customer(self, data: dict) -> Customer:
        pass
    
    @abstractmethod
    def get_customer(self, customer_id: str) -> Optional[Customer]:
        pass

class SusuCustomerService(CustomerServiceInterface):
    def __init__(self, client: SusuDigitalClient):
        self.client = client
    
    def create_customer(self, data: dict) -> Customer:
        try:
            return self.client.customers.create(data)
        except Exception as e:
            logger.error("Failed to create customer", error=str(e), data=data)
            raise
    
    def get_customer(self, customer_id: str) -> Optional[Customer]:
        try:
            return self.client.customers.get(customer_id)
        except NotFoundError:
            return None
        except Exception as e:
            logger.error("Failed to get customer", error=str(e), customer_id=customer_id)
            raise
```

### 3. **Retry Strategy**

```python
import time
import random
from functools import wraps

def retry_with_backoff(max_retries=3, base_delay=1, max_delay=60):
    def decorator(func):
        @wraps(func)
        def wrapper(*args, **kwargs):
            for attempt in range(max_retries):
                try:
                    return func(*args, **kwargs)
                except (NetworkError, RateLimitError) as e:
                    if attempt == max_retries - 1:
                        raise
                    
                    delay = min(base_delay * (2 ** attempt) + random.uniform(0, 1), max_delay)
                    logger.warning(f"Attempt {attempt + 1} failed, retrying in {delay:.2f}s", error=str(e))
                    time.sleep(delay)
            
            return func(*args, **kwargs)
        return wrapper
    return decorator

@retry_with_backoff(max_retries=3)
def create_customer_with_retry(data):
    return client.customers.create(data)
```

---

## Support

### Getting Help

- **Documentation**: [developers.susudigital.app/python-sdk](https://developers.susudigital.app/python-sdk)
- **PyPI Package**: [pypi.org/project/susudigital-python](https://pypi.org/project/susudigital-python)
- **GitHub Issues**: [github.com/susudigital/python-sdk/issues](https://github.com/susudigital/python-sdk/issues)
- **Email Support**: [python-sdk@susudigital.app](mailto:python-sdk@susudigital.app)

### Contributing

We welcome contributions! Please see our [Contributing Guide](https://github.com/susudigital/python-sdk/blob/main/CONTRIBUTING.md) for details.

---

**© 2026 Susu Digital. All rights reserved.**

*Last Updated: April 10, 2026*  
*SDK Version: 2.0.5*