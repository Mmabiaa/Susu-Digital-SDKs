"""
Low-level HTTP client built on ``httpx``.

Responsibilities:
- Injects authentication headers on every request.
- Implements automatic retry with exponential back-off.
- Raises domain-specific :mod:`susudigital.exceptions` on error responses.
- Provides both synchronous (``HttpClient``) and asynchronous
  (``AsyncHttpClient``) flavours that share the same logic via
  :func:`_parse_response`.
"""

from __future__ import annotations

import logging
import time
import uuid
from typing import Any, Dict, Optional, Type, TypeVar

import httpx

from susudigital._version import __version__
from susudigital.exceptions import (
    AuthenticationError,
    NetworkError,
    NotFoundError,
    RateLimitError,
    ServerError,
    SusuDigitalError,
    ValidationError,
)

logger = logging.getLogger(__name__)

T = TypeVar("T")

# Base URLs per environment
_BASE_URLS: Dict[str, str] = {
    "production": "https://susu-digital.onrender.com",
    "sandbox": "https://api-sandbox.susudigital.app/v1",
}

_DEFAULT_TIMEOUT = 30.0
_DEFAULT_MAX_RETRIES = 3
_RETRYABLE_STATUS_CODES = {429, 500, 502, 503, 504}


def _default_headers(api_key: str, organization: Optional[str] = None) -> Dict[str, str]:
    headers = {
        "Authorization": f"Bearer {api_key}",
        "Content-Type": "application/json",
        "Accept": "application/json",
        "User-Agent": f"susudigital-python/{__version__}",
        "X-SDK-Version": __version__,
        "X-SDK-Language": "python",
    }
    if organization:
        headers["X-Organization-ID"] = organization
    return headers


def _parse_response(response: httpx.Response) -> Dict[str, Any]:
    """Convert an httpx response to a Python dict, raising on error."""
    request_id = response.headers.get("X-Request-ID")

    if response.is_success:
        if not response.content:
            return {}
        return response.json()  # type: ignore[no-any-return]

    # Attempt to decode error body
    try:
        body: Dict[str, Any] = response.json()
    except Exception:
        body = {"message": response.text or "Unknown error"}

    message: str = body.get("message", body.get("error", "An error occurred"))
    code: str = body.get("code", "UNKNOWN_ERROR")
    details: Any = body.get("details")

    status = response.status_code

    if status == 401 or status == 403:
        raise AuthenticationError(message, code=code, request_id=request_id, details=details)
    if status == 404:
        raise NotFoundError(message, code=code, request_id=request_id, details=details)
    if status == 429:
        retry_after = int(response.headers.get("Retry-After", "60"))
        raise RateLimitError(
            message,
            code=code,
            retry_after=retry_after,
            request_id=request_id,
            details=details,
        )
    if status in (400, 422):
        field_errors: Dict[str, Any] = body.get("field_errors", body.get("errors", {}))
        raise ValidationError(
            message,
            code=code,
            field_errors=field_errors,
            request_id=request_id,
            details=details,
        )
    if status >= 500:
        raise ServerError(message, code=code, request_id=request_id, status_code=status, details=details)

    raise SusuDigitalError(
        message, code=code, request_id=request_id, status_code=status, details=details
    )


def _should_retry(exc: Exception, attempt: int, max_retries: int) -> bool:
    """Return True if the request should be retried."""
    if attempt >= max_retries:
        return False
    if isinstance(exc, (NetworkError, ServerError, RateLimitError)):
        return getattr(exc, "retryable", False)
    if isinstance(exc, (httpx.TimeoutException, httpx.ConnectError)):
        return True
    return False


def _backoff_delay(attempt: int, base: float = 0.5, cap: float = 30.0) -> float:
    """Exponential back-off: 0.5s, 1s, 2s … capped at ``cap``."""
    import random
    delay = min(base * (2 ** attempt), cap)
    # Add jitter (±25 %)
    return delay * (0.75 + random.random() * 0.5)


# ---------------------------------------------------------------------------
# Synchronous HTTP client
# ---------------------------------------------------------------------------


class HttpClient:
    """Thin synchronous wrapper around ``httpx.Client``."""

    def __init__(
        self,
        api_key: str,
        *,
        environment: str = "sandbox",
        organization: Optional[str] = None,
        timeout: float = _DEFAULT_TIMEOUT,
        max_retries: int = _DEFAULT_MAX_RETRIES,
        custom_headers: Optional[Dict[str, str]] = None,
        http_client: Optional[httpx.Client] = None,
        enable_logging: bool = False,
    ) -> None:
        self._base_url = _BASE_URLS.get(environment, _BASE_URLS["sandbox"])
        self._max_retries = max_retries
        self._enable_logging = enable_logging

        headers = _default_headers(api_key, organization)
        if custom_headers:
            headers.update(custom_headers)

        self._client = http_client or httpx.Client(
            base_url=self._base_url,
            headers=headers,
            timeout=timeout,
        )

    # ------------------------------------------------------------------
    # Public verbs
    # ------------------------------------------------------------------

    def get(self, path: str, params: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._request("GET", path, params=params)

    def post(self, path: str, json: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._request("POST", path, json=json)

    def put(self, path: str, json: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._request("PUT", path, json=json)

    def patch(self, path: str, json: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return self._request("PATCH", path, json=json)

    def delete(self, path: str) -> Dict[str, Any]:
        return self._request("DELETE", path)

    # ------------------------------------------------------------------
    # Internal
    # ------------------------------------------------------------------

    def _request(
        self,
        method: str,
        path: str,
        *,
        params: Optional[Dict[str, Any]] = None,
        json: Optional[Dict[str, Any]] = None,
    ) -> Dict[str, Any]:
        correlation_id = str(uuid.uuid4())
        for attempt in range(self._max_retries + 1):
            try:
                if self._enable_logging:
                    logger.debug(
                        "SDK request",
                        extra={
                            "method": method,
                            "path": path,
                            "attempt": attempt + 1,
                            "correlation_id": correlation_id,
                        },
                    )
                response = self._client.request(
                    method,
                    path,
                    params=params,
                    json=json,
                    headers={"X-Idempotency-Key": correlation_id},
                )
                result = _parse_response(response)
                if self._enable_logging:
                    logger.debug(
                        "SDK response",
                        extra={
                            "status_code": response.status_code,
                            "correlation_id": correlation_id,
                        },
                    )
                return result
            except (httpx.TimeoutException, httpx.ConnectError) as exc:
                wrapped = NetworkError(str(exc))
                if _should_retry(wrapped, attempt, self._max_retries):
                    time.sleep(_backoff_delay(attempt))
                    continue
                raise wrapped from exc
            except (RateLimitError, ServerError) as exc:
                if _should_retry(exc, attempt, self._max_retries):
                    delay = (
                        getattr(exc, "retry_after", None) or _backoff_delay(attempt)
                    )
                    time.sleep(float(delay))
                    continue
                raise
        # Unreachable, but satisfies the type checker
        raise SusuDigitalError("Maximum retries exceeded")  # pragma: no cover

    def close(self) -> None:
        """Close the underlying HTTP session."""
        self._client.close()

    def __enter__(self) -> "HttpClient":
        return self

    def __exit__(self, *args: Any) -> None:
        self.close()


# ---------------------------------------------------------------------------
# Asynchronous HTTP client
# ---------------------------------------------------------------------------


class AsyncHttpClient:
    """Thin asynchronous wrapper around ``httpx.AsyncClient``."""

    def __init__(
        self,
        api_key: str,
        *,
        environment: str = "sandbox",
        organization: Optional[str] = None,
        timeout: float = _DEFAULT_TIMEOUT,
        max_retries: int = _DEFAULT_MAX_RETRIES,
        custom_headers: Optional[Dict[str, str]] = None,
        http_client: Optional[httpx.AsyncClient] = None,
        enable_logging: bool = False,
    ) -> None:
        self._base_url = _BASE_URLS.get(environment, _BASE_URLS["sandbox"])
        self._max_retries = max_retries
        self._enable_logging = enable_logging

        headers = _default_headers(api_key, organization)
        if custom_headers:
            headers.update(custom_headers)

        self._client = http_client or httpx.AsyncClient(
            base_url=self._base_url,
            headers=headers,
            timeout=timeout,
        )

    async def get(self, path: str, params: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return await self._request("GET", path, params=params)

    async def post(self, path: str, json: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return await self._request("POST", path, json=json)

    async def put(self, path: str, json: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return await self._request("PUT", path, json=json)

    async def patch(self, path: str, json: Optional[Dict[str, Any]] = None) -> Dict[str, Any]:
        return await self._request("PATCH", path, json=json)

    async def delete(self, path: str) -> Dict[str, Any]:
        return await self._request("DELETE", path)

    async def _request(
        self,
        method: str,
        path: str,
        *,
        params: Optional[Dict[str, Any]] = None,
        json: Optional[Dict[str, Any]] = None,
    ) -> Dict[str, Any]:
        import asyncio

        correlation_id = str(uuid.uuid4())
        for attempt in range(self._max_retries + 1):
            try:
                response = await self._client.request(
                    method,
                    path,
                    params=params,
                    json=json,
                    headers={"X-Idempotency-Key": correlation_id},
                )
                return _parse_response(response)
            except (httpx.TimeoutException, httpx.ConnectError) as exc:
                wrapped = NetworkError(str(exc))
                if _should_retry(wrapped, attempt, self._max_retries):
                    await asyncio.sleep(_backoff_delay(attempt))
                    continue
                raise wrapped from exc
            except (RateLimitError, ServerError) as exc:
                if _should_retry(exc, attempt, self._max_retries):
                    delay = float(getattr(exc, "retry_after", None) or _backoff_delay(attempt))
                    await asyncio.sleep(delay)
                    continue
                raise
        raise SusuDigitalError("Maximum retries exceeded")  # pragma: no cover

    async def close(self) -> None:
        await self._client.aclose()

    async def __aenter__(self) -> "AsyncHttpClient":
        return self

    async def __aexit__(self, *args: Any) -> None:
        await self.close()
