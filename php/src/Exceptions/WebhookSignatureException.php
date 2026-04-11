<?php

declare(strict_types=1);

namespace SusuDigital\Exceptions;

/** Raised when a webhook payload signature cannot be verified. */
class WebhookSignatureException extends SusuDigitalException
{
    public function __construct(
        string $message = 'Webhook signature verification failed',
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: $message,
            sdkCode: 'WEBHOOK_SIGNATURE_INVALID',
            requestId: null,
            statusCode: null,
            retryable: false,
            details: null,
            previous: $previous,
        );
    }
}
