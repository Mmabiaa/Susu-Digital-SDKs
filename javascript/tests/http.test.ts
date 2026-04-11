/**
 * Tests for the HttpClient (unit – fetch is mocked).
 */

import { HttpClient } from '../src/http';
import {
    AuthenticationError,
    NetworkError,
    NotFoundError,
    RateLimitError,
    ServerError,
    SusuDigitalError,
    ValidationError,
} from '../src/errors';

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function mockFetchOnce(status: number, body: unknown, headers: Record<string, string> = {}): void {
    global.fetch = jest.fn().mockResolvedValueOnce({
        ok: status >= 200 && status < 300,
        status,
        statusText: 'Test',
        headers: {
            get: (key: string) => headers[key] ?? null,
        },
        json: () => Promise.resolve(body),
    } as unknown as Response);
}

function mockFetchNetworkError(message = 'Network failure'): void {
    global.fetch = jest.fn().mockRejectedValueOnce(new TypeError(message));
}

function makeClient(overrides: Partial<ConstructorParameters<typeof HttpClient>[0]> = {}): HttpClient {
    return new HttpClient({
        apiKey: 'sk_test_abc123',
        environment: 'sandbox',
        retryAttempts: 0, // disable retries for unit tests
        ...overrides,
    });
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('HttpClient – successful responses', () => {
    it('GET returns parsed JSON', async () => {
        mockFetchOnce(200, { id: 'cust_123', firstName: 'John' });
        const client = makeClient();
        const result = await client.get('/customers/cust_123');
        expect(result).toEqual({ id: 'cust_123', firstName: 'John' });
    });

    it('POST sends JSON body and returns response', async () => {
        mockFetchOnce(201, { id: 'cust_new' });
        const client = makeClient();
        const result = await client.post('/customers', { firstName: 'Jane' });
        expect(result).toEqual({ id: 'cust_new' });

        const calls = (global.fetch as jest.Mock).mock.calls as [string, RequestInit][];
        expect(calls[0]?.[1]?.body).toBe(JSON.stringify({ firstName: 'Jane' }));
    });

    it('PATCH sends body', async () => {
        mockFetchOnce(200, { id: 'cust_123', email: 'new@example.com' });
        const client = makeClient();
        const result = await client.patch('/customers/cust_123', { email: 'new@example.com' });
        expect(result).toHaveProperty('email', 'new@example.com');
    });

    it('DELETE succeeds with empty body', async () => {
        global.fetch = jest.fn().mockResolvedValueOnce({
            ok: true,
            status: 204,
            headers: { get: () => '0' },
            json: () => Promise.reject(new Error('no body')),
        } as unknown as Response);
        const client = makeClient();
        const result = await client.delete('/customers/cust_123');
        expect(result).toEqual({});
    });

    it('appends query params to URL', async () => {
        mockFetchOnce(200, { data: [] });
        const client = makeClient();
        await client.get('/customers', { page: 1, limit: 50, status: 'active' });

        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).toContain('page=1');
        expect(url).toContain('limit=50');
        expect(url).toContain('status=active');
    });

    it('omits null/undefined query params', async () => {
        mockFetchOnce(200, { data: [] });
        const client = makeClient();
        await client.get('/customers', { page: 1, search: undefined, status: null as unknown as string });

        const url = (global.fetch as jest.Mock).mock.calls[0]?.[0] as string;
        expect(url).not.toContain('search');
        expect(url).not.toContain('status');
    });
});

describe('HttpClient – authentication header', () => {
    it('sends Authorization: Bearer <apiKey>', async () => {
        mockFetchOnce(200, {});
        const client = makeClient({ apiKey: 'sk_test_mykey' });
        await client.get('/test');

        const reqInit = (global.fetch as jest.Mock).mock.calls[0]?.[1] as RequestInit;
        expect((reqInit.headers as Record<string, string>)['Authorization']).toBe('Bearer sk_test_mykey');
    });

    it('includes X-Organization-ID when provided', async () => {
        mockFetchOnce(200, {});
        const client = makeClient({ organization: 'org_123' });
        await client.get('/test');

        const reqInit = (global.fetch as jest.Mock).mock.calls[0]?.[1] as RequestInit;
        expect((reqInit.headers as Record<string, string>)['X-Organization-ID']).toBe('org_123');
    });

    it('merges custom headers', async () => {
        mockFetchOnce(200, {});
        const client = makeClient({ customHeaders: { 'X-App-Version': '1.0.0' } });
        await client.get('/test');

        const reqInit = (global.fetch as jest.Mock).mock.calls[0]?.[1] as RequestInit;
        expect((reqInit.headers as Record<string, string>)['X-App-Version']).toBe('1.0.0');
    });
});

describe('HttpClient – error mapping', () => {
    it('throws AuthenticationError on 401', async () => {
        mockFetchOnce(401, { message: 'Unauthorized', code: 'AUTH_FAILED' });
        const client = makeClient();
        await expect(client.get('/customers')).rejects.toBeInstanceOf(AuthenticationError);
    });

    it('throws AuthenticationError on 403', async () => {
        mockFetchOnce(403, { message: 'Forbidden', code: 'AUTH_FAILED' });
        const client = makeClient();
        await expect(client.get('/customers')).rejects.toBeInstanceOf(AuthenticationError);
    });

    it('throws NotFoundError on 404', async () => {
        mockFetchOnce(404, { message: 'Not found', code: 'NOT_FOUND' });
        const client = makeClient();
        await expect(client.get('/customers/bad_id')).rejects.toBeInstanceOf(NotFoundError);
    });

    it('throws ValidationError on 422 with fieldErrors', async () => {
        mockFetchOnce(422, {
            message: 'Validation failed',
            code: 'VALIDATION_ERROR',
            field_errors: { phone: ['Invalid format'] },
        });
        const client = makeClient();
        const err = await client.get('/customers').catch((e) => e) as ValidationError;
        expect(err).toBeInstanceOf(ValidationError);
        expect(err.fieldErrors).toEqual({ phone: ['Invalid format'] });
    });

    it('throws ValidationError on 400', async () => {
        mockFetchOnce(400, { message: 'Bad request', code: 'VALIDATION_ERROR' });
        const client = makeClient();
        await expect(client.post('/customers', {})).rejects.toBeInstanceOf(ValidationError);
    });

    it('throws RateLimitError on 429 with Retry-After', async () => {
        mockFetchOnce(429, { message: 'Rate limited', code: 'RATE_LIMITED' }, { 'Retry-After': '30' });
        const client = makeClient();
        const err = await client.get('/customers').catch((e) => e) as RateLimitError;
        expect(err).toBeInstanceOf(RateLimitError);
        expect(err.retryAfter).toBe(30);
    });

    it('throws ServerError on 500', async () => {
        mockFetchOnce(500, { message: 'Internal error', code: 'SERVER_ERROR' });
        const client = makeClient();
        await expect(client.get('/customers')).rejects.toBeInstanceOf(ServerError);
    });

    it('throws ServerError on 503', async () => {
        mockFetchOnce(503, { message: 'Service unavailable' });
        const client = makeClient();
        await expect(client.get('/customers')).rejects.toBeInstanceOf(ServerError);
    });

    it('throws SusuDigitalError for unexpected status codes', async () => {
        mockFetchOnce(418, { message: "I'm a teapot" });
        const client = makeClient();
        const err = await client.get('/tea').catch((e) => e) as SusuDigitalError;
        expect(err).toBeInstanceOf(SusuDigitalError);
        expect(err.statusCode).toBe(418);
    });

    it('throws NetworkError on fetch TypeError', async () => {
        mockFetchNetworkError('Failed to fetch');
        const client = makeClient();
        await expect(client.get('/customers')).rejects.toBeInstanceOf(NetworkError);
    });
});

describe('HttpClient – retry logic', () => {
    it('retries on 500 up to maxRetries', async () => {
        global.fetch = jest.fn()
            .mockResolvedValueOnce({
                ok: false, status: 500, statusText: 'Internal Error',
                headers: { get: () => null },
                json: () => Promise.resolve({ message: 'error', code: 'SERVER_ERROR' }),
            })
            .mockResolvedValueOnce({
                ok: false, status: 500, statusText: 'Internal Error',
                headers: { get: () => null },
                json: () => Promise.resolve({ message: 'error', code: 'SERVER_ERROR' }),
            })
            .mockResolvedValueOnce({
                ok: true, status: 200,
                headers: { get: () => null },
                json: () => Promise.resolve({ id: 'cust_123' }),
            }) as jest.Mock;

        // Use very short delays for testing
        const client = makeClient({ retryAttempts: 3 });
        // Patch delay to avoid slow tests
        const result = await client.get('/customers/cust_123');
        expect(result).toEqual({ id: 'cust_123' });
        expect((global.fetch as jest.Mock).mock.calls).toHaveLength(3);
    }, 10_000);

    it('does NOT retry on 401 (non-retryable)', async () => {
        mockFetchOnce(401, { message: 'Unauthorized', code: 'AUTH_FAILED' });
        const client = makeClient({ retryAttempts: 3 });
        await expect(client.get('/customers')).rejects.toBeInstanceOf(AuthenticationError);
        expect((global.fetch as jest.Mock).mock.calls).toHaveLength(1);
    });

    it('does NOT retry on 404 (non-retryable)', async () => {
        mockFetchOnce(404, { message: 'Not found' });
        const client = makeClient({ retryAttempts: 3 });
        await expect(client.get('/customers/bad')).rejects.toBeInstanceOf(NotFoundError);
        expect((global.fetch as jest.Mock).mock.calls).toHaveLength(1);
    });
});
