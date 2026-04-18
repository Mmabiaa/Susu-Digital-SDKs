package susudigital

import (
	"crypto/hmac"
	"crypto/sha256"
	"encoding/hex"
	"encoding/json"
	"errors"
)

// WebhookEvent represents an incoming webhook payload.
type WebhookEvent struct {
	ID        string `json:"id"`
	Type      string `json:"type"`
	Timestamp string `json:"timestamp"`
	Data      any    `json:"data"`
}

// WebhookHandler deals with parsing and validating webhooks.
type WebhookHandler struct {
	secret string
}

// NewWebhookHandler initializes a webhook validation helper.
func NewWebhookHandler(secret string) *WebhookHandler {
	return &WebhookHandler{secret: secret}
}

// ConstructEvent parses and verifies the signature of a webhook event.
func (wh *WebhookHandler) ConstructEvent(payload []byte, signatureHeader string) (*WebhookEvent, error) {
	mac := hmac.New(sha256.New, []byte(wh.secret))
	mac.Write(payload)
	expectedSig := hex.EncodeToString(mac.Sum(nil))

	if !hmac.Equal([]byte(expectedSig), []byte(signatureHeader)) {
		return nil, errors.New("invalid webhook signature")
	}

	var event WebhookEvent
	if err := json.Unmarshal(payload, &event); err != nil {
		return nil, err
	}
	return &event, nil
}
