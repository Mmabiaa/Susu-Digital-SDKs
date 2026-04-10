/**
 * Low-level HTTP client.
 *
 * Responsibilities:
 * - Injects authentication headers on every request.
 * - Implements automatic retry with exponential back-off.
 * - Raises domain-specific errors on non-2xx responses.
 */

import {
    AuthenticationError,
    NetworkError,
    NotFoundError,
    RateLimitError,
    ServerError,
    SusuDigitalError,
    ValidationError,
} from './errors.js';
import type { SusuDigitalClientConfig } from './types.js';
import { VERSION, SDK_NAME, SDK_LANGUAGE } from './version.js';

// Version constants are imported from ./version.js

const BASE_URLS: Record<string, string> = {
    production: 'https://susu-digital.onrender.com',
    sandbox: 'https://api-sandbox.susudigital.app/v1',
};

const RETRYABLE_STATUS_CODES = new Set([429, 500, 502, 503, 504]);
const DEFAULT_TIMEOUT_MS = 30_000;
const DEFAULT_MAX_RETRIES = 3;

// ---------------------------------------------------------------------------
// Internal helpers
// ---------------------------------------------------------------------------

function buildHeaders(
    apiKey: string,
    organization?: string,
    custom: Record<string, string> = {},
): Record<string, string> {
    const headers: Record<string, string> = {
        Authorization: `Bearer ${apiKey}`,
        'Content-Type': 'application/json',
        Accept: 'application/json',
        'User-Agent': `${SDK_NAME}/${VERSION}`,
        'X-SDK-Version': VERSION,
        'X-SDK-Language': SDK_LANGUAGE,
    };
    if (organization) {
        headers['X-Organization-ID'] = organization;
    }
    return { ...headers, ...custom };
}

function parseErrorBody(body: Record<string, unknown>): {
    message: string;
    code: string;
    details: unknown;
    fieldErrors: Record<string, string[]>;
} {
    return {
        message: String(body['message'] ?? body['error'] ?? 'An error occurred'),
        code: String(body['code'] ?? 'UNKNOWN_ERROR'),
        details: body['details'],
        fieldErrors: (body['field_errors'] ?? body['errors'] ?? {}) as Record<string, string[]>,
    };
}

async function parseResponse(response: Response, requestId?: string): Promise<Record<string, unknown>> {
    if (response.ok) {
        if (response.status === 204 || response.headers.get('content-length') === '0') {
            return {};
        }
        try {
            return (await response.json()) as Record<string, unknown>;
        } catch {
            return {};
        }
    }

    let body: Record<string, unknown> = {};
    try {
        body = (await response.json()) as Record<string, unknown>;
    } catch {
        body = { message: response.statusText || 'Unknown error' };
    }

    const { message, code, details, fieldErrors } = parseErrorBody(body);
    const rid = requestId ?? response.headers.get('X-Request-ID') ?? undefined;

    switch (response.status) {
        case 401:
        case 403:
            throw new AuthenticationError(message, { code, requestId: rid, details });

        case 404:
            throw new NotFoundError(message, { code, requestId: rid, details });

        case 429: {
            const retryAfter = parseInt(response.headers.get('Retry-After') ?? '60', 10);
            throw new RateLimitError(message, { code, requestId: rid, details, retryAfter });
        }

        case 400:
        case 422:
            throw new ValidationError(message, { code, requestId: rid, details, fieldErrors });

        default:
            if (response.status >= 500) {
                throw new ServerError(message, { code, statusCode: response.status, requestId: rid, details });
            }
            throw new SusuDigitalError(message, { code, statusCode: response.status, requestId: rid, details });
    }
}

function isRetryable(err: unknown): boolean {
    if (err instanceof RateLimitError || err instanceof ServerError || err instanceof NetworkError) {
        return err.retryable;
    }
    return false;
}

function backoffDelay(attempt: number, baseMs = 500, capMs = 30_000): number {
    const delay = Math.min(baseMs * Math.pow(2, attempt), capMs);
    // ±25% jitter
    return delay * (0.75 + Math.random() * 0.5);
}

function sleep(ms: number): Promise<void> {
    return new Promise<void>((resolve) => setTimeout(resolve, ms));
}

// ---------------------------------------------------------------------------
// HttpClient
// ---------------------------------------------------------------------------

export class HttpClient {
    private readonly baseUrl: string;
    private readonly headers: Record<string, string>;
    private readonly maxRetries: number;
    private readonly timeoutMs: number;
    private readonly enableLogging: boolean;

    constructor(config: SusuDigitalClientConfig) {
        const env = config.environment ?? 'sandbox';
        this.baseUrl = config.baseUrl ?? BASE_URLS[env] ?? (BASE_URLS.sandbox as string);
        this.headers = buildHeaders(config.apiKey, config.organization, config.customHeaders);
        this.maxRetries = config.retryAttempts ?? DEFAULT_MAX_RETRIES;
        this.timeoutMs = (config.timeout ?? 30) * 1000;
        this.enableLogging = config.enableLogging ?? false;
    }

    async get<T = Record<string, unknown>>(
        path: string,
        params?: Record<string, unknown>,
    ): Promise<T> {
        return this.request<T>('GET', path, { params });
    }

    async post<T = Record<string, unknown>>(
        path: string,
        body?: Record<string, unknown>,
    ): Promise<T> {
        return this.request<T>('POST', path, { body });
    }

    async put<T = Record<string, unknown>>(
        path: string,
        body?: Record<string, unknown>,
    ): Promise<T> {
        return this.request<T>('PUT', path, { body });
    }

    async patch<T = Record<string, unknown>>(
        path: string,
        body?: Record<string, unknown>,
    ): Promise<T> {
        return this.request<T>('PATCH', path, { body });
    }

    async delete<T = Record<string, unknown>>(path: string): Promise<T> {
        return this.request<T>('DELETE', path);
    }

    // --------------------------------------------------------------------------

    private async request<T>(
        method: string,
        path: string,
        options: {
            params?: Record<string, unknown>;
            body?: Record<string, unknown>;
        } = {},
    ): Promise<T> {
        const correlationId = crypto.randomUUID();

        let url = `${this.baseUrl}${path}`;
        if (options.params) {
            const qs = new URLSearchParams();
            for (const [key, val] of Object.entries(options.params)) {
                if (val !== undefined && val !== null) {
                    qs.append(key, String(val));
                }
            }
            const qsStr = qs.toString();
            if (qsStr) url += `?${qsStr}`;
        }

        let lastError: unknown;

        for (let attempt = 0; attempt <= this.maxRetries; attempt++) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), this.timeoutMs);

            try {
                if (this.enableLogging) {
                    // eslint-disable-next-line no-console
                    console.debug('[SusuDigital SDK]', { method, path: url, attempt: attempt + 1, correlationId });
                }

                const response = await fetch(url, {
                    method,
                    headers: {
                        ...this.headers,
                        'X-Idempotency-Key': correlationId,
                    },
                    body: options.body !== undefined ? JSON.stringify(options.body) : undefined,
                    signal: controller.signal,
                });

                clearTimeout(timer);

                const result = await parseResponse(response, correlationId);

                if (this.enableLogging) {
                    console.debug('[SusuDigital SDK] Response', { statusCode: response.status, correlationId });
                }

                return result as T;
            } catch (err) {
                clearTimeout(timer);

                // Wrap abort / network errors
                if (err instanceof DOMException && err.name === 'AbortError') {
                    lastError = new NetworkError(`Request timed out after ${this.timeoutMs}ms`);
                } else if (err instanceof TypeError && String(err.message).includes('fetch')) {
                    lastError = new NetworkError(String(err.message));
                } else {
                    lastError = err;
                }

                if (attempt < this.maxRetries && isRetryable(lastError)) {
                    const delay =
                        lastError instanceof RateLimitError
                            ? lastError.retryAfter * 1000
                            : backoffDelay(attempt);

                    if (this.enableLogging) {
                        console.warn('[SusuDigital SDK] Retrying', {
                            attempt: attempt + 1,
                            delayMs: Math.round(delay),
                            correlationId,
                        });
                    }

                    await sleep(delay);
                    continue;
                }

                throw lastError;
            }
        }

        throw lastError ?? new SusuDigitalError('Maximum retries exceeded');
    }
}
