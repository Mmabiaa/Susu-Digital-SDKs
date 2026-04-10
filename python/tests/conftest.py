"""Shared pytest fixtures and test configuration."""

from __future__ import annotations

import json
import pytest
import respx
import httpx
from decimal import Decimal
from datetime import datetime, timezone
from unittest.mock import MagicMock

from susudigital import SusuDigitalClient, AsyncSusuDigitalClient


# ---------------------------------------------------------------------------
# Constants
# ---------------------------------------------------------------------------

SANDBOX_BASE = "https://api-sandbox.susudigital.app/v1"
TEST_API_KEY  = "sk_test_fixture_key"


# ---------------------------------------------------------------------------
# HTTP mock helpers
# ---------------------------------------------------------------------------

@pytest.fixture
def mock_transport():
    """Provide a respx MockTransport and a pre-configured sync client."""
    with respx.mock(base_url=SANDBOX_BASE, assert_all_called=False) as mock:
        yield mock


@pytest.fixture
def sdk_client(mock_transport):
    """Return a SusuDigitalClient backed by respx."""
    http = httpx.Client(
        base_url=SANDBOX_BASE,
        transport=respx.MockTransport(assert_all_called=False),
    )
    client = SusuDigitalClient(api_key=TEST_API_KEY, session=http)
    yield client
    client.close()


# ---------------------------------------------------------------------------
# Payload factories
# ---------------------------------------------------------------------------

def make_customer_payload(**overrides) -> dict:
    base = {
        "id": "cust_abc123",
        "first_name": "John",
        "last_name": "Doe",
        "phone": "+233244000001",
        "email": "john.doe@example.com",
        "status": "active",
        "created_at": "2026-04-01T00:00:00Z",
        "updated_at": "2026-04-01T00:00:00Z",
    }
    base.update(overrides)
    return base


def make_transaction_payload(**overrides) -> dict:
    base = {
        "id": "txn_abc123",
        "customer_id": "cust_abc123",
        "type": "deposit",
        "amount": "100.00",
        "currency": "GHS",
        "status": "completed",
        "reference": "DEP-001",
        "created_at": "2026-04-01T00:00:00Z",
    }
    base.update(overrides)
    return base


def make_loan_payload(**overrides) -> dict:
    base = {
        "id": "loan_abc123",
        "customer_id": "cust_abc123",
        "amount": "5000.00",
        "currency": "GHS",
        "term": 12,
        "interest_rate": "15.0",
        "purpose": "business_expansion",
        "status": "pending",
        "created_at": "2026-04-01T00:00:00Z",
    }
    base.update(overrides)
    return base


def make_savings_account_payload(**overrides) -> dict:
    base = {
        "id": "sacc_abc123",
        "customer_id": "cust_abc123",
        "account_type": "regular",
        "currency": "GHS",
        "minimum_balance": "10.00",
        "balance": "0.00",
        "status": "active",
        "created_at": "2026-04-01T00:00:00Z",
    }
    base.update(overrides)
    return base
