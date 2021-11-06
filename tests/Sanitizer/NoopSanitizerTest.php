<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Test\Sanitizer;

use RuntimeException;
use Szemul\DebugDataCreator\Sanitizer\NoopSanitizer;
use PHPUnit\Framework\TestCase;

class NoopSanitizerTest extends TestCase
{
    private const TEST_ARRAY = [
        'foo' => 'bar',
    ];

    private NoopSanitizer $sut;

    protected function setUp(): void
    {
        $this->sut = new NoopSanitizer();
    }

    public function testIncludeException(): void
    {
        $this->assertTrue($this->sut->includeException(new RuntimeException()));
    }

    public function testSanitizeBackTrace(): void
    {
        $backtrace = debug_backtrace();
        $this->assertSame($backtrace, $this->sut->sanitizeBackTrace($backtrace));
    }

    public function testSanitizeCookie(): void
    {
        $this->assertSame(self::TEST_ARRAY, $this->sut->sanitizeCookie(self::TEST_ARRAY));
    }

    public function testSanitizeEnv(): void
    {
        $this->assertSame(self::TEST_ARRAY, $this->sut->sanitizeEnv(self::TEST_ARRAY));
    }

    public function testSanitizeErrorMessage(): void
    {
        $this->assertSame('test message', $this->sut->sanitizeErrorMessage('test message'));
    }

    public function testSanitizeGet(): void
    {
        $this->assertSame(self::TEST_ARRAY, $this->sut->sanitizeGet(self::TEST_ARRAY));
    }

    public function testSanitizePost(): void
    {
        $this->assertSame(self::TEST_ARRAY, $this->sut->sanitizePost(self::TEST_ARRAY));
    }

    public function testSanitizeServer(): void
    {
        $this->assertSame(self::TEST_ARRAY, $this->sut->sanitizeServer(self::TEST_ARRAY));
    }
}
