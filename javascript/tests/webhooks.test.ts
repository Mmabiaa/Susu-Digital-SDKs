/**
 * Tests for WebhookHandler.
 */

import { createHmac } from 'node:crypto';
import { WebhookHandler } from '../src/webhooks';
import { WebhookSignatureError } from '../src/errors';
import { mockWebhookEvent } from './fixtures';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

const SECRET = 'whsec_testSecretKey12345';

function makeSignature(payload: string | Buffer, secret: string, timestampOverride?: number): string {
    const ts = timestampOverride ?? Math.floor(Date.now() / 1000);
    const payloadBuf = Buffer.isBuffer(payload) ? payload : Buffer.from(payload);
    const signed = Buffer.concat([Buffer.from(`${ts}.`), payloadBuf]);
    const sig = createHmac('sha256', secret).update(signed).digest('hex');
    return `t=${ts},v1=${sig}`;
}

function makePayload(event = mockWebhookEvent): Buffer {
    return Buffer.from(JSON.stringify(event));
}

function makeHandler(overrides: Partial<ConstructorParameters<typeof WebhookHandler>[0]> = {}): WebhookHandler {
    return new WebhookHandler({ secret: SECRET, ...overrides });
}

// ---------------------------------------------------------------------------
// constructEvent()
// ---------------------------------------------------------------------------

describe('WebhookHandler.constructEvent()', () => {
    it('returns a typed WebhookEvent for a valid signature', () => {
        const payload = makePayload();
        const sig = makeSignature(payload, SECRET);
        const handler = makeHandler();
        const event = handler.constructEvent(payload, sig);
        expect(event.id).toBe(mockWebhookEvent.id);
        expect(event.type).toBe('transaction.completed');
    });

    it('accepts a string payload', () => {
        const payloadStr = JSON.stringify(mockWebhookEvent);
        const sig = makeSignature(payloadStr, SECRET);
        const handler = makeHandler();
        const event = handler.constructEvent(payloadStr, sig);
        expect(event.id).toBe(mockWebhookEvent.id);
    });

    it('throws WebhookSignatureError when signature is null', () => {
        const handler = makeHandler();
        expect(() => handler.constructEvent(makePayload(), null)).toThrow(WebhookSignatureError);
        expect(() => handler.constructEvent(makePayload(), null)).toThrow('Missing Susu-Signature header');
    });

    it('throws WebhookSignatureError when signature is missing', () => {
        const handler = makeHandler();
        expect(() => handler.constructEvent(makePayload(), undefined)).toThrow(WebhookSignatureError);
    });

    it('throws WebhookSignatureError for malformed signature', () => {
        const handler = makeHandler();
        expect(() => handler.constructEvent(makePayload(), 'not=a,valid=signature')).toThrow(WebhookSignatureError);
    });

    it('throws WebhookSignatureError when signature is wrong', () => {
        const payload = makePayload();
        const sig = makeSignature(payload, 'wrong_secret');
        const handler = makeHandler();
        expect(() => handler.constructEvent(payload, sig)).toThrow(WebhookSignatureError);
    });

    it('throws WebhookSignatureError for stale timestamp (> tolerance)', () => {
        const payload = makePayload();
        const staleTs = Math.floor(Date.now() / 1000) - 400; // 400s old, tolerance=300
        const sig = makeSignature(payload, SECRET, staleTs);
        const handler = makeHandler({ tolerance: 300 });
        expect(() => handler.constructEvent(payload, sig)).toThrow(WebhookSignatureError);
        expect(() => handler.constructEvent(payload, sig)).toThrow('too old');
    });

    it('accepts payload within tolerance window', () => {
        const payload = makePayload();
        const recentTs = Math.floor(Date.now() / 1000) - 100; // 100s old
        const sig = makeSignature(payload, SECRET, recentTs);
        const handler = makeHandler({ tolerance: 300 });
        expect(() => handler.constructEvent(payload, sig)).not.toThrow();
    });

    it('skips signature check when verifySignatures=false', () => {
        const handler = makeHandler({ verifySignatures: false });
        const payload = Buffer.from(JSON.stringify(mockWebhookEvent));
        const event = handler.constructEvent(payload, 'totally-invalid-signature');
        expect(event.type).toBe('transaction.completed');
    });

    it('throws WebhookSignatureError for invalid JSON payload', () => {
        const payload = makePayload();
        const sig = makeSignature(payload, SECRET);
        const handler = makeHandler();
        // Valid sig but broken JSON
        expect(() => handler.constructEvent(Buffer.from('not json'), sig)).toThrow(WebhookSignatureError);
    });

    it('accepts a one-time secret override', () => {
        const payload = makePayload();
        const overrideSecret = 'alternate_secret_xyz';
        const sig = makeSignature(payload, overrideSecret);
        const handler = makeHandler(); // uses different secret by default
        const event = handler.constructEvent(payload, sig, overrideSecret);
        expect(event.id).toBe(mockWebhookEvent.id);
    });
});

// ---------------------------------------------------------------------------
// on() / dispatch()
// ---------------------------------------------------------------------------

describe('WebhookHandler.on() / dispatch()', () => {
    it('dispatches event to registered handler', async () => {
        const handler = makeHandler({ verifySignatures: false });
        const received: unknown[] = [];
        handler.on('transaction.completed', (evt) => { received.push(evt); });

        await handler.dispatch(mockWebhookEvent);
        expect(received).toHaveLength(1);
        expect(received[0]).toMatchObject({ id: mockWebhookEvent.id });
    });

    it('dispatches to wildcard * handler', async () => {
        const handler = makeHandler({ verifySignatures: false });
        const received: string[] = [];
        handler.on('*', (evt) => { received.push(evt.type); });

        await handler.dispatch(mockWebhookEvent);
        expect(received).toContain('transaction.completed');
    });

    it('dispatches to both specific and wildcard handlers', async () => {
        const handler = makeHandler({ verifySignatures: false });
        const specific: number[] = [];
        const wildcard: number[] = [];
        handler.on('transaction.completed', () => { specific.push(1); });
        handler.on('*', () => { wildcard.push(1); });

        await handler.dispatch(mockWebhookEvent);
        expect(specific).toHaveLength(1);
        expect(wildcard).toHaveLength(1);
    });

    it('does NOT dispatch to mismatched event type', async () => {
        const handler = makeHandler({ verifySignatures: false });
        const received: unknown[] = [];
        handler.on('loan.approved', (evt) => { received.push(evt); });

        await handler.dispatch(mockWebhookEvent); // type: transaction.completed
        expect(received).toHaveLength(0);
    });

    it('supports multiple handlers for the same event type', async () => {
        const handler = makeHandler({ verifySignatures: false });
        let count = 0;
        handler.on('transaction.completed', () => { count++; });
        handler.on('transaction.completed', () => { count++; });

        await handler.dispatch(mockWebhookEvent);
        expect(count).toBe(2);
    });

    it('on() returns this for chaining', () => {
        const handler = makeHandler({ verifySignatures: false });
        const result = handler.on('transaction.completed', () => { });
        expect(result).toBe(handler);
    });
});
