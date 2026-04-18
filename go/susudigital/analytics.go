package susudigital

import (
	"context"
)

// AnalyticsReport holds business intelligence data.
type AnalyticsReport struct {
	TotalCustomers int     `json:"total_customers"`
	TotalDeposits  float64 `json:"total_deposits"`
	TotalLoans     float64 `json:"total_loans"`
}

// AnalyticsService provides access to business intelligence data.
type AnalyticsService struct {
	http *httpClient
}

// GetSummary retrieves the top-level analytics summary.
func (s *AnalyticsService) GetSummary(ctx context.Context) (*AnalyticsReport, error) {
	var report AnalyticsReport
	err := s.http.get(ctx, "/analytics/summary", &report)
	return &report, err
}
