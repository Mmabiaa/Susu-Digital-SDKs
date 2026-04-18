package susudigital

import "fmt"

// APIError represents an error response from the Susu Digital API.
type APIError struct {
	StatusCode int    `json:"-"`
	Code       string `json:"code"`
	Message    string `json:"message"`
	RequestID  string `json:"requestId"`
	Timestamp  string `json:"timestamp"`
	Retryable  bool   `json:"retryable"`
	Details    any    `json:"details,omitempty"`
}

func (e *APIError) Error() string {
	return fmt.Sprintf("susu api error: %s - %s (request_id: %s)", e.Code, e.Message, e.RequestID)
}

// Ensure APIError implements the error interface.
var _ error = (*APIError)(nil)
