package susudigital

import (
	"context"
	"fmt"
)

// Transaction represents a financial transaction.
type Transaction struct {
	BaseModel
	CustomerID string  `json:"customer_id"`
	Amount     float64 `json:"amount"`
	Type       string  `json:"type"` // "deposit", "withdrawal", "transfer"
	Status     string  `json:"status"`
}

// TransactionCreateParams holds parameters for a transaction.
type TransactionCreateParams struct {
	CustomerID string  `json:"customer_id"`
	Amount     float64 `json:"amount"`
	Type       string  `json:"type"`
	Notes      string  `json:"notes,omitempty"`
}

// TransactionService provides access to the Transactions API.
type TransactionService struct {
	http *httpClient
}

// Create creates a transaction.
func (s *TransactionService) Create(ctx context.Context, params *TransactionCreateParams) (*Transaction, error) {
	var txn Transaction
	err := s.http.post(ctx, "/transactions", params, &txn)
	return &txn, err
}

// Get retrieves a transaction by ID.
func (s *TransactionService) Get(ctx context.Context, id string) (*Transaction, error) {
	var txn Transaction
	err := s.http.get(ctx, fmt.Sprintf("/transactions/%s", id), &txn)
	return &txn, err
}
