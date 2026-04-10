"""
Asynchronous top-level client for the Susu Digital Python SDK.

Usage::

    import asyncio
    from susudigital import AsyncSusuDigitalClient

    async def main():
        async with AsyncSusuDigitalClient(
            api_key="sk_live_...",
            environment="production",
        ) as client:
            customer = await client.customers.create(...)

            # Concurrent operations
            results = await asyncio.gather(
                client.customers.get("cust_123"),
                client.transactions.list(customer_id="cust_123"),
                client.loans.list(customer_id="cust_123"),
            )

    asyncio.run(main())
"""

from __future__ import annotations

from typing import Any, Dict, Optional

import httpx

from susudigital._http import AsyncHttpClient
from susudigital.async_services import (
    AsyncAnalyticsService,
    AsyncCustomerService,
    AsyncLoanService,
    AsyncSavingsService,
    AsyncTransactionService,
)


class AsyncSusuDigitalClient:
    """The primary entry-point for the async Susu Digital Python SDK.

    Identical constructor signature to :class:`~susudigital.client.SusuDigitalClient`;
    all service methods return coroutines.

    Args:
        api_key: Your Susu Digital API key.
        environment: ``"production"`` or ``"sandbox"`` (default: ``"sandbox"``).
        organization: Optional organization ID to scope requests.
        timeout: HTTP timeout in seconds (default: ``30``).
        max_retries: Maximum number of automatic retries (default: ``3``).
        enable_logging: Enable SDK-level logging (default: ``False``).
        custom_headers: Additional headers for every request.
        session: Bring-your-own ``httpx.AsyncClient``.
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
        session: Optional[httpx.AsyncClient] = None,
    ) -> None:
        self._http = AsyncHttpClient(
            api_key=api_key,
            environment=environment,
            organization=organization,
            timeout=timeout,
            max_retries=max_retries,
            custom_headers=custom_headers,
            http_client=session,
            enable_logging=enable_logging,
        )

        self._customers = AsyncCustomerService(self._http)
        self._transactions = AsyncTransactionService(self._http)
        self._loans = AsyncLoanService(self._http)
        self._savings = AsyncSavingsService(self._http)
        self._analytics = AsyncAnalyticsService(self._http)

    # ------------------------------------------------------------------
    # Service accessors
    # ------------------------------------------------------------------

    @property
    def customers(self) -> AsyncCustomerService:
        return self._customers

    @property
    def transactions(self) -> AsyncTransactionService:
        return self._transactions

    @property
    def loans(self) -> AsyncLoanService:
        return self._loans

    @property
    def savings(self) -> AsyncSavingsService:
        return self._savings

    @property
    def analytics(self) -> AsyncAnalyticsService:
        return self._analytics

    # ------------------------------------------------------------------
    # Lifecycle
    # ------------------------------------------------------------------

    async def close(self) -> None:
        """Close the underlying async HTTP session."""
        await self._http.close()

    async def __aenter__(self) -> "AsyncSusuDigitalClient":
        return self

    async def __aexit__(self, *args: Any) -> None:
        await self.close()

    def __repr__(self) -> str:
        return f"AsyncSusuDigitalClient(environment={self._http._base_url!r})"
