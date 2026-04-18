package tests

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"testing"

	susu "github.com/susudigital/susu-go-sdk/susudigital"
)

func TestWebhookHandler_ConstructEvent(t *testing.T) {
	secret := "whsec_test_secret"
	payload := []byte(`{"id":"evt-123","type":"customer.created"}`)
	
	mac := hmac.New(sha256.New, []byte(secret))
	mac.Write(payload)
	signature := hex.EncodeToString(mac.Sum(nil))

	handler := susu.NewWebhookHandler(secret)
	event, err := handler.ConstructEvent(payload, signature)
	
	if err != nil {
		t.Fatalf("Unexpected error: %v", err)
	}
	if event.ID != "evt-123" {
		t.Errorf("Expected ID evt-123, got %s", event.ID)
	}
	if event.Type != "customer.created" {
		t.Errorf("Expected type customer.created, got %s", event.Type)
	}
}

func TestWebhookHandler_InvalidSignature(t *testing.T) {
	handler := susu.NewWebhookHandler("whsec_test_secret")
	_, err := handler.ConstructEvent([]byte(`{}`), "invalid_sig")
	if err == nil {
		t.Fatal("Expected error for invalid signature")
	}
}
