package susudigital

import (
	"bytes"
	"context"
	"encoding/json"
	"fmt"
	"io"
	"log"
	"net/http"
	"time"
)

type httpClient struct {
	client *http.Client
	config *Config
}

func newHTTPClient(cfg *Config) *httpClient {
	return &httpClient{
		client: &http.Client{
			Timeout: cfg.Timeout,
		},
		config: cfg,
	}
}

func (hc *httpClient) do(ctx context.Context, method, path string, body, result any) error {
	baseURL := "https://api.sandbox.susudigital.app/v1"
	if hc.config.Environment == "production" {
		baseURL = "https://api.susudigital.app/v1"
	}
	u := baseURL + path

	var reqBody io.Reader
	if body != nil {
		b, err := json.Marshal(body)
		if err != nil {
			return err
		}
		reqBody = bytes.NewReader(b)
	}

	req, err := http.NewRequestWithContext(ctx, method, u, reqBody)
	if err != nil {
		return err
	}

	req.Header.Set("Authorization", "Bearer "+hc.config.APIKey)
	req.Header.Set("Content-Type", "application/json")
	req.Header.Set("User-Agent", "susu-go-sdk/0.7.0")
	if hc.config.Organization != "" {
		req.Header.Set("X-Organization-Id", hc.config.Organization)
	}
	for k, v := range hc.config.CustomHeaders {
		req.Header.Set(k, v)
	}

	var res *http.Response
	retries := hc.config.MaxRetries
	for i := 0; i <= retries; i++ {
		res, err = hc.client.Do(req)
		if err != nil {
			if i == retries {
				return err
			}
			time.Sleep(time.Duration(i*2) * time.Second) // basic backoff
			continue
		}
		
		if hc.config.EnableLogging {
			log.Printf("Susu API Request: %s %s, Status: %d", method, path, res.StatusCode)
		}

		if res.StatusCode >= 400 {
			if res.StatusCode == http.StatusTooManyRequests || res.StatusCode >= 500 {
				res.Body.Close()
				if i < retries {
					time.Sleep(time.Duration(i*2) * time.Second)
					continue
				}
			}
			defer res.Body.Close()
			var apiErr APIError
			if err := json.NewDecoder(res.Body).Decode(&apiErr); err == nil {
				apiErr.StatusCode = res.StatusCode
				return &apiErr
			}
			return fmt.Errorf("http error: %d", res.StatusCode)
		}
		
		break
	}

	if result != nil {
		defer res.Body.Close()
		if err := json.NewDecoder(res.Body).Decode(result); err != nil {
			return err
		}
	} else if res != nil && res.Body != nil {
		res.Body.Close()
	}

	return nil
}

func (hc *httpClient) get(ctx context.Context, path string, result any) error {
	return hc.do(ctx, http.MethodGet, path, nil, result)
}

func (hc *httpClient) post(ctx context.Context, path string, body, result any) error {
	return hc.do(ctx, http.MethodPost, path, body, result)
}
