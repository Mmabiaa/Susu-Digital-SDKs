package susudigital

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestCustomerService_Create(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		if r.Method != http.MethodPost {
			t.Errorf("Expected POST, got %s", r.Method)
		}
		if r.URL.Path != "/customers" {
			t.Errorf("Expected /customers, got %s", r.URL.Path)
		}
		
		var params CustomerCreateParams
		json.NewDecoder(r.Body).Decode(&params)
		if params.FirstName != "Alice" {
			t.Errorf("Expected Alice, got %s", params.FirstName)
		}

		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(Customer{
			BaseModel: BaseModel{ID: "cust-123"},
			FirstName: "Alice",
			LastName:  "Smith",
			Status:    "active",
		})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := NewClient(cfg)

	customer, err := client.Customers.Create(context.Background(), &CustomerCreateParams{
		FirstName: "Alice",
		LastName:  "Smith",
	})
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if customer.ID != "cust-123" {
		t.Errorf("Expected cust-123, got %s", customer.ID)
	}
}
