"""
Exception hierarchy for the Susu Digital Python SDK.

All SDK exceptions inherit from :class:`SusuDigitalError`, allowing
callers to catch either specific exceptions or the base class.

Exception Hierarchy::

    SusuDigitalError
    ├── AuthenticationError       (HTTP 401)
    ├── ValidationError           (HTTP 422 / 400 – field errors)
    ├── NotFoundError             (HTTP 404)
    ├── RateLimitError            (HTTP 429)
    ├── ServerError               (HTTP 5xx)
    ├── NetworkError              (connection / timeout)
    └── WebhookSignatureError     (bad webhook HMAC)
"""

from __future__ import annotations

from typing import Any, Dict, List, Optional


class SusuDigitalError(Exception):
    """Base exception for all Susu Digital SDK errors.

    Attributes:
        message: Human-readable description of the error.
        code: Machine-readable error code (e.g. ``VALIDATION_ERROR``).
        request_id: Unique identifier for the originating request.
        status_code: HTTP status code, if applicable.
        retryable: Whether the failed operation may be safely retried.
        details: Raw error payload from the API.
    """

    def __init__(
        self,
        message: str,
        *,
        code: str = "UNKNOWN_ERROR",
        request_id: Optional[str] = None,
        status_code: Optional[int] = None,
        retryable: bool = False,
        details: Optional[Any] = None,
    ) -> None:
        super().__init__(message)
        self.message = message
        self.code = code
        self.request_id = request_id
        self.status_code = status_code
        self.retryable = retryable
        self.details = details

    def __repr__(self) -> str:
        return (
            f"{type(self).__name__}("
            f"message={self.message!r}, "
            f"code={self.code!r}, "
            f"request_id={self.request_id!r})"
        )


class AuthenticationError(SusuDigitalError):
    """Raised when API authentication fails (HTTP 401 / 403).

    Usually indicates an invalid or missing API key.
    """

    def __init__(self, message: str = "Authentication failed", **kwargs: Any) -> None:
        kwargs.setdefault("code", "AUTH_FAILED")
        kwargs.setdefault("status_code", 401)
        kwargs.setdefault("retryable", False)
        super().__init__(message, **kwargs)


class ValidationError(SusuDigitalError):
    """Raised when the request payload fails validation (HTTP 422 / 400).

    Attributes:
        field_errors: Mapping of field names to error messages.
    """

    def __init__(
        self,
        message: str = "Validation failed",
        *,
        field_errors: Optional[Dict[str, List[str]]] = None,
        **kwargs: Any,
    ) -> None:
        kwargs.setdefault("code", "VALIDATION_ERROR")
        kwargs.setdefault("status_code", 422)
        kwargs.setdefault("retryable", False)
        super().__init__(message, **kwargs)
        self.field_errors: Dict[str, List[str]] = field_errors or {}

    @property
    def field_error_details(self) -> Dict[str, Any]:
        """Return field_errors wrapped in a dict (for convenience)."""
        return {"field_errors": self.field_errors}


class NotFoundError(SusuDigitalError):
    """Raised when a requested resource cannot be found (HTTP 404)."""

    def __init__(self, message: str = "Resource not found", **kwargs: Any) -> None:
        kwargs.setdefault("code", "NOT_FOUND")
        kwargs.setdefault("status_code", 404)
        kwargs.setdefault("retryable", False)
        super().__init__(message, **kwargs)


class RateLimitError(SusuDigitalError):
    """Raised when the client is rate-limited (HTTP 429).

    Attributes:
        retry_after: Number of seconds to wait before the next attempt.
    """

    def __init__(
        self,
        message: str = "Rate limit exceeded",
        *,
        retry_after: int = 60,
        **kwargs: Any,
    ) -> None:
        kwargs.setdefault("code", "RATE_LIMITED")
        kwargs.setdefault("status_code", 429)
        kwargs.setdefault("retryable", True)
        super().__init__(message, **kwargs)
        self.retry_after = retry_after


class ServerError(SusuDigitalError):
    """Raised when the Susu Digital API returns a 5xx error."""

    def __init__(self, message: str = "Server error", **kwargs: Any) -> None:
        kwargs.setdefault("code", "SERVER_ERROR")
        kwargs.setdefault("status_code", 500)
        kwargs.setdefault("retryable", True)
        super().__init__(message, **kwargs)


class NetworkError(SusuDigitalError):
    """Raised when a network or transport error occurs (connection refused,
    timeout, TLS error, etc.)."""

    def __init__(self, message: str = "Network error", **kwargs: Any) -> None:
        kwargs.setdefault("code", "NETWORK_ERROR")
        kwargs.setdefault("retryable", True)
        super().__init__(message, **kwargs)


class WebhookSignatureError(SusuDigitalError):
    """Raised when a webhook payload signature cannot be verified."""

    def __init__(
        self, message: str = "Webhook signature verification failed", **kwargs: Any
    ) -> None:
        kwargs.setdefault("code", "WEBHOOK_SIGNATURE_INVALID")
        kwargs.setdefault("retryable", False)
        super().__init__(message, **kwargs)
