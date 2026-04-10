"""
Webhook verification and event construction.

Susu Digital signs every webhook delivery with an HMAC-SHA256 signature
attached in the ``Susu-Signature`` HTTP header.  This module provides
:class:`WebhookHandler` which verifies the signature and deserialises the
payload into a typed :class:`~susudigital.types.WebhookEvent`.

Signature format::

    Susu-Signature: t=<unix_timestamp>,v1=<hex_hmac>

Usage::

    from susudigital import WebhookHandler

    handler = WebhookHandler(secret="whsec_...")

    # In a Flask view:
    event = handler.construct_event(
        payload=request.data,
        signature=request.headers["Susu-Signature"],
    )

    if event.type == "transaction.completed":
        print(event.data["transaction"]["id"])
"""

from __future__ import annotations

import hashlib
import hmac
import json
import time
from typing import Any, Callable, Dict, Optional

from susudigital.exceptions import WebhookSignatureError
from susudigital.types import WebhookEvent


class WebhookHandler:
    """Verify and parse incoming Susu Digital webhook events.

    Args:
        secret: The webhook secret from your Susu Digital dashboard
                (``whsec_...``).
        verify_signatures: Set to ``False`` *only* in development to skip
                           HMAC validation (default: ``True``).
        tolerance: Maximum age of a webhook in seconds before it is rejected
                   as a potential replay attack (default: 300 = 5 minutes).
    """

    def __init__(
        self,
        secret: str,
        *,
        verify_signatures: bool = True,
        tolerance: int = 300,
    ) -> None:
        self._secret = secret.encode() if isinstance(secret, str) else secret
        self._verify = verify_signatures
        self._tolerance = tolerance
        self._handlers: Dict[str, list[Callable[[WebhookEvent], Any]]] = {}

    # ------------------------------------------------------------------
    # Event construction
    # ------------------------------------------------------------------

    def construct_event(
        self,
        payload: bytes,
        signature: Optional[str],
        secret: Optional[str] = None,
    ) -> WebhookEvent:
        """Parse and verify a raw webhook payload.

        Args:
            payload: Raw request body bytes.
            signature: Value of the ``Susu-Signature`` header.
            secret: Override the instance-level secret (optional).

        Returns:
            A fully typed :class:`~susudigital.types.WebhookEvent`.

        Raises:
            :class:`~susudigital.exceptions.WebhookSignatureError`: If the
                signature is invalid or the timestamp is stale.
        """
        if self._verify:
            key = (secret.encode() if isinstance(secret, str) else secret) or self._secret
            self._verify_signature(payload, signature, key)

        try:
            data: Dict[str, Any] = json.loads(payload)
        except json.JSONDecodeError as exc:
            raise WebhookSignatureError(
                f"Invalid JSON payload: {exc}"
            ) from exc

        return WebhookEvent(**data)

    # ------------------------------------------------------------------
    # Event routing (decorator pattern from docs)
    # ------------------------------------------------------------------

    def on(self, event_type: str) -> Callable:
        """Register a handler for a specific webhook event type.

        .. code-block:: python

            @handler.on("transaction.completed")
            def handle_txn(event: WebhookEvent):
                update_balance(event.data["customer_id"])
        """

        def decorator(func: Callable) -> Callable:
            self._handlers.setdefault(event_type, []).append(func)
            return func

        return decorator

    def dispatch(self, event: WebhookEvent) -> None:
        """Dispatch an event to all registered handlers."""
        for handler in self._handlers.get(event.type, []):
            handler(event)
        for handler in self._handlers.get("*", []):
            handler(event)

    # ------------------------------------------------------------------
    # Internal helpers
    # ------------------------------------------------------------------

    def _verify_signature(
        self, payload: bytes, signature: Optional[str], secret: bytes
    ) -> None:
        if not signature:
            raise WebhookSignatureError("Missing Susu-Signature header")

        # Parse "t=...,v1=..."
        parts: Dict[str, str] = {}
        for part in signature.split(","):
            if "=" in part:
                k, _, v = part.partition("=")
                parts[k.strip()] = v.strip()

        timestamp = parts.get("t")
        expected_sig = parts.get("v1")

        if not timestamp or not expected_sig:
            raise WebhookSignatureError(
                "Malformed Susu-Signature header – expected t=<ts>,v1=<sig>"
            )

        # Replay-attack protection
        if self._tolerance > 0:
            try:
                age = int(time.time()) - int(timestamp)
            except ValueError:
                raise WebhookSignatureError("Invalid timestamp in signature header")
            if abs(age) > self._tolerance:
                raise WebhookSignatureError(
                    f"Webhook timestamp is too old ({age}s). "
                    f"Tolerance is {self._tolerance}s."
                )

        # HMAC-SHA256 verification
        signed_payload = f"{timestamp}.".encode() + payload
        computed = hmac.new(secret, signed_payload, hashlib.sha256).hexdigest()

        if not hmac.compare_digest(computed, expected_sig):
            raise WebhookSignatureError(
                "Webhook signature verification failed – payloads do not match"
            )
