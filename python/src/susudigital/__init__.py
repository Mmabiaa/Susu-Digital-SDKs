"""
Susu Digital Python SDK
~~~~~~~~~~~~~~~~~~~~~~~

Enterprise-Grade Python SDK for Susu Digital's microfinance platform.
Supports Django, Flask, FastAPI and plain Python 3.8+ applications.

Usage::

    from susudigital import SusuDigitalClient

    client = SusuDigitalClient(
        api_key="sk_live_...",
        environment="production",
    )

    customer = client.customers.create(
        first_name="John",
        last_name="Doe",
        phone="+233XXXXXXXXX",
    )

:copyright: (c) 2026 Susu Digital.
:license: MIT, see LICENSE for more details.
"""

from susudigital._version import __version__, __version_info__
from susudigital.client import SusuDigitalClient
from susudigital.async_client import AsyncSusuDigitalClient
from susudigital.webhooks import WebhookHandler
from susudigital.exceptions import (
    SusuDigitalError,
    AuthenticationError,
    ValidationError,
    NotFoundError,
    RateLimitError,
    NetworkError,
    ServerError,
    WebhookSignatureError,
)

__all__ = [
    "__version__",
    "__version_info__",
    # Clients
    "SusuDigitalClient",
    "AsyncSusuDigitalClient",
    # Webhook
    "WebhookHandler",
    # Exceptions
    "SusuDigitalError",
    "AuthenticationError",
    "ValidationError",
    "NotFoundError",
    "RateLimitError",
    "NetworkError",
    "ServerError",
    "WebhookSignatureError",
]
