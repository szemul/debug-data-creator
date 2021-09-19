<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Sanitizer;

use Throwable;

/**
 * Debug data sanitizer that does nothing. It can be used as the base class of other sanitizers so they don't have to
 * implement methods for data that they don't want to sanitize.
 */
class NoopSanitizer implements SanitizerInterface
{
    public function sanitizeErrorMessage(string $errorMessage): string
    {
        return $errorMessage;
    }

    public function includeException(Throwable $exception): bool
    {
        return true;
    }

    public function sanitizeBackTrace(array $backtrace): array
    {
        return $backtrace;
    }

    public function sanitizeServer(array $server): array
    {
        return $server;
    }

    public function sanitizeGet(array $get): array
    {
        return $get;
    }

    public function sanitizePost(array $post): array
    {
        return $post;
    }

    public function sanitizeCookie(array $cookie): array
    {
        return $cookie;
    }

    public function sanitizeEnv(array $env): array
    {
        return $env;
    }
}
