<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/** Raised when a network or transport error occurs (connection refused, timeout, TLS error). */
class NetworkException extends SusuDigitalException
{
    public function __construct(
        string $message = 'Network error',
        string $sdkCode = 'NETWORK_ERROR',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: $sdkCode,
            requestId: null,
            statusCode: null,
            retryable: true,
            details: null,
            previous: $previous,
        );
    }
}
