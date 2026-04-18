package susudigital

import (
	"context"
	"fmt"
)

// Customer represents a Susu Digital customer.
type Customer struct {
	BaseModel
	FirstName string `json:"first_name"`
	LastName  string `json:"last_name"`
	Phone     string `json:"phone"`
	Email     string `json:"email,omitempty"`
	Status    string `json:"status"`
}

// CustomerCreateParams holds data for creating a new customer.
type CustomerCreateParams struct {
	FirstName string `json:"first_name"`
	LastName  string `json:"last_name"`
	Phone     string `json:"phone"`
	Email     string `json:"email,omitempty"`
}

// CustomerService provides access to the Customers API.
type CustomerService struct {
	http *httpClient
}

// Create creates a new customer.
func (s *CustomerService) Create(ctx context.Context, params *CustomerCreateParams) (*Customer, error) {
	var customer Customer
	err := s.http.post(ctx, "/customers", params, &customer)
	return &customer, err
}

// Get retrieves a customer by ID.
func (s *CustomerService) Get(ctx context.Context, id string) (*Customer, error) {
	var customer Customer
	err := s.http.get(ctx, fmt.Sprintf("/customers/%s", id), &customer)
	return &customer, err
}

// List retrieves a paginated list of customers.
func (s *CustomerService) List(ctx context.Context, limit int, startingAfter string) (*PagedResult[Customer], error) {
	var res PagedResult[Customer]
	path := fmt.Sprintf("/customers?limit=%d", limit)
	if startingAfter != "" {
		path += "&starting_after=" + startingAfter
	}
	err := s.http.get(ctx, path, &res)
	return &res, err
}
