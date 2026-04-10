"""Tests for the AsyncSusuDigitalClient and async services."""

from __future__ import annotations

import pytest
import pytest_asyncio
from decimal import Decimal
from unittest.mock import AsyncMock, MagicMock, patch

from susudigital import AsyncSusuDigitalClient
from susudigital.async_services import (
    AsyncAnalyticsService,
    AsyncCustomerService,
    AsyncLoanService,
    AsyncSavingsService,
    AsyncTransactionService,
)
from susudigital.types import (
    Customer,
    CustomerCreate,
    DepositRequest,
    Transaction,
    TransactionType,
)


class TestAsyncSusuDigitalClient:
    def test_service_properties(self):
        client = AsyncSusuDigitalClient(api_key="sk_test_xxx")
        assert isinstance(client.customers, AsyncCustomerService)
        assert isinstance(client.transactions, AsyncTransactionService)
        assert isinstance(client.loans, AsyncLoanService)
        assert isinstance(client.savings, AsyncSavingsService)
        assert isinstance(client.analytics, AsyncAnalyticsService)

    @pytest.mark.asyncio
    async def test_context_manager(self):
        async with AsyncSusuDigitalClient(api_key="sk_test_xxx") as client:
            assert isinstance(client.customers, AsyncCustomerService)


class TestAsyncCustomerService:
    @pytest.mark.asyncio
    async def test_create(self):
        http = MagicMock()
        http.post = AsyncMock(return_value={
            "id": "cust_1", "first_name": "John", "last_name": "Doe",
            "phone": "+233244000001", "status": "active",
        })

        from susudigital.async_services import AsyncCustomerService
        svc = AsyncCustomerService(http)
        customer = await svc.create(
            CustomerCreate(first_name="John", last_name="Doe", phone="+233244000001")
        )
        assert isinstance(customer, Customer)
        assert customer.id == "cust_1"

    @pytest.mark.asyncio
    async def test_get(self):
        http = MagicMock()
        http.get = AsyncMock(return_value={
            "id": "cust_1", "first_name": "John", "last_name": "Doe",
            "phone": "+233244000001", "status": "active",
        })
        from susudigital.async_services import AsyncCustomerService
        svc = AsyncCustomerService(http)
        customer = await svc.get("cust_1")
        assert customer.id == "cust_1"

    @pytest.mark.asyncio
    async def test_list(self):
        http = MagicMock()
        http.get = AsyncMock(return_value={
            "data": [], "total": 0, "page": 1, "limit": 50,
        })
        from susudigital.async_services import AsyncCustomerService
        svc = AsyncCustomerService(http)
        result = await svc.list()
        assert result.total == 0


class TestAsyncTransactionService:
    @pytest.mark.asyncio
    async def test_deposit(self):
        http = MagicMock()
        http.post = AsyncMock(return_value={
            "id": "txn_1", "customer_id": "cust_1",
            "type": "deposit", "amount": "100.00", "currency": "GHS",
            "status": "completed",
        })
        from susudigital.async_services import AsyncTransactionService
        svc = AsyncTransactionService(http)
        txn = await svc.deposit(DepositRequest(customer_id="cust_1", amount=Decimal("100.00")))
        assert isinstance(txn, Transaction)
        assert txn.type == TransactionType.DEPOSIT
