"""Unit tests for the exception hierarchy."""

from __future__ import annotations

import pytest

from susudigital.exceptions import (
    AuthenticationError,
    NetworkError,
    NotFoundError,
    RateLimitError,
    ServerError,
    SusuDigitalError,
    ValidationError,
    WebhookSignatureError,
)


class TestSusuDigitalError:
    def test_defaults(self):
        exc = SusuDigitalError("Something went wrong")
        assert exc.message == "Something went wrong"
        assert exc.code == "UNKNOWN_ERROR"
        assert exc.request_id is None
        assert exc.retryable is False

    def test_custom_fields(self):
        exc = SusuDigitalError(
            "bad",
            code="CUSTOM",
            request_id="req_001",
            retryable=True,
            status_code=503,
        )
        assert exc.code == "CUSTOM"
        assert exc.request_id == "req_001"
        assert exc.retryable is True
        assert exc.status_code == 503

    def test_repr(self):
        exc = SusuDigitalError("oops", code="X", request_id="r1")
        assert "SusuDigitalError" in repr(exc)
        assert "oops" in repr(exc)


class TestAuthenticationError:
    def test_defaults(self):
        exc = AuthenticationError()
        assert exc.code == "AUTH_FAILED"
        assert exc.status_code == 401
        assert exc.retryable is False

    def test_is_susu_error(self):
        assert isinstance(AuthenticationError(), SusuDigitalError)


class TestValidationError:
    def test_defaults(self):
        exc = ValidationError()
        assert exc.code == "VALIDATION_ERROR"
        assert exc.status_code == 422
        assert exc.field_errors == {}

    def test_field_errors(self):
        exc = ValidationError(field_errors={"phone": ["Invalid format"]})
        assert exc.field_errors == {"phone": ["Invalid format"]}
        assert exc.field_error_details == {"field_errors": {"phone": ["Invalid format"]}}


class TestRateLimitError:
    def test_defaults(self):
        exc = RateLimitError()
        assert exc.code == "RATE_LIMITED"
        assert exc.retry_after == 60
        assert exc.retryable is True

    def test_custom_retry_after(self):
        exc = RateLimitError(retry_after=120)
        assert exc.retry_after == 120


class TestNotFoundError:
    def test_defaults(self):
        exc = NotFoundError()
        assert exc.code == "NOT_FOUND"
        assert exc.status_code == 404


class TestNetworkError:
    def test_retryable(self):
        exc = NetworkError()
        assert exc.retryable is True


class TestServerError:
    def test_retryable(self):
        exc = ServerError()
        assert exc.retryable is True


class TestWebhookSignatureError:
    def test_defaults(self):
        exc = WebhookSignatureError()
        assert exc.code == "WEBHOOK_SIGNATURE_INVALID"
        assert exc.retryable is False
