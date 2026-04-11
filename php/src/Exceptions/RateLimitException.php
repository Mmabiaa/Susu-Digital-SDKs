<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/** Raised when the client is rate-limited (HTTP 429). */
class RateLimitException extends SusuDigitalException
{
    private int $retryAfter;

    public function __construct(
        string $message = 'Rate limit exceeded',
        string $sdkCode = 'RATE_LIMITED',
        ?string $requestId = null,
        int $retryAfter = 60,
        mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: $sdkCode,
            requestId: $requestId,
            statusCode: 429,
            retryable: true,
            details: $details,
            previous: $previous,
        );

        $this->retryAfter = $retryAfter;
    }

    /** Number of seconds to wait before retrying. */
    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
