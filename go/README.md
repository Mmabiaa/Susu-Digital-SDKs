# Susu Digital Go SDK

The official Go SDK for the Susu Digital API.

## Installation

```sh
go get github.com/mmabiaa/susudigital-sdk
```

## Usage

```go
package main

import (
	"context"
	"fmt"
	"log"

	susu "github.com/mmabiaa/susudigital-sdk"
)

func main() {
	cfg := susu.DefaultConfig("your-api-key")
	client := susu.NewClient(cfg)

	customer, err := client.Customers.Create(context.Background(), &susu.CustomerCreateParams{
		FirstName: "John",
		LastName:  "Doe",
		Phone:     "+233XXXXXXXXX",
	})
	if err != nil {
		log.Fatalf("Error creating customer: %v", err)
	}

	fmt.Printf("Created customer ID: %s\n", customer.ID)
}
```

## Webhook Verification

```go
handler := susu.NewWebhookHandler("your-webhook-secret")
event, err := handler.ConstructEvent(payloadBytes, signatureHeader)
if err != nil {
	// handle error
}
fmt.Printf("Received event type: %s\n", event.Type)
```
