<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Sanitizer;

use Throwable;

interface SanitizerInterface
{
    public function sanitizeErrorMessage(string $errorMessage): string;

    public function includeException(Throwable $exception): bool;

    /**
     * @param mixed[] $backtrace
     *
     * @return mixed[]
     */
    public function sanitizeBackTrace(array $backtrace): array;

    /**
     * @param mixed[] $server
     *
     * @return mixed[]
     */
    public function sanitizeServer(array $server): array;

    /**
     * @param mixed[] $get
     *
     * @return mixed[]
     */
    public function sanitizeGet(array $get): array;

    /**
     * @param mixed[] $post
     *
     * @return mixed[]
     */
    public function sanitizePost(array $post): array;

    /**
     * @param mixed[] $cookie
     *
     * @return mixed[]
     */
    public function sanitizeCookie(array $cookie): array;

    /**
     * @param mixed[] $env
     *
     * @return mixed[]
     */
    public function sanitizeEnv(array $env): array;
}
