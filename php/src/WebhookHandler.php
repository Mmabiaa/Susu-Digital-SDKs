<?php

declare(strict_types=1);

namespace SusuDigital;

use SusuDigital\Exceptions\WebhookSignatureException;
use SusuDigital\Models\WebhookEvent;

/**
 * Webhook verification and event construction.
 *
 * Susu Digital signs every webhook delivery with an HMAC-SHA256 signature
 * attached in the `Susu-Signature` HTTP header. This class verifies the
 * signature and deserialises the payload into a typed WebhookEvent.
 *
 * Signature format:
 *   Susu-Signature: t=<unix_timestamp>,v1=<hex_hmac>
 *
 * Usage (e.g. in a Laravel controller):
 *
 *   $handler = new WebhookHandler(secret: 'whsec_...');
 *
 *   $event = $handler->constructEvent(
 *       payload:   $request->getContent(),
 *       signature: $request->header('Susu-Signature'),
 *   );
 *
 *   if ($event->type === 'transaction.completed') {
 *       // handle it
 *   }
 */
final class WebhookHandler
{
    private string $secret;
    private bool $verifySignatures;
    private int $tolerance;

    /** @var array<string, callable[]> */
    private array $handlers = [];

    /**
     * @param string $secret            Webhook secret from your Susu Digital dashboard (whsec_...).
     * @param bool   $verifySignatures  Set to false ONLY in development to skip HMAC validation.
     * @param int    $tolerance         Maximum age of a webhook in seconds (default: 300 = 5 min).
     */
    public function __construct(
        string $secret,
        bool $verifySignatures = true,
        int $tolerance = 300,
    ) {
        $this->secret           = $secret;
        $this->verifySignatures = $verifySignatures;
        $this->tolerance        = $tolerance;
    }

    // -------------------------------------------------------------------------
    // Event construction
    // -------------------------------------------------------------------------

    /**
     * Parse and verify a raw webhook payload.
     *
     * @param  string|resource $payload    Raw request body.
     * @param  string|null     $signature  Value of the Susu-Signature header.
     * @param  string|null     $secret     Override the instance-level secret (optional).
     *
     * @throws WebhookSignatureException  If the signature is invalid or the timestamp is stale.
     */
    public function constructEvent(
        string $payload,
        ?string $signature,
        ?string $secret = null,
    ): WebhookEvent {
        if ($this->verifySignatures) {
            $key = $secret ?? $this->secret;
            $this->verifySignature($payload, $signature, $key);
        }

        $data = json_decode($payload, true);

        if (!\is_array($data)) {
            throw new WebhookSignatureException('Invalid JSON payload');
        }

        return new WebhookEvent($data);
    }

    // -------------------------------------------------------------------------
    // Event routing (decorator pattern matching Python SDK)
    // -------------------------------------------------------------------------

    /**
     * Register a handler for a specific webhook event type.
     *
     * Usage:
     *
     *   $handler->on('transaction.completed', function (WebhookEvent $event) {
     *       updateBalance($event->data['customer_id']);
     *   });
     */
    public function on(string $eventType, callable $callback): self
    {
        $this->handlers[$eventType][] = $callback;
        return $this;
    }

    /**
     * Dispatch an event to all registered handlers.
     */
    public function dispatch(WebhookEvent $event): void
    {
        foreach ($this->handlers[$event->type] ?? [] as $callback) {
            $callback($event);
        }

        // Wildcard handlers
        foreach ($this->handlers['*'] ?? [] as $callback) {
            $callback($event);
        }
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * @throws WebhookSignatureException
     */
    private function verifySignature(string $payload, ?string $signature, string $secret): void
    {
        if ($signature === null || $signature === '') {
            throw new WebhookSignatureException('Missing Susu-Signature header');
        }

        // Parse "t=<ts>,v1=<sig>"
        $parts = [];
        foreach (explode(',', $signature) as $part) {
            if (str_contains($part, '=')) {
                [$k, $v]     = explode('=', trim($part), 2);
                $parts[$k] = $v;
            }
        }

        $timestamp   = $parts['t'] ?? null;
        $expectedSig = $parts['v1'] ?? null;

        if ($timestamp === null || $expectedSig === null) {
            throw new WebhookSignatureException(
                'Malformed Susu-Signature header – expected t=<ts>,v1=<sig>'
            );
        }

        // Replay-attack protection
        if ($this->tolerance > 0) {
            if (!ctype_digit($timestamp)) {
                throw new WebhookSignatureException('Invalid timestamp in Susu-Signature header');
            }

            $age = abs(time() - (int) $timestamp);

            if ($age > $this->tolerance) {
                throw new WebhookSignatureException(
                    "Webhook timestamp is too old ({$age}s). Tolerance is {$this->tolerance}s."
                );
            }
        }

        // HMAC-SHA256 verification
        $signedPayload = "{$timestamp}.{$payload}";
        $computed      = hash_hmac('sha256', $signedPayload, $secret);

        if (!hash_equals($computed, $expectedSig)) {
            throw new WebhookSignatureException(
                'Webhook signature verification failed – payloads do not match'
            );
        }
    }
}
