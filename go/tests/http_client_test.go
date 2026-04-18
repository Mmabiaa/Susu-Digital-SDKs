package tests

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
	"time"

	susu "github.com/mmabiaa/susudigital-sdk/susudigital"
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
		json.NewEncoder(w).Encode(susu.AnalyticsReport{TotalCustomers: 5})
	}))
	defer server.Close()

	cfg := susu.DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	cfg.MaxRetries = 3
	cfg.Timeout = 1 * time.Second

	client := susu.NewClient(cfg)
	// Trigger HTTP request via public API
	report, err := client.Analytics.GetSummary(context.Background())

	if err != nil {
		t.Fatalf("Expected successful request after retries, got error: %v", err)
	}
	if requests != 3 {
		t.Errorf("Expected 3 requests, got %d", requests)
	}
	if report.TotalCustomers != 5 {
		t.Errorf("Expected 5 customers, got %v", report.TotalCustomers)
	}
}

func TestHTTPClient_ErrorParsing(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusBadRequest)
		json.NewEncoder(w).Encode(susu.APIError{
			Code:      "invalid_request",
			Message:   "Bad parameters",
			RequestID: "req-123",
		})
	}))
	defer server.Close()

	cfg := susu.DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	cfg.MaxRetries = 0

	client := susu.NewClient(cfg)
	_, err := client.Analytics.GetSummary(context.Background())

	if err == nil {
		t.Fatal("Expected error, got nil")
	}
	
	apiErr, ok := err.(*susu.APIError)
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
