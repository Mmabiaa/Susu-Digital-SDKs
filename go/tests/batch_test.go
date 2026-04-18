package tests

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	susu "github.com/susudigital/susu-go-sdk/susudigital"
)

func TestBatchProcessor_CreateTransactions(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode([]susu.Transaction{
			{BaseModel: susu.BaseModel{ID: "txn-1"}},
			{BaseModel: susu.BaseModel{ID: "txn-2"}},
		})
	}))
	defer server.Close()

	cfg := susu.DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := susu.NewClient(cfg)
	bp := susu.NewBatchProcessor(client)

	items := []susu.TransactionCreateParams{
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
