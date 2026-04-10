"""Unit tests for Pydantic types / models."""

from __future__ import annotations

import pytest
from decimal import Decimal
from datetime import date

from susudigital.types import (
    Address,
    Balance,
    CollateralType,
    Customer,
    CustomerCreate,
    CustomerStatus,
    CustomerUpdate,
    DepositRequest,
    Identification,
    Loan,
    LoanApplicationRequest,
    LoanStatus,
    PagedResult,
    SavingsAccountCreate,
    SavingsAccountType,
    Transaction,
    TransactionStatus,
    TransactionType,
    TransferRequest,
    WebhookEvent,
    WithdrawalRequest,
)


class TestCustomerCreate:
    def test_valid(self):
        c = CustomerCreate(first_name="Jane", last_name="Smith", phone="+233244000001")
        assert c.first_name == "Jane"
        assert c.phone == "+233244000001"

    def test_phone_must_start_with_plus(self):
        with pytest.raises(Exception):
            CustomerCreate(first_name="X", last_name="Y", phone="0244000001")

    def test_to_dict_excludes_none(self):
        c = CustomerCreate(first_name="A", last_name="B", phone="+233244000001")
        d = c.to_dict()
        assert "email" not in d
        assert "date_of_birth" not in d

    def test_with_address(self):
        addr = Address(street="1 Main St", city="Accra", region="GA", country="Ghana")
        c = CustomerCreate(
            first_name="J", last_name="D", phone="+233244000001", address=addr
        )
        assert c.address.city == "Accra"

    def test_with_identification(self):
        ident = Identification(
            type="national_id",
            number="GHA-123",
            expiry_date=date(2030, 12, 31),
        )
        c = CustomerCreate(
            first_name="J", last_name="D", phone="+233244000001", identification=ident
        )
        assert c.identification.number == "GHA-123"


class TestCustomer:
    def test_full_name(self):
        c = Customer(
            id="cust_1",
            first_name="John",
            last_name="Doe",
            phone="+233244000001",
            status=CustomerStatus.ACTIVE,
        )
        assert c.full_name == "John Doe"

    def test_default_status(self):
        c = Customer(id="c1", first_name="X", last_name="Y", phone="+233244000001")
        assert c.status == CustomerStatus.ACTIVE


class TestDepositRequest:
    def test_valid(self):
        req = DepositRequest(
            customer_id="cust_1",
            amount=Decimal("100.00"),
            currency="GHS",
        )
        assert req.amount == Decimal("100.00")

    def test_amount_must_be_positive(self):
        with pytest.raises(Exception):
            DepositRequest(customer_id="c", amount=Decimal("-1.00"))

    def test_zero_amount_rejected(self):
        with pytest.raises(Exception):
            DepositRequest(customer_id="c", amount=Decimal("0.00"))


class TestTransferRequest:
    def test_cannot_transfer_to_self(self):
        with pytest.raises(Exception):
            TransferRequest(
                from_customer_id="cust_1",
                to_customer_id="cust_1",
                amount=Decimal("10.00"),
            )

    def test_valid_transfer(self):
        req = TransferRequest(
            from_customer_id="cust_1",
            to_customer_id="cust_2",
            amount=Decimal("10.00"),
        )
        assert req.from_customer_id != req.to_customer_id


class TestPagedResult:
    def test_total_pages(self):
        pr = PagedResult(total=105, limit=50, page=1)
        assert pr.total_pages == 3

    def test_total_pages_zero_limit(self):
        pr = PagedResult(total=10, limit=0)
        assert pr.total_pages == 0


class TestWebhookEvent:
    def test_parse(self):
        from datetime import datetime, timezone

        evt = WebhookEvent(
            id="evt_001",
            type="transaction.completed",
            created_at=datetime.now(tz=timezone.utc),
            data={"transaction": {"id": "txn_1"}},
        )
        assert evt.type == "transaction.completed"
        assert evt.data["transaction"]["id"] == "txn_1"
