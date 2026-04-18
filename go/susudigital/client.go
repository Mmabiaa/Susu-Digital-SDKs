package susudigital

// Client is the primary entry-point for the Susu Digital Go SDK.
type Client struct {
	config       *Config
	http         *httpClient
	
	Customers    *CustomerService
	Transactions *TransactionService
	Loans        *LoanService
	Savings      *SavingsService
	Analytics    *AnalyticsService
}

// NewClient initializes a new Susu Digital client API.
func NewClient(cfg *Config) *Client {
	hc := newHTTPClient(cfg)
	return &Client{
		config:       cfg,
		http:         hc,
		Customers:    &CustomerService{http: hc},
		Transactions: &TransactionService{http: hc},
		Loans:        &LoanService{http: hc},
		Savings:      &SavingsService{http: hc},
		Analytics:    &AnalyticsService{http: hc},
	}
}
