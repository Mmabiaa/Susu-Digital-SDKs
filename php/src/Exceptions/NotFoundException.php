<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/** Raised when a requested resource cannot be found (HTTP 404). */
class NotFoundException extends SusuDigitalException
{
    public function __construct(
        string $message = 'Resource not found',
        string $sdkCode = 'NOT_FOUND',
        ?string $requestId = null,
        mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: $sdkCode,
            requestId: $requestId,
            statusCode: 404,
            retryable: false,
            details: $details,
            previous: $previous,
        );
    }
}
