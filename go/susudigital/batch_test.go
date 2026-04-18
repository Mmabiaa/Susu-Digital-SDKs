package susudigital

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestBatchProcessor_CreateTransactions(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode([]Transaction{
			{BaseModel: BaseModel{ID: "txn-1"}},
			{BaseModel: BaseModel{ID: "txn-2"}},
		})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := NewClient(cfg)
	bp := NewBatchProcessor(client)

	items := []TransactionCreateParams{
		{Amount: 10},
		{Amount: 20},
	}
	
	results, err := bp.CreateTransactions(context.Background(), items)
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if len(results) != 2 {
		t.Errorf("Expected 2 results, got %d", len(results))
	}
}
