"""
Django mixins for the Susu Digital Python SDK.

:class:`DjangoSusuService` provides a base service class that automatically
reads ``settings.SUSU_DIGITAL`` and exposes a shared, lazily-initialised
:class:`~susudigital.SusuDigitalClient`.

:class:`SusuModelMixin` gives Django models a ``susu_client`` property,
mirroring the pattern shown in the SDK docs.
"""

from __future__ import annotations

import logging
from functools import cached_property
from typing import Any

logger = logging.getLogger(__name__)


class DjangoSusuService:
    """Base class for Django services that interact with Susu Digital.

    Sub-classes get a fully configured :attr:`client` without any boilerplate::

        class CustomerService(DjangoSusuService):
            def create_from_user(self, user):
                return self.client.customers.create(
                    first_name=user.first_name,
                    last_name=user.last_name,
                    phone=user.profile.phone,
                )
    """

    @cached_property
    def client(self) -> Any:  # returns SusuDigitalClient at runtime
        from django.conf import settings  # type: ignore[import]
        from susudigital import SusuDigitalClient

        cfg = getattr(settings, "SUSU_DIGITAL", {})
        return SusuDigitalClient(
            api_key=cfg["API_KEY"],
            environment=cfg.get("ENVIRONMENT", "sandbox"),
            organization=cfg.get("ORGANIZATION"),
            timeout=float(cfg.get("TIMEOUT", 30)),
            max_retries=int(cfg.get("MAX_RETRIES", 3)),
        )

    def log_error(self, message: str, exc: Exception, **context: Any) -> None:
        logger.error(message, exc_info=exc, extra=context)


class SusuModelMixin:
    """Mixin for Django models that have a ``susu_customer_id`` field.

    Provides a :attr:`susu_client` property and a :meth:`get_susu_customer`
    helper method::

        class Customer(SusuModelMixin, models.Model):
            susu_customer_id = models.CharField(max_length=100)

            def sync(self):
                susu = self.get_susu_customer()
                self.phone = susu.phone
                self.save()
    """

    @property
    def susu_client(self) -> Any:
        from django.conf import settings  # type: ignore[import]
        from susudigital import SusuDigitalClient

        cfg = getattr(settings, "SUSU_DIGITAL", {})
        return SusuDigitalClient(
            api_key=cfg["API_KEY"],
            environment=cfg.get("ENVIRONMENT", "sandbox"),
        )

    def get_susu_customer(self) -> Any:
        customer_id: str = getattr(self, "susu_customer_id", None)  # type: ignore[assignment]
        if not customer_id:
            raise ValueError("susu_customer_id is not set on this model instance")
        return self.susu_client.customers.get(customer_id)

    def get_susu_balance(self) -> Any:
        customer_id: str = getattr(self, "susu_customer_id", None)  # type: ignore[assignment]
        if not customer_id:
            raise ValueError("susu_customer_id is not set on this model instance")
        return self.susu_client.customers.get_balance(customer_id)
