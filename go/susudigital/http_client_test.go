package susudigital

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"
)

func TestHTTPClient_Retries(t *testing.T) {
	requests := 0
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		requests++
		if requests < 3 {
			w.WriteHeader(http.StatusInternalServerError)
			return
		}
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(map[string]string{"status": "ok"})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	cfg.MaxRetries = 3
	cfg.Timeout = 1 * time.Second

	client := NewClient(cfg)
	var result map[string]string
	err := client.http.get(context.Background(), "/test", &result)

	if err != nil {
		t.Fatalf("Expected successful request after retries, got error: %v", err)
	}
	if requests != 3 {
		t.Errorf("Expected 3 requests, got %d", requests)
	}
	if result["status"] != "ok" {
		t.Errorf("Expected status ok, got %v", result["status"])
	}
}

func TestHTTPClient_ErrorParsing(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(APIError{
			Code:      "invalid_request",
			Message:   "Bad parameters",
			RequestID: "req-123",
		})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	cfg.MaxRetries = 0

	client := NewClient(cfg)
	err := client.http.get(context.Background(), "/test", nil)

	if err == nil {
		t.Fatal("Expected error, got nil")
	}
	
	apiErr, ok := err.(*APIError)
	if !ok {
		t.Fatalf("Expected *APIError, got %T", err)
	}
	if apiErr.Code != "invalid_request" {
		t.Errorf("Expected code invalid_request, got %s", apiErr.Code)
	}
	if apiErr.StatusCode != http.StatusBadRequest {
		t.Errorf("Expected status code 400, got %d", apiErr.StatusCode)
	}
}
