package susudigital

import (
	"context"
	"fmt"
)

// SavingsAccount represents a customer's savings account.
type SavingsAccount struct {
	BaseModel
	CustomerID string  `json:"customer_id"`
	Balance    float64 `json:"balance"`
	Status     string  `json:"status"`
}

// SavingsService provides access to the Savings API.
type SavingsService struct {
	http *httpClient
}

// Get retrieves a savings account by ID.
func (s *SavingsService) Get(ctx context.Context, id string) (*SavingsAccount, error) {
	var acc SavingsAccount
	err := s.http.get(ctx, fmt.Sprintf("/savings/%s", id), &acc)
	return &acc, err
}

// Create creates a new savings account for a customer.
func (s *SavingsService) Create(ctx context.Context, customerID string) (*SavingsAccount, error) {
	var acc SavingsAccount
	err := s.http.post(ctx, "/savings", map[string]string{"customer_id": customerID}, &acc)
	return &acc, err
}
