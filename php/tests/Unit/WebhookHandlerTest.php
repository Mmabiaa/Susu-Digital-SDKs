<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SusuDigital\Exceptions\WebhookSignatureException;
use SusuDigital\Models\WebhookEvent;
use SusuDigital\WebhookHandler;

final class WebhookHandlerTest extends TestCase
{
    private const SECRET = 'whsec_test_secret_key_1234567890';

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Build a valid Susu-Signature header for the given payload and timestamp.
     */
    private function makeSignature(string $payload, int $timestamp, string $secret = self::SECRET): string
    {
        $signed   = "{$timestamp}.{$payload}";
        $hmac     = hash_hmac('sha256', $signed, $secret);
        return "t={$timestamp},v1={$hmac}";
    }

    private function makePayload(array $data = []): string
    {
        $defaults = [
            'id'          => 'evt_test_001',
            'type'        => 'transaction.completed',
            'created_at'  => '2026-04-11T00:00:00Z',
            'data'        => ['amount' => '100.00'],
            'api_version' => 'v1',
        ];

        return json_encode(array_merge($defaults, $data));
    }

    // -------------------------------------------------------------------------
    // constructEvent – happy path
    // -------------------------------------------------------------------------

    public function test_construct_event_with_valid_signature(): void
    {
        $handler   = new WebhookHandler(self::SECRET, tolerance: 300);
        $payload   = $this->makePayload();
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp);

        $event = $handler->constructEvent($payload, $signature);

        $this->assertInstanceOf(WebhookEvent::class, $event);
        $this->assertSame('evt_test_001', $event->id);
        $this->assertSame('transaction.completed', $event->type);
    }

    public function test_construct_event_with_override_secret(): void
    {
        $altSecret = 'whsec_alt_secret';
        $handler   = new WebhookHandler(self::SECRET, tolerance: 300);
        $payload   = $this->makePayload();
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp, $altSecret);

        $event = $handler->constructEvent($payload, $signature, $altSecret);

        $this->assertInstanceOf(WebhookEvent::class, $event);
    }

    // -------------------------------------------------------------------------
    // constructEvent – error cases
    // -------------------------------------------------------------------------

    public function test_missing_signature_header_throws(): void
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessageMatches('/Missing Susu-Signature/');

        $handler = new WebhookHandler(self::SECRET);
        $handler->constructEvent($this->makePayload(), null);
    }

    public function test_empty_signature_header_throws(): void
    {
        $this->expectException(WebhookSignatureException::class);

        $handler = new WebhookHandler(self::SECRET);
        $handler->constructEvent($this->makePayload(), '');
    }

    public function test_malformed_signature_missing_v1_throws(): void
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessageMatches('/Malformed/');

        $handler = new WebhookHandler(self::SECRET);
        $handler->constructEvent($this->makePayload(), 't=' . time());
    }

    public function test_stale_timestamp_throws(): void
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessageMatches('/too old/');

        $handler   = new WebhookHandler(self::SECRET, tolerance: 300);
        $payload   = $this->makePayload();
        $timestamp = time() - 600; // 10 minutes ago
        $signature = $this->makeSignature($payload, $timestamp);

        $handler->constructEvent($payload, $signature);
    }

    public function test_wrong_secret_throws(): void
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessageMatches('/verification failed/');

        $handler   = new WebhookHandler(self::SECRET);
        $payload   = $this->makePayload();
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp, 'wrong_secret');

        $handler->constructEvent($payload, $signature);
    }

    public function test_invalid_json_throws(): void
    {
        $this->expectException(WebhookSignatureException::class);
        $this->expectExceptionMessageMatches('/Invalid JSON/');

        $payload   = 'not-valid-json';
        $timestamp = time();
        $signature = $this->makeSignature($payload, $timestamp);

        $handler = new WebhookHandler(self::SECRET, tolerance: 300);
        $handler->constructEvent($payload, $signature);
    }

    // -------------------------------------------------------------------------
    // Bypass mode (verifySignatures = false)
    // -------------------------------------------------------------------------

    public function test_construct_event_bypasses_verification_in_dev_mode(): void
    {
        $handler = new WebhookHandler(self::SECRET, verifySignatures: false);
        $payload = $this->makePayload();

        // No valid signature – should still succeed
        $event = $handler->constructEvent($payload, 'garbage');

        $this->assertSame('transaction.completed', $event->type);
    }

    // -------------------------------------------------------------------------
    // Event routing
    // -------------------------------------------------------------------------

    public function test_on_registers_and_dispatch_calls_handler(): void
    {
        $handler = new WebhookHandler(self::SECRET, verifySignatures: false);
        $called  = false;

        $handler->on('transaction.completed', function (WebhookEvent $event) use (&$called) {
            $called = true;
            $this->assertSame('transaction.completed', $event->type);
        });

        $event = new WebhookEvent([
            'id'         => 'e1',
            'type'       => 'transaction.completed',
            'created_at' => '2026-01-01T00:00:00Z',
            'data'       => [],
        ]);

        $handler->dispatch($event);

        $this->assertTrue($called);
    }

    public function test_wildcard_handler_receives_all_events(): void
    {
        $handler  = new WebhookHandler(self::SECRET, verifySignatures: false);
        $received = [];

        $handler->on('*', function (WebhookEvent $event) use (&$received) {
            $received[] = $event->type;
        });

        foreach (['txn.created', 'loan.approved', 'customer.updated'] as $type) {
            $handler->dispatch(new WebhookEvent([
                'id'         => 'e',
                'type'       => $type,
                'created_at' => '2026-01-01T00:00:00Z',
                'data'       => [],
            ]));
        }

        $this->assertSame(['txn.created', 'loan.approved', 'customer.updated'], $received);
    }

    public function test_unregistered_event_type_does_not_throw(): void
    {
        $handler = new WebhookHandler(self::SECRET, verifySignatures: false);

        // No handler registered → dispatch should be a no-op
        $handler->dispatch(new WebhookEvent([
            'id'         => 'e2',
            'type'       => 'unknown.event',
            'created_at' => '2026-01-01T00:00:00Z',
            'data'       => [],
        ]));

        $this->assertTrue(true); // No exception = pass
    }

    public function test_multiple_handlers_for_same_event_are_all_called(): void
    {
        $handler = new WebhookHandler(self::SECRET, verifySignatures: false);
        $callCount = 0;

        $handler->on('customer.created', function () use (&$callCount) { $callCount++; });
        $handler->on('customer.created', function () use (&$callCount) { $callCount++; });

        $handler->dispatch(new WebhookEvent([
            'id'         => 'e3',
            'type'       => 'customer.created',
            'created_at' => '2026-01-01T00:00:00Z',
            'data'       => [],
        ]));

        $this->assertSame(2, $callCount);
    }

    public function test_on_returns_handler_for_fluent_chaining(): void
    {
        $handler = new WebhookHandler(self::SECRET, verifySignatures: false);
        $result  = $handler->on('test.event', fn () => null);

        $this->assertSame($handler, $result);
    }
}
