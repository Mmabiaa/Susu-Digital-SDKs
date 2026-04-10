"""
Unit tests for all synchronous service classes.

Uses unittest.mock to stub the HttpClient so no real HTTP calls are made.
"""

from __future__ import annotations

import pytest
from decimal import Decimal
from datetime import date
from unittest.mock import MagicMock, call

from susudigital.services import (
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
)
from susudigital.types import (
    Balance,
    Customer,
    CustomerCreate,
    CustomerStatus,
    DepositRequest,
    Loan,
    LoanApplicationRequest,
    PagedResult,
    SavingsAccount,
    SavingsAccountCreate,
    Transaction,
    TransactionStatus,
    TransactionType,
    TransferRequest,
    WithdrawalRequest,
)


# ---------------------------------------------------------------------------
# CustomerService
# ---------------------------------------------------------------------------

class TestCustomerService:
    @pytest.fixture
    def http(self):
        return MagicMock()

    @pytest.fixture
    def svc(self, http):
        return CustomerService(http)

    def test_create_with_model(self, svc, http):
        http.post.return_value = {
            "id": "cust_1", "first_name": "John", "last_name": "Doe",
            "phone": "+233244000001", "status": "active",
        }
        data = CustomerCreate(first_name="John", last_name="Doe", phone="+233244000001")
        customer = svc.create(data)
        assert isinstance(customer, Customer)
        assert customer.id == "cust_1"
        http.post.assert_called_once()

    def test_create_with_dict(self, svc, http):
        http.post.return_value = {
            "id": "cust_2", "first_name": "Jane", "last_name": "Smith",
            "phone": "+233244000002", "status": "active",
        }
        customer = svc.create(
            {"first_name": "Jane", "last_name": "Smith", "phone": "+233244000002"}
        )
        assert customer.first_name == "Jane"

    def test_get(self, svc, http):
        http.get.return_value = {
            "id": "cust_1", "first_name": "John", "last_name": "Doe",
            "phone": "+233244000001", "status": "active",
        }
        customer = svc.get("cust_1")
        assert customer.id == "cust_1"
        http.get.assert_called_once_with("/customers/cust_1")

    def test_update_with_kwargs(self, svc, http):
        http.patch.return_value = {
            "id": "cust_1", "first_name": "John", "last_name": "Doe",
            "phone": "+233244000001", "email": "new@example.com", "status": "active",
        }
        updated = svc.update("cust_1", email="new@example.com")
        assert updated.email == "new@example.com"

    def test_delete(self, svc, http):
        http.delete.return_value = {}
        svc.delete("cust_1")
        http.delete.assert_called_once_with("/customers/cust_1")

    def test_get_balance(self, svc, http):
        http.get.return_value = {
            "customer_id": "cust_1",
            "currency": "GHS",
            "available": "250.00",
            "ledger": "250.00",
        }
        balance = svc.get_balance("cust_1")
        assert isinstance(balance, Balance)
        assert balance.currency == "GHS"

    def test_list_default_params(self, svc, http):
        http.get.return_value = {
            "data": [
                {"id": "cust_1", "first_name": "A", "last_name": "B",
                 "phone": "+233244000001", "status": "active"},
            ],
            "total": 1, "page": 1, "limit": 50,
        }
        result = svc.list()
        assert isinstance(result, PagedResult)
        assert len(result.data) == 1
        http.get.assert_called_once_with("/customers", params={"page": 1, "limit": 50})

    def test_list_with_filters(self, svc, http):
        http.get.return_value = {"data": [], "total": 0, "page": 1, "limit": 50}
        svc.list(search="jo", status=CustomerStatus.ACTIVE)
        args = http.get.call_args
        assert args[1]["params"]["search"] == "jo"
        assert args[1]["params"]["status"] == "active"


# ---------------------------------------------------------------------------
# TransactionService
# ---------------------------------------------------------------------------

class TestTransactionService:
    @pytest.fixture
    def http(self):
        return MagicMock()

    @pytest.fixture
    def svc(self, http):
        return TransactionService(http)

    def _txn_payload(self, **overrides):
        base = {
            "id": "txn_1", "customer_id": "cust_1",
            "type": "deposit", "amount": "100.00", "currency": "GHS",
            "status": "completed",
        }
        base.update(overrides)
        return base

    def test_deposit(self, svc, http):
        http.post.return_value = self._txn_payload(type="deposit")
        req = DepositRequest(customer_id="cust_1", amount=Decimal("100.00"))
        txn = svc.deposit(req)
        assert txn.type == TransactionType.DEPOSIT
        assert txn.amount == Decimal("100.00")

    def test_withdraw(self, svc, http):
        http.post.return_value = self._txn_payload(type="withdrawal")
        req = WithdrawalRequest(customer_id="cust_1", amount=Decimal("50.00"))
        txn = svc.withdraw(req)
        assert txn.type == TransactionType.WITHDRAWAL

    def test_transfer(self, svc, http):
        http.post.return_value = self._txn_payload(type="transfer")
        req = TransferRequest(
            from_customer_id="cust_1",
            to_customer_id="cust_2",
            amount=Decimal("25.00"),
        )
        txn = svc.transfer(req)
        assert txn.type == TransactionType.TRANSFER
        http.post.assert_called_once()
        call_path = http.post.call_args[0][0]
        assert "/transfer" in call_path

    def test_get(self, svc, http):
        http.get.return_value = self._txn_payload()
        txn = svc.get("txn_1")
        assert txn.id == "txn_1"

    def test_list_with_filters(self, svc, http):
        http.get.return_value = {"data": [], "total": 0, "page": 1, "limit": 50}
        svc.list(
            customer_id="cust_1",
            transaction_type=TransactionType.DEPOSIT,
            status=TransactionStatus.COMPLETED,
        )
        params = http.get.call_args[1]["params"]
        assert params["customer_id"] == "cust_1"
        assert params["type"] == "deposit"
        assert params["status"] == "completed"


# ---------------------------------------------------------------------------
# LoanService
# ---------------------------------------------------------------------------

class TestLoanService:
    @pytest.fixture
    def http(self):
        return MagicMock()

    @pytest.fixture
    def svc(self, http):
        return LoanService(http)

    def _loan_payload(self, **overrides):
        base = {
            "id": "loan_1", "customer_id": "cust_1",
            "amount": "5000.00", "currency": "GHS", "term": 12,
            "interest_rate": "15.0", "purpose": "business",
            "status": "pending",
        }
        base.update(overrides)
        return base

    def test_create_application(self, svc, http):
        http.post.return_value = self._loan_payload()
        req = LoanApplicationRequest(
            customer_id="cust_1",
            amount=Decimal("5000.00"),
            term=12,
            interest_rate=Decimal("15.0"),
            purpose="business",
        )
        loan = svc.create_application(req)
        assert isinstance(loan, Loan)
        assert loan.status == "pending"

    def test_get_schedule(self, svc, http):
        http.get.return_value = {
            "data": [
                {
                    "installment_number": 1,
                    "due_date": "2026-05-01",
                    "principal": "400.00",
                    "interest": "50.00",
                    "total": "450.00",
                    "outstanding_balance": "4600.00",
                    "status": "pending",
                }
            ]
        }
        items = svc.get_schedule("loan_1")
        assert len(items) == 1
        assert items[0].installment_number == 1


# ---------------------------------------------------------------------------
# SavingsService
# ---------------------------------------------------------------------------

class TestSavingsService:
    @pytest.fixture
    def http(self):
        return MagicMock()

    @pytest.fixture
    def svc(self, http):
        return SavingsService(http)

    def test_create_account(self, svc, http):
        http.post.return_value = {
            "id": "sacc_1", "customer_id": "cust_1",
            "account_type": "regular", "currency": "GHS",
            "minimum_balance": "10.00", "balance": "0.00", "status": "active",
        }
        account = svc.create_account(
            SavingsAccountCreate(customer_id="cust_1")
        )
        assert isinstance(account, SavingsAccount)
        assert account.id == "sacc_1"
