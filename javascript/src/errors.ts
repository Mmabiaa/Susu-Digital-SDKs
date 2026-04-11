/**
 * Exception hierarchy for the Susu Digital JavaScript/TypeScript SDK.
 *
 * All exceptions inherit from {@link SusuDigitalError}, allowing callers to
 * catch either specific exceptions or the base class.
 *
 * Exception Hierarchy:
 *   SusuDigitalError
 *   ├── AuthenticationError   (HTTP 401 / 403)
 *   ├── ValidationError       (HTTP 400 / 422)
 *   ├── NotFoundError         (HTTP 404)
 *   ├── RateLimitError        (HTTP 429)
 *   ├── ServerError           (HTTP 5xx)
 *   ├── NetworkError          (connection / timeout)
 *   └── WebhookSignatureError (bad webhook HMAC)
 */

export class SusuDigitalError extends Error {
    readonly code: string;
    readonly requestId?: string;
    readonly statusCode?: number;
    readonly retryable: boolean;
    readonly details?: unknown;

    constructor(
        message: string,
        options: {
            code?: string;
            requestId?: string;
            statusCode?: number;
            retryable?: boolean;
            details?: unknown;
        } = {},
    ) {
        super(message);
        this.name = 'SusuDigitalError';
        this.code = options.code ?? 'UNKNOWN_ERROR';
        this.requestId = options.requestId;
        this.statusCode = options.statusCode;
        this.retryable = options.retryable ?? false;
        this.details = options.details;

        // Ensure proper prototype chain for instanceof checks in transpiled code
        Object.setPrototypeOf(this, new.target.prototype);
    }

    override toString(): string {
        return `${this.name}(code=${this.code}, message=${this.message}, requestId=${this.requestId ?? 'N/A'})`;
    }
}

/**
 * Raised when API authentication fails (HTTP 401 / 403).
 * Usually indicates an invalid or missing API key.
 */
export class AuthenticationError extends SusuDigitalError {
    constructor(message = 'Authentication failed', options: ConstructorParameters<typeof SusuDigitalError>[1] = {}) {
        super(message, {
            code: 'AUTH_FAILED',
            statusCode: 401,
            retryable: false,
            ...options,
        });
        this.name = 'AuthenticationError';
        Object.setPrototypeOf(this, AuthenticationError.prototype);
    }
}

/**
 * Raised when the request payload fails validation (HTTP 400 / 422).
 */
export class ValidationError extends SusuDigitalError {
    readonly fieldErrors: Record<string, string[]>;

    constructor(
        message = 'Validation failed',
        options: ConstructorParameters<typeof SusuDigitalError>[1] & { fieldErrors?: Record<string, string[]> } = {},
    ) {
        const { fieldErrors, ...rest } = options;
        super(message, {
            code: 'VALIDATION_ERROR',
            statusCode: 422,
            retryable: false,
            ...rest,
        });
        this.name = 'ValidationError';
        this.fieldErrors = fieldErrors ?? {};
        Object.setPrototypeOf(this, ValidationError.prototype);
    }
}

/**
 * Raised when a requested resource cannot be found (HTTP 404).
 */
export class NotFoundError extends SusuDigitalError {
    constructor(message = 'Resource not found', options: ConstructorParameters<typeof SusuDigitalError>[1] = {}) {
        super(message, {
            code: 'NOT_FOUND',
            statusCode: 404,
            retryable: false,
            ...options,
        });
        this.name = 'NotFoundError';
        Object.setPrototypeOf(this, NotFoundError.prototype);
    }
}

/**
 * Raised when the client is rate-limited (HTTP 429).
 */
export class RateLimitError extends SusuDigitalError {
    readonly retryAfter: number;

    constructor(
        message = 'Rate limit exceeded',
        options: ConstructorParameters<typeof SusuDigitalError>[1] & { retryAfter?: number } = {},
    ) {
        const { retryAfter, ...rest } = options;
        super(message, {
            code: 'RATE_LIMITED',
            statusCode: 429,
            retryable: true,
            ...rest,
        });
        this.name = 'RateLimitError';
        this.retryAfter = retryAfter ?? 60;
        Object.setPrototypeOf(this, RateLimitError.prototype);
    }
}

/**
 * Raised when the Susu Digital API returns a 5xx error.
 */
export class ServerError extends SusuDigitalError {
    constructor(message = 'Server error', options: ConstructorParameters<typeof SusuDigitalError>[1] = {}) {
        super(message, {
            code: 'SERVER_ERROR',
            statusCode: 500,
            retryable: true,
            ...options,
        });
        this.name = 'ServerError';
        Object.setPrototypeOf(this, ServerError.prototype);
    }
}

/**
 * Raised when a network or transport error occurs (no connection, timeout, etc.).
 */
export class NetworkError extends SusuDigitalError {
    constructor(message = 'Network error', options: ConstructorParameters<typeof SusuDigitalError>[1] = {}) {
        super(message, {
            code: 'NETWORK_ERROR',
            retryable: true,
            ...options,
        });
        this.name = 'NetworkError';
        Object.setPrototypeOf(this, NetworkError.prototype);
    }
}

/**
 * Raised when a webhook payload signature cannot be verified.
 */
export class WebhookSignatureError extends SusuDigitalError {
    constructor(message = 'Webhook signature verification failed', options: ConstructorParameters<typeof SusuDigitalError>[1] = {}) {
        super(message, {
            code: 'WEBHOOK_SIGNATURE_INVALID',
            retryable: false,
            ...options,
        });
        this.name = 'WebhookSignatureError';
        Object.setPrototypeOf(this, WebhookSignatureError.prototype);
    }
}
