<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Test\FileHandler;

use RuntimeException;
use Szemul\DebugDataCreator\Exception\FileHandler\LogDirCreationFailedException;
use Szemul\DebugDataCreator\Exception\FileHandler\LogDirIsNotADirectoryException;
use Szemul\DebugDataCreator\Exception\FileHandler\LogDirIsNotWritableException;
use Szemul\DebugDataCreator\FileHandler\FileHandler;
use PHPUnit\Framework\TestCase;

class FileHandlerTest extends TestCase
{
    private const PATH = '/tmp/file_handler_test';

    protected function setUp(): void
    {
        parent::setUp();

        if (!file_exists('/tmp') || !is_dir('/tmp') || !is_writable('/tmp')) {
            $this->markTestSkipped('No temp dir at /tmp or is not writable');
        }

        $this->cleanUpPath();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->cleanUpPath();
    }

    public function testConstructWithExistingDirectory_shouldComplete(): void
    {
        mkdir(self::PATH);

        new FileHandler(self::PATH);

        // Dummy assert
        $this->assertTrue(true);
    }

    public function testConstructWithNotExistingDirectory_shouldComplete(): void
    {
        new FileHandler(self::PATH);

        // Dummy assert
        $this->assertTrue(true);
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructWithExistingNonWritableDirectory_shouldThrow(): void
    {
        if (0 === getmyuid()) {
            posix_setuid(82);
        }
        $this->expectException(LogDirIsNotWritableException::class);

        mkdir(self::PATH, 0400);

        new FileHandler(self::PATH);
    }

    public function testConstructWithExistingFile_shouldThrow(): void
    {
        $this->expectException(LogDirIsNotADirectoryException::class);

        touch(self::PATH);

        new FileHandler(self::PATH);
    }

    /**
     * @runInSeparateProcess
     */
    public function testConstructWithNotExistingDirectoryThatCantBeCreated_shouldThrow(): void
    {
        if (0 === getmyuid()) {
            posix_setuid(82);
        }

        $this->expectException(LogDirCreationFailedException::class);

        mkdir(self::PATH, 0400);

        new FileHandler(self::PATH . '/test');
    }

    public function testDoesLogFileExistWithExistingFile_shouldReturnTrue(): void
    {
        $sut = new FileHandler(self::PATH);

        file_put_contents(self::PATH . '/test.log', 'test');

        $this->assertTrue($sut->doesLogFileExist('test'));
    }

    public function testDoesLogFileExistWithExisting0ByteFile_shouldReturnFalse(): void
    {
        $sut = new FileHandler(self::PATH);

        touch(self::PATH . '/test.log');

        $this->assertFalse($sut->doesLogFileExist('test'));
    }

    public function testDoesLogFileExistWithNotExistingFile_shouldReturnFalse(): void
    {
        $this->assertFalse((new FileHandler(self::PATH))->doesLogFileExist('test'));
    }

    public function testGetLogFileObject(): void
    {
        $file = (new FileHandler(self::PATH))->getLogFileObject('test');

        $this->assertSame(self::PATH . '/test.log', $file->getPathname());
        $this->assertSame(0, $file->getSize());
    }

    private function cleanUpPath(string $path = self::PATH): void
    {
        if (!file_exists($path)) {
            return;
        }

        if (is_dir($path)) {
            foreach (scandir($path) as $file) {
                if ('.' == $file || '..' == $file) {
                    continue;
                }

                $this->cleanUpPath($path . '/' . $file);
            }

            rmdir($path);
        } else {
            if (!unlink($path)) {
                throw new RuntimeException('Failed to delete test file');
            }
        }
    }
}
