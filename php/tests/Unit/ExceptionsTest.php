<?php

declare(strict_types=1);

namespace SusuDigital\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SusuDigital\Exceptions\AuthenticationException;
use SusuDigital\Exceptions\NetworkException;
use SusuDigital\Exceptions\NotFoundException;
use SusuDigital\Exceptions\RateLimitException;
use SusuDigital\Exceptions\ServerException;
use SusuDigital\Exceptions\SusuDigitalException;
use SusuDigital\Exceptions\ValidationException;
use SusuDigital\Exceptions\WebhookSignatureException;

final class ExceptionsTest extends TestCase
{
    public function test_base_exception_stores_all_fields(): void
    {
        $exc = new SusuDigitalException(
            message: 'something went wrong',
            sdkCode: 'MY_CODE',
            requestId: 'req_123',
            statusCode: 418,
            retryable: true,
            details: ['extra' => 'data'],
        );

        $this->assertSame('something went wrong', $exc->getMessage());
        $this->assertSame('MY_CODE', $exc->getSdkCode());
        $this->assertSame('req_123', $exc->getRequestId());
        $this->assertSame(418, $exc->getStatusCode());
        $this->assertTrue($exc->isRetryable());
        $this->assertSame(['extra' => 'data'], $exc->getDetails());
    }

    public function test_authentication_exception_defaults(): void
    {
        $exc = new AuthenticationException();

        $this->assertSame(401, $exc->getStatusCode());
        $this->assertSame('AUTH_FAILED', $exc->getSdkCode());
        $this->assertFalse($exc->isRetryable());
        $this->assertInstanceOf(SusuDigitalException::class, $exc);
    }

    public function test_validation_exception_carries_field_errors(): void
    {
        $exc = new ValidationException(
            fieldErrors: ['phone' => ['Invalid format']],
        );

        $this->assertSame(422, $exc->getStatusCode());
        $this->assertSame('VALIDATION_ERROR', $exc->getSdkCode());
        $this->assertSame(['phone' => ['Invalid format']], $exc->getFieldErrors());
        $this->assertFalse($exc->isRetryable());
    }

    public function test_not_found_exception_defaults(): void
    {
        $exc = new NotFoundException();
        $this->assertSame(404, $exc->getStatusCode());
        $this->assertFalse($exc->isRetryable());
    }

    public function test_rate_limit_exception_carries_retry_after(): void
    {
        $exc = new RateLimitException(retryAfter: 30);
        $this->assertSame(429, $exc->getStatusCode());
        $this->assertSame(30, $exc->getRetryAfter());
        $this->assertTrue($exc->isRetryable());
    }

    public function test_server_exception_is_retryable(): void
    {
        $exc = new ServerException(statusCode: 503);
        $this->assertSame(503, $exc->getStatusCode());
        $this->assertTrue($exc->isRetryable());
    }

    public function test_network_exception_is_retryable(): void
    {
        $exc = new NetworkException('Connection refused');
        $this->assertSame('NETWORK_ERROR', $exc->getSdkCode());
        $this->assertTrue($exc->isRetryable());
        $this->assertNull($exc->getStatusCode());
    }

    public function test_webhook_signature_exception(): void
    {
        $exc = new WebhookSignatureException();
        $this->assertSame('WEBHOOK_SIGNATURE_INVALID', $exc->getSdkCode());
        $this->assertFalse($exc->isRetryable());
    }

    public function test_to_string_includes_class_name_and_code(): void
    {
        $exc = new AuthenticationException('Bad key', requestId: 'req_abc');
        $str = (string) $exc;

        $this->assertStringContainsString('AuthenticationException', $str);
        $this->assertStringContainsString('Bad key', $str);
        $this->assertStringContainsString('req_abc', $str);
    }

    public function test_exception_hierarchy_is_correct(): void
    {
        $this->assertInstanceOf(SusuDigitalException::class, new AuthenticationException());
        $this->assertInstanceOf(SusuDigitalException::class, new ValidationException());
        $this->assertInstanceOf(SusuDigitalException::class, new NotFoundException());
        $this->assertInstanceOf(SusuDigitalException::class, new RateLimitException());
        $this->assertInstanceOf(SusuDigitalException::class, new ServerException());
        $this->assertInstanceOf(SusuDigitalException::class, new NetworkException());
        $this->assertInstanceOf(SusuDigitalException::class, new WebhookSignatureException());
    }
}
