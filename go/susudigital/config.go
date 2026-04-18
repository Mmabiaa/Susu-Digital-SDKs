package susudigital

import "time"

// Config holds the configuration for the Susu Digital API client.
type Config struct {
	APIKey        string
	Environment   string             // "sandbox" or "production"
	BaseURL       string             // Override the base URL for testing
	Organization  string             // Optional organization ID
	Timeout       time.Duration      // HTTP timeout. Default is 30s.
	MaxRetries    int                // Maximum retries. Default is 3.
	EnableLogging bool
	CustomHeaders map[string]string
}

// DefaultConfig returns a new Config with default values.
func DefaultConfig(apiKey string) *Config {
	return &Config{
		APIKey:      apiKey,
		Environment: "sandbox",
		Timeout:     30 * time.Second,
		MaxRetries:  3,
	}
}
