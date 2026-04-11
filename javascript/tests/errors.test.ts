/**
 * Tests for the error hierarchy.
 */

import {
    AuthenticationError,
    NetworkError,
    NotFoundError,
    RateLimitError,
    ServerError,
    SusuDigitalError,
    ValidationError,
    WebhookSignatureError,
} from '../src/errors';

describe('SusuDigitalError (base)', () => {
    it('extends Error and preserves instanceof', () => {
        const err = new SusuDigitalError('something went wrong');
        expect(err).toBeInstanceOf(Error);
        expect(err).toBeInstanceOf(SusuDigitalError);
        expect(err.message).toBe('something went wrong');
        expect(err.name).toBe('SusuDigitalError');
        expect(err.code).toBe('UNKNOWN_ERROR');
        expect(err.retryable).toBe(false);
    });

    it('accepts all constructor options', () => {
        const err = new SusuDigitalError('oops', {
            code: 'CUSTOM_CODE',
            requestId: 'req_123',
            statusCode: 503,
            retryable: true,
            details: { foo: 'bar' },
        });
        expect(err.code).toBe('CUSTOM_CODE');
        expect(err.requestId).toBe('req_123');
        expect(err.statusCode).toBe(503);
        expect(err.retryable).toBe(true);
        expect(err.details).toEqual({ foo: 'bar' });
    });

    it('toString includes useful info', () => {
        const err = new SusuDigitalError('bad thing', { code: 'ERR', requestId: 'r1' });
        expect(err.toString()).toContain('SusuDigitalError');
        expect(err.toString()).toContain('ERR');
        expect(err.toString()).toContain('r1');
    });
});

describe('AuthenticationError', () => {
    it('has correct defaults', () => {
        const err = new AuthenticationError();
        expect(err).toBeInstanceOf(SusuDigitalError);
        expect(err).toBeInstanceOf(AuthenticationError);
        expect(err.code).toBe('AUTH_FAILED');
        expect(err.statusCode).toBe(401);
        expect(err.retryable).toBe(false);
        expect(err.name).toBe('AuthenticationError');
    });

    it('accepts custom message', () => {
        const err = new AuthenticationError('Token expired');
        expect(err.message).toBe('Token expired');
    });
});

describe('ValidationError', () => {
    it('has correct defaults and fieldErrors', () => {
        const err = new ValidationError('Invalid input', {
            fieldErrors: { phone: ['Invalid format'], firstName: ['Required'] },
        });
        expect(err).toBeInstanceOf(ValidationError);
        expect(err.code).toBe('VALIDATION_ERROR');
        expect(err.statusCode).toBe(422);
        expect(err.retryable).toBe(false);
        expect(err.fieldErrors).toEqual({ phone: ['Invalid format'], firstName: ['Required'] });
    });

    it('defaults fieldErrors to empty object', () => {
        const err = new ValidationError();
        expect(err.fieldErrors).toEqual({});
    });
});

describe('NotFoundError', () => {
    it('has correct defaults', () => {
        const err = new NotFoundError();
        expect(err).toBeInstanceOf(NotFoundError);
        expect(err.code).toBe('NOT_FOUND');
        expect(err.statusCode).toBe(404);
        expect(err.retryable).toBe(false);
    });
});

describe('RateLimitError', () => {
    it('has correct defaults', () => {
        const err = new RateLimitError();
        expect(err).toBeInstanceOf(RateLimitError);
        expect(err.code).toBe('RATE_LIMITED');
        expect(err.statusCode).toBe(429);
        expect(err.retryable).toBe(true);
        expect(err.retryAfter).toBe(60);
    });

    it('accepts custom retryAfter', () => {
        const err = new RateLimitError('Slow down', { retryAfter: 120 });
        expect(err.retryAfter).toBe(120);
    });
});

describe('ServerError', () => {
    it('is retryable by default', () => {
        const err = new ServerError();
        expect(err.retryable).toBe(true);
        expect(err.code).toBe('SERVER_ERROR');
        expect(err.statusCode).toBe(500);
    });
});

describe('NetworkError', () => {
    it('is retryable by default', () => {
        const err = new NetworkError('Connection refused');
        expect(err.retryable).toBe(true);
        expect(err.code).toBe('NETWORK_ERROR');
        expect(err.message).toBe('Connection refused');
    });
});

describe('WebhookSignatureError', () => {
    it('is NOT retryable', () => {
        const err = new WebhookSignatureError();
        expect(err.retryable).toBe(false);
        expect(err.code).toBe('WEBHOOK_SIGNATURE_INVALID');
    });
});

describe('instanceof across subclasses', () => {
    it('subclasses are instanceof SusuDigitalError', () => {
        const errors: SusuDigitalError[] = [
            new AuthenticationError(),
            new ValidationError(),
            new NotFoundError(),
            new RateLimitError(),
            new ServerError(),
            new NetworkError(),
            new WebhookSignatureError(),
        ];
        for (const err of errors) {
            expect(err).toBeInstanceOf(SusuDigitalError);
            expect(err).toBeInstanceOf(Error);
        }
    });
});
