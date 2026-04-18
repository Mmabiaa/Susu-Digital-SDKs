package tests

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"

	susu "github.com/mmabiaa/susudigital-sdk/susudigital"
)

func TestAnalyticsService_GetSummary(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(susu.AnalyticsReport{
			TotalCustomers: 10,
		})
	}))
	defer server.Close()

	cfg := susu.DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := susu.NewClient(cfg)

	report, err := client.Analytics.GetSummary(context.Background())
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if report.TotalCustomers != 10 {
		t.Errorf("Expected 10 customers, got %d", report.TotalCustomers)
	}
}
