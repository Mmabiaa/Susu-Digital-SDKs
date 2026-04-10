"""Django-specific webhook handler helper."""

from __future__ import annotations

from susudigital.webhooks import WebhookHandler


def _get_webhook_handler() -> WebhookHandler:
    """Return a :class:`WebhookHandler` configured from Django settings."""
    from django.conf import settings  # type: ignore[import]

    cfg = getattr(settings, "SUSU_DIGITAL", {})
    secret = cfg.get("WEBHOOK_SECRET", "")
    return WebhookHandler(secret=secret)


# Module-level singleton – lazily initialised to avoid Django import errors
# at module import time.
class _LazyWebhookHandler:
    _instance: WebhookHandler | None = None

    def __getattr__(self, name: str):  # type: ignore[override]
        if self._instance is None:
            self._instance = _get_webhook_handler()
        return getattr(self._instance, name)


webhook_handler: WebhookHandler = _LazyWebhookHandler()  # type: ignore[assignment]
