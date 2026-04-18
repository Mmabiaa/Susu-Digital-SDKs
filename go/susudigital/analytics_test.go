package susudigital

import (
	"context"
	"encoding/json"
	"net/http"
	"net/http/httptest"
	"testing"
)

func TestAnalyticsService_GetSummary(t *testing.T) {
	server := httptest.NewServer(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.WriteHeader(http.StatusOK)
		json.NewEncoder(w).Encode(AnalyticsReport{
			TotalCustomers: 10,
		})
	}))
	defer server.Close()

	cfg := DefaultConfig("test-key")
	cfg.BaseURL = server.URL
	client := NewClient(cfg)

	report, err := client.Analytics.GetSummary(context.Background())
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if report.TotalCustomers != 10 {
		t.Errorf("Expected 10 customers, got %d", report.TotalCustomers)
	}
}
