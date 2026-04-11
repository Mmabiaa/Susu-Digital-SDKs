<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/** Raised when the Susu Digital API returns a 5xx error. */
class ServerException extends SusuDigitalException
{
    public function __construct(
        string $message = 'Server error',
        string $sdkCode = 'SERVER_ERROR',
        ?string $requestId = null,
        ?int $statusCode = 500,
        mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: $sdkCode,
            requestId: $requestId,
            statusCode: $statusCode,
            retryable: true,
            details: $details,
            previous: $previous,
        );
    }
}
