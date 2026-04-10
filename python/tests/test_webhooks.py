"""Tests for WebhookHandler."""

from __future__ import annotations

import hashlib
import hmac
import json
import time
import pytest

from susudigital.exceptions import WebhookSignatureError
from susudigital.webhooks import WebhookHandler


SECRET = "whsec_test_secret_key_abc123"
SECRET_BYTES = SECRET.encode()


def _sign_payload(payload: bytes, timestamp: int = None, secret: bytes = SECRET_BYTES) -> str:
    """Produce a valid Susu-Signature header value."""
    ts = timestamp or int(time.time())
    signed = f"{ts}.".encode() + payload
    sig = hmac.new(secret, signed, hashlib.sha256).hexdigest()
    return f"t={ts},v1={sig}"


class TestConstructEvent:
    def test_valid_signature(self):
        handler = WebhookHandler(secret=SECRET)
        payload = json.dumps({
            "id": "evt_1",
            "type": "transaction.completed",
            "created_at": "2026-04-10T00:00:00Z",
            "data": {"transaction": {"id": "txn_1"}},
        }).encode()
        sig = _sign_payload(payload)
        event = handler.construct_event(payload, sig)
        assert event.type == "transaction.completed"
        assert event.data["transaction"]["id"] == "txn_1"

    def test_invalid_signature_raises(self):
        handler = WebhookHandler(secret=SECRET)
        payload = b'{"id":"e1","type":"x","created_at":"2026-01-01T00:00:00Z","data":{}}'
        bad_sig = "t=12345,v1=aabbccddee"
        with pytest.raises(WebhookSignatureError):
            handler.construct_event(payload, bad_sig)

    def test_missing_signature_raises(self):
        handler = WebhookHandler(secret=SECRET)
        with pytest.raises(WebhookSignatureError):
            handler.construct_event(b"{}", None)

    def test_stale_timestamp_raises(self):
        handler = WebhookHandler(secret=SECRET, tolerance=300)
        payload = b'{"id":"e1","type":"x","created_at":"2026-01-01T00:00:00Z","data":{}}'
        stale_ts = int(time.time()) - 600  # 10 minutes ago
        sig = _sign_payload(payload, timestamp=stale_ts)
        with pytest.raises(WebhookSignatureError):
            handler.construct_event(payload, sig)

    def test_verify_disabled_skips_check(self):
        handler = WebhookHandler(secret=SECRET, verify_signatures=False)
        payload = json.dumps({
            "id": "e1",
            "type": "loan.approved",
            "created_at": "2026-04-10T00:00:00Z",
            "data": {},
        }).encode()
        event = handler.construct_event(payload, "invalid-sig-ignored")
        assert event.type == "loan.approved"

    def test_invalid_json_raises(self):
        handler = WebhookHandler(secret=SECRET, verify_signatures=False)
        with pytest.raises(WebhookSignatureError):
            handler.construct_event(b"not json", None)

    def test_malformed_header_raises(self):
        handler = WebhookHandler(secret=SECRET)
        payload = b'{"id":"e1","type":"x","created_at":"2026-01-01T00:00:00Z","data":{}}'
        with pytest.raises(WebhookSignatureError):
            handler.construct_event(payload, "malformed_header_no_equals")


class TestEventDispatch:
    def test_on_decorator(self):
        handler = WebhookHandler(secret=SECRET, verify_signatures=False)
        received = []

        @handler.on("transaction.completed")
        def handle(event):
            received.append(event.type)

        from datetime import datetime, timezone
        from susudigital.types import WebhookEvent

        event = WebhookEvent(
            id="e1",
            type="transaction.completed",
            created_at=datetime.now(tz=timezone.utc),
            data={},
        )
        handler.dispatch(event)
        assert received == ["transaction.completed"]

    def test_wildcard_handler(self):
        handler = WebhookHandler(secret=SECRET, verify_signatures=False)
        received = []

        @handler.on("*")
        def catch_all(event):
            received.append(event.type)

        from datetime import datetime, timezone
        from susudigital.types import WebhookEvent

        event = WebhookEvent(
            id="e2",
            type="loan.approved",
            created_at=datetime.now(tz=timezone.utc),
            data={},
        )
        handler.dispatch(event)
        assert "loan.approved" in received
