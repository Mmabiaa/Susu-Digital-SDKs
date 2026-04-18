package tests

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	susu "github.com/susudigital/susu-go-sdk/susudigital"
)

func TestSavingsService_Create(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(susu.SavingsAccount{
			BaseModel: susu.BaseModel{ID: "sav-123"},
			Balance:   0.0,
		})
	}))
	defer server.Close()

	cfg := susu.DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := susu.NewClient(cfg)

	sav, err := client.Savings.Create(context.Background(), "cust-1")
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if sav.ID != "sav-123" {
		t.Errorf("Expected sav-123, got %s", sav.ID)
	}
}
