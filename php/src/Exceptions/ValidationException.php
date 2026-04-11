<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/**
 * Raised when request payload fails validation (HTTP 422 / 400).
 */
class ValidationException extends SusuDigitalException
{
    /** @var array<string, string[]> */
    private array $fieldErrors;

    /**
     * @param array<string, string[]> $fieldErrors
     */
    public function __construct(
        string $message = 'Validation failed',
        string $sdkCode = 'VALIDATION_ERROR',
        ?string $requestId = null,
        array $fieldErrors = [],
        mixed $details = null,
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: $sdkCode,
            requestId: $requestId,
            statusCode: 422,
            retryable: false,
            details: $details,
            previous: $previous,
        );

        $this->fieldErrors = $fieldErrors;
    }

    /**
     * @return array<string, string[]>
     */
    public function getFieldErrors(): array
    {
        return $this->fieldErrors;
    }
}
