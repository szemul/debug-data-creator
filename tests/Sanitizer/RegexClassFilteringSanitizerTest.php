<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Test\Sanitizer;

use Mockery;
use stdClass;
use PHPUnit\Framework\TestCase;
use Szemul\DebugDataCreator\Sanitizer\RegexClassFilteringSanitizer;

class RegexClassFilteringSanitizerTest extends TestCase
{
    public function testSanitizeBackTrace(): void
    {
        $class1 = Mockery::mock(stdClass::class);
        $class2 = Mockery::mock(TestCase::class);

        $trace = [
            [
                'file'     => 'foo',
                'line'     => 123,
                'function' => 'foo',
                'class'    => $class1,
            ],
            [
                'file'     => 'bar',
                'line'     => 234,
                'function' => 'bar',
                'class'    => $class2,
            ],
        ];

        $expected = [
            [
                'file'     => 'foo',
                'line'     => 123,
                'function' => 'foo',
                'class'    => '*** Removed by blacklist. Class of ' . get_class($class1) . ' ***',
            ],
            [
                'file'     => 'bar',
                'line'     => 234,
                'function' => 'bar',
                'class'    => $class2,
            ],
        ];

        $actual = (new RegexClassFilteringSanitizer(10, '/^.*std[cC]lass.*/'))->sanitizeBackTrace($trace);

        $this->assertSame($expected, $actual);
    }
}
