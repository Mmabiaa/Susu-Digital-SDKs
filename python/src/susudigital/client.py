"""
Synchronous top-level client for the Susu Digital Python SDK.

Usage::

    from susudigital import SusuDigitalClient

    client = SusuDigitalClient(
        api_key="sk_live_...",
        environment="production",
        organization="org_...",
        timeout=30,
        max_retries=3,
    )

    # Access services as attributes
    customer = client.customers.create(...)
    txn      = client.transactions.deposit(...)
    loan     = client.loans.create_application(...)

    # Close when done (also supports context manager)
    client.close()
"""

from __future__ import annotations

from typing import Any, Dict, Optional

import httpx

from susudigital._http import HttpClient
from susudigital.services import (
    AnalyticsService,
    CustomerService,
    LoanService,
    SavingsService,
    TransactionService,
)


class SusuDigitalClient:
    """The primary entry-point for the synchronous Susu Digital Python SDK.

    Args:
        api_key: Your Susu Digital API key (``sk_live_...`` or ``sk_test_...``).
        environment: ``"production"`` or ``"sandbox"`` (default: ``"sandbox"``).
        organization: Optional organization ID to scope requests.
        timeout: HTTP timeout in seconds (default: ``30``).
        max_retries: Maximum number of automatic retries (default: ``3``).
        enable_logging: Enable structured SDK-level logging (default: ``False``).
        custom_headers: Additional HTTP headers to include on every request.
        session: Bring-your-own ``httpx.Client`` (for connection pooling).
    """

    def __init__(
        self,
        api_key: str,
        *,
        environment: str = "sandbox",
        organization: Optional[str] = None,
        timeout: float = 30.0,
        max_retries: int = 3,
        enable_logging: bool = False,
        custom_headers: Optional[Dict[str, str]] = None,
        session: Optional[httpx.Client] = None,
    ) -> None:
        self._http = HttpClient(
            api_key=api_key,
            environment=environment,
            organization=organization,
            timeout=timeout,
            max_retries=max_retries,
            custom_headers=custom_headers,
            http_client=session,
            enable_logging=enable_logging,
        )

        # Initialise service singletons
        self._customers = CustomerService(self._http)
        self._transactions = TransactionService(self._http)
        self._loans = LoanService(self._http)
        self._savings = SavingsService(self._http)
        self._analytics = AnalyticsService(self._http)

    # ------------------------------------------------------------------
    # Service accessors (lazy-style properties for clarity)
    # ------------------------------------------------------------------

    @property
    def customers(self) -> CustomerService:
        """Customer management service."""
        return self._customers

    @property
    def transactions(self) -> TransactionService:
        """Transaction processing service."""
        return self._transactions

    @property
    def loans(self) -> LoanService:
        """Loan origination and servicing."""
        return self._loans

    @property
    def savings(self) -> SavingsService:
        """Savings account management."""
        return self._savings

    @property
    def analytics(self) -> AnalyticsService:
        """Analytics and reporting service."""
        return self._analytics

    # ------------------------------------------------------------------
    # Lifecycle
    # ------------------------------------------------------------------

    def close(self) -> None:
        """Close the underlying HTTP session and release resources."""
        self._http.close()

    def __enter__(self) -> "SusuDigitalClient":
        return self

    def __exit__(self, *args: Any) -> None:
        self.close()

    def __repr__(self) -> str:
        return f"SusuDigitalClient(environment={self._http._client.base_url!r})"
