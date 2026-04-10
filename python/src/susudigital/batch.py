"""
Batch processing utilities for the Susu Digital Python SDK.

:class:`BatchProcessor` allows high-volume operations to be split into
configurable batches and executed with configurable concurrency.

Usage::

    from susudigital.batch import BatchProcessor

    processor = BatchProcessor(client, batch_size=100)

    customers_data = [
        {"first_name": "John", "last_name": "Doe", "phone": "+233XXXXXXXXX"},
        ...
    ]

    results = processor.customers.create_batch(customers_data)

    for result in results:
        if result.success:
            print(f"Created: {result.data.id}")
        else:
            print(f"Failed: {result.error}")
"""

from __future__ import annotations

import logging
from dataclasses import dataclass, field
from typing import Any, Callable, Generic, List, Optional, TypeVar

logger = logging.getLogger(__name__)

T = TypeVar("T")


@dataclass
class BatchResult(Generic[T]):
    """Result of a single item in a batch operation."""

    success: bool
    data: Optional[T] = None
    error: Optional[Exception] = None
    index: int = 0


@dataclass
class BatchResults(Generic[T]):
    """Aggregate result of a full batch operation."""

    results: List[BatchResult[T]] = field(default_factory=list)

    @property
    def successful(self) -> List[T]:
        return [r.data for r in self.results if r.success and r.data is not None]

    @property
    def failed(self) -> List[BatchResult[T]]:
        return [r for r in self.results if not r.success]

    @property
    def success_count(self) -> int:
        return sum(1 for r in self.results if r.success)

    @property
    def failure_count(self) -> int:
        return sum(1 for r in self.results if not r.success)

    def __iter__(self):  # type: ignore[override]
        return iter(self.results)


class _BatchServiceWrapper:
    """Internal helper that wraps a service and adds ``create_batch`` / ``list_batch``."""

    def __init__(self, service: Any, batch_size: int) -> None:
        self._service = service
        self._batch_size = batch_size

    def __getattr__(self, name: str) -> Any:
        return getattr(self._service, name)

    def create_batch(self, items: List[Any]) -> BatchResults:
        """Create many resources in batches, collecting successes and failures."""
        results: BatchResults = BatchResults()

        for i in range(0, len(items), self._batch_size):
            chunk = items[i : i + self._batch_size]
            for j, item in enumerate(chunk):
                index = i + j
                try:
                    data = self._service.create(item)
                    results.results.append(BatchResult(success=True, data=data, index=index))
                except Exception as exc:
                    logger.warning("Batch create failed for item %d: %s", index, exc)
                    results.results.append(
                        BatchResult(success=False, error=exc, index=index)
                    )

        return results


class BatchProcessor:
    """Process SDK operations in configurable batches.

    Args:
        client: A :class:`~susudigital.SusuDigitalClient` instance.
        batch_size: Maximum items per batch (default: ``100``).
    """

    def __init__(self, client: Any, batch_size: int = 100) -> None:
        self._client = client
        self._batch_size = batch_size

    @property
    def customers(self) -> _BatchServiceWrapper:
        return _BatchServiceWrapper(self._client.customers, self._batch_size)

    @property
    def transactions(self) -> _BatchServiceWrapper:
        return _BatchServiceWrapper(self._client.transactions, self._batch_size)

    @property
    def loans(self) -> _BatchServiceWrapper:
        return _BatchServiceWrapper(self._client.loans, self._batch_size)
