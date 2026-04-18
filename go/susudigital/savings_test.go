package susudigital

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestSavingsService_Create(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(SavingsAccount{
			BaseModel: BaseModel{ID: "sav-123"},
			Balance:   0.0,
		})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := NewClient(cfg)

	sav, err := client.Savings.Create(context.Background(), "cust-1")
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if sav.ID != "sav-123" {
		t.Errorf("Expected sav-123, got %s", sav.ID)
	}
}
