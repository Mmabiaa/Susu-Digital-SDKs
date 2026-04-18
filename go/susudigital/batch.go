package susudigital

import (
	"context"
)

// BatchProcessor handles bulk operations.
type BatchProcessor struct {
	client *Client
}

// NewBatchProcessor creates a helper for processing items in batches.
func NewBatchProcessor(client *Client) *BatchProcessor {
	return &BatchProcessor{client: client}
}

// CreateTransactions submits multiple transactions in a single batch.
func (bp *BatchProcessor) CreateTransactions(ctx context.Context, items []TransactionCreateParams) ([]Transaction, error) {
	var results []Transaction
	err := bp.client.http.post(ctx, "/batch/transactions", map[string]any{"items": items}, &results)
	return results, err
}
