<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Test\Sanitizer;

use Szemul\DebugDataCreator\Sanitizer\ServerAllowListSanitizer;
use PHPUnit\Framework\TestCase;

class ServerAllowListSanitizerTest extends TestCase
{
    private const SERVER = [
        'argc'              => 1,
        'argv'              => ['foo', 'bar'],
        'DB_PASSWORD'       => 'foo',
        'DB_USERNAME'       => 'bar',
        'HTTP_HOST'         => 'example.com',
        'HTTP_CONTENT_TYPE' => 'text/html',
        'HTTP_ACCEPT'       => 'text/html',
        'HTTP_X_API_KEY'    => 'test',
        'PHP_AUTH_PW'       => 'test',
        'REMOTE_ADDR'       => '127.0.0,1',

    ];

    public function testSanitizeServerWithDefaults(): void
    {
        $expected = [
            'REMOTE_ADDR' => '127.0.0,1',
        ];

        $this->assertSame($expected, (new ServerAllowListSanitizer())->sanitizeServer(self::SERVER));
    }

    public function testSanitizeServerWithHeadersAllowed(): void
    {
        $expected = [
            'HTTP_HOST'         => 'example.com',
            'HTTP_CONTENT_TYPE' => 'text/html',
            'HTTP_ACCEPT'       => 'text/html',
            'HTTP_X_API_KEY'    => 'test',
            'REMOTE_ADDR'       => '127.0.0,1',
        ];

        $this->assertSame($expected, (new ServerAllowListSanitizer(true))->sanitizeServer(self::SERVER));
    }

    public function testSanitizeServerWithMostHeadersAllowed(): void
    {
        $expected = [
            'HTTP_HOST'         => 'example.com',
            'HTTP_CONTENT_TYPE' => 'text/html',
            'HTTP_ACCEPT'       => 'text/html',
            'REMOTE_ADDR'       => '127.0.0,1',
        ];

        $this->assertSame(
            $expected,
            (new ServerAllowListSanitizer(true, bannedHeaders: ['X-Api-Key']))->sanitizeServer(self::SERVER),
        );
    }

    public function testSanitizeServerWithSensitiveServerKeysAllowed(): void
    {
        $expected = [
            'PHP_AUTH_PW' => 'test',
            'REMOTE_ADDR' => '127.0.0,1',
        ];

        $this->assertSame(
            $expected,
            (new ServerAllowListSanitizer(allowSensitiveServerKeys: true))->sanitizeServer(self::SERVER),
        );
    }

    public function testSanitizeServerWithArgsAllowed(): void
    {
        $expected = [
            'argc'        => 1,
            'argv'        => ['foo', 'bar'],
            'REMOTE_ADDR' => '127.0.0,1',
        ];

        $this->assertSame(
            $expected,
            (new ServerAllowListSanitizer(allowArgs: true))->sanitizeServer(self::SERVER),
        );
    }

    public function testSanitizeServerWithSpecificKeysAllowed(): void
    {
        $expected = [
            'DB_USERNAME' => 'bar',
            'REMOTE_ADDR' => '127.0.0,1',
        ];

        $this->assertSame(
            $expected,
            (new ServerAllowListSanitizer(allowedKeys: ['DB_USERNAME']))->sanitizeServer(self::SERVER),
        );
    }
}
