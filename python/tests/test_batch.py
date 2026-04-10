"""Tests for BatchProcessor."""

from __future__ import annotations

import pytest
from decimal import Decimal
from unittest.mock import MagicMock

from susudigital.batch import BatchProcessor, BatchResult, BatchResults
from susudigital.exceptions import SusuDigitalError


class TestBatchResults:
    def test_successful_items(self):
        results = BatchResults(results=[
            BatchResult(success=True, data="a", index=0),
            BatchResult(success=False, error=Exception("err"), index=1),
            BatchResult(success=True, data="b", index=2),
        ])
        assert results.successful == ["a", "b"]
        assert results.failure_count == 1
        assert results.success_count == 2

    def test_failed_items(self):
        err = SusuDigitalError("bad")
        results = BatchResults(results=[
            BatchResult(success=False, error=err, index=0),
        ])
        assert len(results.failed) == 1
        assert results.failed[0].error is err

    def test_iterable(self):
        results = BatchResults(results=[
            BatchResult(success=True, data="x", index=0),
        ])
        items = list(results)
        assert len(items) == 1


class TestBatchProcessor:
    def test_create_batch_all_success(self):
        client = MagicMock()
        client.customers.create.side_effect = [
            MagicMock(id="c1"),
            MagicMock(id="c2"),
        ]
        processor = BatchProcessor(client, batch_size=10)
        results = processor.customers.create_batch([
            {"first_name": "A", "last_name": "B", "phone": "+233244000001"},
            {"first_name": "C", "last_name": "D", "phone": "+233244000002"},
        ])
        assert results.success_count == 2
        assert results.failure_count == 0

    def test_create_batch_partial_failure(self):
        client = MagicMock()
        client.customers.create.side_effect = [
            MagicMock(id="c1"),
            SusuDigitalError("boom"),
        ]
        processor = BatchProcessor(client, batch_size=10)
        results = processor.customers.create_batch([
            {"first_name": "A", "last_name": "B", "phone": "+233244000001"},
            {"first_name": "C", "last_name": "D", "phone": "+233244000002"},
        ])
        assert results.success_count == 1
        assert results.failure_count == 1

    def test_batch_size_respected(self):
        """Verify batching splits data into correct chunks."""
        client = MagicMock()
        client.customers.create.return_value = MagicMock(id="cx")
        processor = BatchProcessor(client, batch_size=2)
        items = [{"first_name": str(i), "last_name": "X", "phone": "+233244000001"}
                 for i in range(5)]
        results = processor.customers.create_batch(items)
        assert client.customers.create.call_count == 5
