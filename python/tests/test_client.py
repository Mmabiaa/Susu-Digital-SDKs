"""Tests for the SusuDigitalClient facade."""

from __future__ import annotations

import pytest
from unittest.mock import MagicMock, patch

from susudigital import SusuDigitalClient
from susudigital.services import (
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
)


class TestSusuDigitalClient:
    def test_service_properties(self):
        client = SusuDigitalClient(api_key="sk_test_xxx", environment="sandbox")
        assert isinstance(client.customers, CustomerService)
        assert isinstance(client.transactions, TransactionService)
        assert isinstance(client.loans, LoanService)
        assert isinstance(client.savings, SavingsService)
        assert isinstance(client.analytics, AnalyticsService)
        client.close()

    def test_context_manager(self):
        with SusuDigitalClient(api_key="sk_test_xxx") as client:
            assert isinstance(client.customers, CustomerService)

    def test_repr(self):
        client = SusuDigitalClient(api_key="sk_test_xxx", environment="sandbox")
        r = repr(client)
        assert "SusuDigitalClient" in r
        client.close()

    def test_custom_headers_passed_through(self):
        """Ensure custom headers are forwarded to the HTTP client."""
        with patch("susudigital.client.HttpClient") as mock_http_cls:
            mock_http_cls.return_value = MagicMock()
            SusuDigitalClient(
                api_key="sk_test_xxx",
                custom_headers={"X-Custom": "value"},
            )
            kwargs = mock_http_cls.call_args[1]
            assert kwargs["custom_headers"] == {"X-Custom": "value"}
