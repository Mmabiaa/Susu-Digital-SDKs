<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/**
 * Exception hierarchy for the Susu Digital PHP SDK.
 *
 * All SDK exceptions inherit from SusuDigitalException, allowing
 * callers to catch either specific exceptions or the base class.
 *
 * Hierarchy:
 *
 *   SusuDigitalException
 *   ├── AuthenticationException    (HTTP 401 / 403)
 *   ├── ValidationException        (HTTP 422 / 400 – field errors)
 *   ├── NotFoundException          (HTTP 404)
 *   ├── RateLimitException         (HTTP 429)
 *   ├── ServerException            (HTTP 5xx)
 *   ├── NetworkException           (connection / timeout)
 *   └── WebhookSignatureException  (bad webhook HMAC)
 */
class SusuDigitalException extends \RuntimeException
{
    protected string $sdkCode;
    protected ?string $requestId;
    protected ?int $statusCode;
    protected bool $retryable;
    protected mixed $details;

    public function __construct(
        string $message = 'An error occurred',
        string $sdkCode = 'UNKNOWN_ERROR',
        ?string $requestId = null,
        ?int $statusCode = null,
        bool $retryable = false,
        mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);

        $this->sdkCode   = $sdkCode;
        $this->requestId = $requestId;
        $this->statusCode = $statusCode;
        $this->retryable = $retryable;
        $this->details   = $details;
    }

    public function getSdkCode(): string
    {
        return $this->sdkCode;
    }

    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }

    public function getDetails(): mixed
    {
        return $this->details;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s(message=%s, code=%s, request_id=%s)',
            static::class,
            $this->getMessage(),
            $this->sdkCode,
            $this->requestId ?? 'null',
        );
    }
}
