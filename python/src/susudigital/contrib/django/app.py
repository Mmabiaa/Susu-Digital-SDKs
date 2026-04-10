"""Django AppConfig for Susu Digital."""

from __future__ import annotations

from django.apps import AppConfig  # type: ignore[import]


class SusuDigitalConfig(AppConfig):
    name = "susudigital.contrib.django"
    label = "susudigital"
    verbose_name = "Susu Digital"

    def ready(self) -> None:
        """Validate settings on startup."""
        from django.conf import settings  # type: ignore[import]

        cfg = getattr(settings, "SUSU_DIGITAL", {})
        if not cfg.get("API_KEY"):
            import warnings

            warnings.warn(
                "SUSU_DIGITAL['API_KEY'] is not set. "
                "The Susu Digital SDK will not function correctly.",
                RuntimeWarning,
                stacklevel=2,
            )
