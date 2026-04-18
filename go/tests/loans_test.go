package tests

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	susu "github.com/mmabiaa/susudigital-sdk/susudigital"
)

func TestLoanService_CreateApplication(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/loans" {
			t.Errorf("Expected /loans, got %s", r.URL.Path)
		}
		
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(susu.Loan{
			BaseModel: susu.BaseModel{ID: "loan-123"},
			Status:    "pending",
		})
	}))
	defer server.Close()

	cfg := susu.DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := susu.NewClient(cfg)

	loan, err := client.Loans.CreateApplication(context.Background(), &susu.LoanApplicationParams{
		CustomerID: "cust-1",
		Amount:     500.0,
		Duration:   12,
	})
	
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if loan.ID != "loan-123" {
		t.Errorf("Expected loan-123, got %s", loan.ID)
	}
}
