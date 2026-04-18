package susudigital

import (
	"context"
	"fmt"
)

// Loan represents a loan application or issued loan.
type Loan struct {
	BaseModel
	CustomerID string  `json:"customer_id"`
	Amount     float64 `json:"amount"`
	Interest   float64 `json:"interest"`
	Status     string  `json:"status"` // "pending", "approved", "active", "completed"
}

// LoanApplicationParams holds data to apply for a loan.
type LoanApplicationParams struct {
	CustomerID string  `json:"customer_id"`
	Amount     float64 `json:"amount"`
	Duration   int     `json:"duration_months"`
}

// LoanService provides access to the Loans API.
type LoanService struct {
	http *httpClient
}

// CreateApplication submits a new loan application.
func (s *LoanService) CreateApplication(ctx context.Context, params *LoanApplicationParams) (*Loan, error) {
	var loan Loan
	err := s.http.post(ctx, "/loans", params, &loan)
	return &loan, err
}

// Get retrieves a loan record.
func (s *LoanService) Get(ctx context.Context, id string) (*Loan, error) {
	var loan Loan
	err := s.http.get(ctx, fmt.Sprintf("/loans/%s", id), &loan)
	return &loan, err
}
