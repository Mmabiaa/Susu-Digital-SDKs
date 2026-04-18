package tests

import (
	"testing"
	susu "github.com/susudigital/susu-go-sdk/susudigital"
)

func TestNewClient(t *testing.T) {
	cfg := susu.DefaultConfig("test-key")
	client := susu.NewClient(cfg)

	if client == nil {
		t.Fatal("Expected non-nil client")
	}
	if client.Customers == nil {
		t.Error("Expected Customers service to be initialized")
	}
	if client.Transactions == nil {
		t.Error("Expected Transactions service to be initialized")
	}
	if client.Loans == nil {
		t.Error("Expected Loans service to be initialized")
	}
	if client.Savings == nil {
		t.Error("Expected Savings service to be initialized")
	}
	if client.Analytics == nil {
		t.Error("Expected Analytics service to be initialized")
	}
}
