/**
 * Webhook verification and event construction.
 *
 * Susu Digital signs every webhook delivery with an HMAC-SHA256 signature
 * attached in the `Susu-Signature` HTTP header. This module provides
 * {@link WebhookHandler} which verifies the signature and deserialises the
 * payload into a typed {@link WebhookEvent}.
 *
 * Signature format:
 *   Susu-Signature: t=<unix_timestamp>,v1=<hex_hmac>
 *
 * @example
 * ```ts
 * import { WebhookHandler } from '@susudigital/sdk';
 *
 * const handler = new WebhookHandler({ secret: process.env.SUSU_WEBHOOK_SECRET! });
 *
 * // Express.js:
 * app.post('/webhooks/susu', express.raw({ type: 'application/json' }), (req, res) => {
 *   const event = handler.constructEvent(req.body, req.headers['susu-signature']);
 *   if (event.type === 'transaction.completed') { ... }
 *   res.status(200).send('OK');
 * });
 * ```
 */

import { createHmac, timingSafeEqual } from 'node:crypto';
import { WebhookSignatureError } from './errors.js';
import type { WebhookEvent } from './types.js';

export interface WebhookHandlerConfig {
    /** Webhook secret from your Susu Digital dashboard (whsec_...) */
    secret: string;
    /** Maximum age of a webhook in seconds before it is rejected (default: 300) */
    tolerance?: number;
    /** Set to false in development to skip HMAC validation (default: true) */
    verifySignatures?: boolean;
}

type EventHandler = (event: WebhookEvent) => void | Promise<void>;

export class WebhookHandler {
    private readonly secret: string;
    private readonly tolerance: number;
    private readonly verifySignatures: boolean;
    private readonly handlers: Map<string, EventHandler[]> = new Map();

    constructor(config: WebhookHandlerConfig) {
        this.secret = config.secret;
        this.tolerance = config.tolerance ?? 300;
        this.verifySignatures = config.verifySignatures ?? true;
    }

    // --------------------------------------------------------------------------
    // Event construction
    // --------------------------------------------------------------------------

    /**
     * Parse and verify a raw webhook payload.
     *
     * @param payload - Raw request body (Buffer or string).
     * @param signature - Value of the `Susu-Signature` header.
     * @param secret - Override the instance-level secret (optional).
     * @returns A fully typed {@link WebhookEvent}.
     * @throws {@link WebhookSignatureError} If the signature is invalid or stale.
     */
    constructEvent(
        payload: Buffer | string,
        signature: string | null | undefined,
        secret?: string,
    ): WebhookEvent {
        if (this.verifySignatures) {
            this.verifySignature(
                Buffer.isBuffer(payload) ? payload : Buffer.from(payload),
                signature,
                secret ?? this.secret,
            );
        }

        let data: Record<string, unknown>;
        try {
            data = JSON.parse(payload.toString()) as Record<string, unknown>;
        } catch (err) {
            throw new WebhookSignatureError(`Invalid JSON payload: ${String(err)}`);
        }

        return data as unknown as WebhookEvent;
    }

    // --------------------------------------------------------------------------
    // Event routing (decorator pattern)
    // --------------------------------------------------------------------------

    /**
     * Register a handler for a specific webhook event type.
     *
     * @example
     * ```ts
     * handler.on('transaction.completed', (event) => {
     *   console.log('Transaction completed:', event.data.transactionId);
     * });
     * ```
     */
    on(eventType: string, handler: EventHandler): this {
        const list = this.handlers.get(eventType) ?? [];
        list.push(handler);
        this.handlers.set(eventType, list);
        return this;
    }

    /**
     * Dispatch an event to all registered handlers (including `*` wildcards).
     */
    async dispatch(event: WebhookEvent): Promise<void> {
        const specificHandlers = this.handlers.get(event.type) ?? [];
        const wildcardHandlers = this.handlers.get('*') ?? [];

        for (const handler of [...specificHandlers, ...wildcardHandlers]) {
            await handler(event);
        }
    }

    // --------------------------------------------------------------------------
    // Internal helpers
    // --------------------------------------------------------------------------

    private verifySignature(payload: Buffer, signature: string | null | undefined, secret: string): void {
        if (!signature) {
            throw new WebhookSignatureError('Missing Susu-Signature header');
        }

        // Parse "t=...,v1=..."
        const parts: Record<string, string> = {};
        for (const part of signature.split(',')) {
            const eqIdx = part.indexOf('=');
            if (eqIdx > -1) {
                const k = part.slice(0, eqIdx).trim();
                const v = part.slice(eqIdx + 1).trim();
                parts[k] = v;
            }
        }

        const timestamp = parts['t'];
        const expectedSig = parts['v1'];

        if (!timestamp || !expectedSig) {
            throw new WebhookSignatureError(
                'Malformed Susu-Signature header – expected t=<ts>,v1=<sig>',
            );
        }

        // Replay-attack protection
        if (this.tolerance > 0) {
            const nowSec = Math.floor(Date.now() / 1000);
            const age = nowSec - parseInt(timestamp, 10);
            if (isNaN(age) || Math.abs(age) > this.tolerance) {
                throw new WebhookSignatureError(
                    `Webhook timestamp is too old (${age}s). Tolerance is ${this.tolerance}s.`,
                );
            }
        }

        // HMAC-SHA256 verification
        const signedPayload = Buffer.concat([Buffer.from(`${timestamp}.`), payload]);
        const computed = createHmac('sha256', secret).update(signedPayload).digest('hex');

        let computedBuf: Buffer;
        let expectedBuf: Buffer;
        try {
            computedBuf = Buffer.from(computed, 'hex');
            expectedBuf = Buffer.from(expectedSig, 'hex');
        } catch {
            throw new WebhookSignatureError('Webhook signature verification failed – invalid hex encoding');
        }

        if (
            computedBuf.length !== expectedBuf.length ||
            !timingSafeEqual(computedBuf, expectedBuf)
        ) {
            throw new WebhookSignatureError(
                'Webhook signature verification failed – payloads do not match',
            );
        }
    }
}
