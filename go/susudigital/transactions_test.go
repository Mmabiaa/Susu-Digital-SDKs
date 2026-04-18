package susudigital

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestTransactionService_Create(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.URL.Path != "/transactions" {
			t.Errorf("Expected /transactions, got %s", r.URL.Path)
		}
		
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(Transaction{
			BaseModel:  BaseModel{ID: "txn-123"},
			CustomerID: "cust-1",
			Amount:     100.0,
			Type:       "deposit",
		})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := NewClient(cfg)

	txn, err := client.Transactions.Create(context.Background(), &TransactionCreateParams{
		CustomerID: "cust-1",
		Amount:     100.0,
		Type:       "deposit",
	})
	
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if txn.ID != "txn-123" {
		t.Errorf("Expected txn-123, got %s", txn.ID)
	}
}
