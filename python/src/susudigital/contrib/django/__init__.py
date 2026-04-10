"""
Django integration helpers for the Susu Digital Python SDK.

Add ``'susudigital.contrib.django'`` to ``INSTALLED_APPS`` and
configure ``settings.SUSU_DIGITAL``::

    # settings.py
    SUSU_DIGITAL = {
        "API_KEY": os.environ["SUSU_API_KEY"],
        "ENVIRONMENT": os.environ.get("SUSU_ENVIRONMENT", "sandbox"),
        "ORGANIZATION": os.environ.get("SUSU_ORGANIZATION_ID"),
        "WEBHOOK_SECRET": os.environ.get("SUSU_WEBHOOK_SECRET"),
        "TIMEOUT": 30,
        "MAX_RETRIES": 3,
    }

    INSTALLED_APPS = [
        ...
        "susudigital.contrib.django",
    ]
"""

from __future__ import annotations

from susudigital.contrib.django.app import SusuDigitalConfig
from susudigital.contrib.django.mixins import DjangoSusuService, SusuModelMixin
from susudigital.contrib.django.webhooks import webhook_handler

__all__ = [
    "SusuDigitalConfig",
    "DjangoSusuService",
    "SusuModelMixin",
    "webhook_handler",
]

default_app_config = "susudigital.contrib.django.app.SusuDigitalConfig"
