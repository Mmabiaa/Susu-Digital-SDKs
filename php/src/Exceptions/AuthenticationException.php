<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/**
 * Raised when API authentication fails (HTTP 401 / 403).
 *
 * Usually indicates an invalid or missing API key.
 */
class AuthenticationException extends SusuDigitalException
{
    public function __construct(
        string $message = 'Authentication failed',
        string $sdkCode = 'AUTH_FAILED',
        ?string $requestId = null,
        mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: $sdkCode,
            requestId: $requestId,
            statusCode: 401,
            retryable: false,
            details: $details,
            previous: $previous,
        );
    }
}
