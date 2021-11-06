<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Test;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\LegacyMockInterface;
use Mockery\MockInterface;
use RuntimeException;
use SplFileObject;
use Szemul\DebugDataCreator\Config\DebugDataCreatorConfig;
use Szemul\DebugDataCreator\DebugDataCreator;
use PHPUnit\Framework\TestCase;
use Szemul\DebugDataCreator\FileHandler\FileHandlerInterface;
use Szemul\ErrorHandler\Helper\ErrorHandlerLevelConverter;
use Szemul\Helper\VarDumpHelper;

class DebugDataCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const SANITIZER_PREFIX                 = 'sanitized_';
    private const ERROR_ID                         = 'errorId';
    private const ERROR_LEVEL                      = E_USER_ERROR;
    private const ERROR_LEVEL_DESCRIPTION          = 'E_USER_ERROR';
    private const ERROR_MESSAGE                    = 'error message';
    private const ERROR_CODE                       = 10;
    private const LINE_NUMBER                      = 5;
    private const FILE                             = 'test.php';
    private const ERROR_EXPECTED_MESSAGE           = '[E_USER_ERROR(256)]: error message on line 5 in test.php';
    private const EXCEPTION_EXPECTED_MESSAGE       = '[E_EXCEPTION]: Unhandled RuntimeException: error message(10) on line 70 in ' . __FILE__;
    private const TEST_FILE_PATH                   = '/tmp/test';

    protected DebugDataCreator                                             $sut;
    protected DebugDataCreatorConfig                                       $config;
    protected FileHandlerInterface|MockInterface|LegacyMockInterface       $fileHandler;
    protected VarDumpHelper|MockInterface|LegacyMockInterface              $varDumpHelper;
    protected ErrorHandlerLevelConverter|MockInterface|LegacyMockInterface $levelConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->config         = new DebugDataCreatorConfig();
        $this->fileHandler    = Mockery::mock(FileHandlerInterface::class);
        $this->varDumpHelper  = Mockery::mock(VarDumpHelper::class);
        $this->levelConverter = Mockery::mock(ErrorHandlerLevelConverter::class);

        $this->sut = new DebugDataCreator(
            $this->config,
            $this->fileHandler, //@phpstan-ignore-line
            $this->varDumpHelper, //@phpstan-ignore-line
            //@phpstan-ignore-next-line
            $this->levelConverter,
        );
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if (file_exists(self::TEST_FILE_PATH)) {
            unlink(self::TEST_FILE_PATH);
        }
    }

    private function getException(): RuntimeException
    {
        return new RuntimeException(self::ERROR_MESSAGE, self::ERROR_CODE);
    }

    public function testHandleErrorWithExistingLogFileshouldDoNothing(): void
    {
        $this->expectLogFileExistenceChecked(true);

        $this->sut->handleError(
            self::ERROR_LEVEL,
            self::ERROR_MESSAGE,
            self::FILE,
            self::LINE_NUMBER,
            self::ERROR_ID,
            false,
            [],
        );
    }

    public function testHandleExceptionWithExistingLogFile_shouldDoNothing(): void
    {
        $this->expectLogFileExistenceChecked(true);

        $this->sut->handleException($this->getException(), self::ERROR_ID);
    }

    public function testHandleShutdownWithExistingLogFileshouldDoNothing(): void
    {
        $this->expectLogFileExistenceChecked(true);

        $this->sut->handleShutdown(
            self::ERROR_LEVEL,
            self::ERROR_MESSAGE,
            self::FILE,
            self::LINE_NUMBER,
            self::ERROR_ID,
        );
    }

    /**
     * @dataProvider getConfigValues
     * @backupGlobals
     */
    public function testHandleErrorWithNotExistingLogFile_shouldCreateDump(
        bool $cookie,
        bool $env,
        bool $exception,
        bool $get,
        bool $post,
        bool $server,
        bool $trace,
    ): void {
        $this->setGlobals();
        $this->setupConfig($cookie, $env, $exception, $get, $post, $server, $trace);
        $this->expectErrorLevelConverted();
        $this->expectLogFileExistenceChecked();
        $file = $this->expectLogFileObjectRetrieved();
        $this->expectDataWrittenToTraceFile($file, self::ERROR_EXPECTED_MESSAGE);

        $this->sut->handleError(
            self::ERROR_LEVEL,
            self::ERROR_MESSAGE,
            self::FILE,
            self::LINE_NUMBER,
            self::ERROR_ID,
            false,
            debug_backtrace(),
        );
    }

    /**
     * @dataProvider getConfigValues
     * @backupGlobals
     */
    public function testHandleExceptionWithNotExistingLogFile_shouldCreateDump(
        bool $cookie,
        bool $env,
        bool $exception,
        bool $get,
        bool $post,
        bool $server,
        bool $trace,
    ): void {
        $this->setGlobals();
        $this->setupConfig($cookie, $env, $exception, $get, $post, $server, $trace);
        $this->expectLogFileExistenceChecked();
        $file = $this->expectLogFileObjectRetrieved();
        $this->expectDataWrittenToTraceFile($file, self::EXCEPTION_EXPECTED_MESSAGE, true);

        $this->sut->handleException($this->getException(), self::ERROR_ID);
    }

    /**
     * @dataProvider getConfigValues
     * @backupGlobals
     */
    public function testHandleShutdownWithNotExistingLogFile_shouldCreateDump(
        bool $cookie,
        bool $env,
        bool $exception,
        bool $get,
        bool $post,
        bool $server,
        bool $trace,
    ): void {
        $this->setGlobals();
        $this->setupConfig($cookie, $env, $exception, $get, $post, $server, $trace);
        $this->expectErrorLevelConverted();
        $this->expectLogFileExistenceChecked();
        $file = $this->expectLogFileObjectRetrieved();
        $this->expectDataWrittenToTraceFile($file, self::ERROR_EXPECTED_MESSAGE);

        $this->sut->handleShutdown(
            self::ERROR_LEVEL,
            self::ERROR_MESSAGE,
            self::FILE,
            self::LINE_NUMBER,
            self::ERROR_ID,
        );
    }

    /**
     * @return array[]
     */
    public function getConfigValues(): array
    {
        return [
            [false, false, false, false, false, false, false],
            [true, false, false, false, false, false, false],
            [false, true, false, false, false, false, false],
            [false, false, true, false, false, false, false],
            [false, false, false, true, false, false, false],
            [false, false, false, false, true, false, false],
            [false, false, false, false, false, true, false],
            [false, false, false, false, false, false, true],
        ];
    }

    private function setupConfig(
        bool $cookie,
        bool $env,
        bool $exception,
        bool $get,
        bool $post,
        bool $server,
        bool $trace,
    ): void {
        $this->config->setCookieEnabled($cookie);
        $this->config->setEnvEnabled($env);
        $this->config->setExceptionEnabled($exception);
        $this->config->setGetEnabled($get);
        $this->config->setPostEnabled($post);
        $this->config->setServerEnabled($server);
        $this->config->setTraceEnabled($trace);
    }

    private function expectErrorLevelConverted(): void
    {
        //@phpstan-ignore-next-line
        $this->levelConverter->shouldReceive('getPhpErrorLevelDescription')
            ->once()
            ->with(self::ERROR_LEVEL)
            ->andReturn(self::ERROR_LEVEL_DESCRIPTION);
    }

    private function expectLogFileExistenceChecked(bool $exists = false): void
    {
        //@phpstan-ignore-next-line
        $this->fileHandler->shouldReceive('doesLogFileExist')
            ->once()
            ->with(self::ERROR_ID)
            ->andReturn($exists);
    }

    private function expectLogFileObjectRetrieved(): MockInterface|LegacyMockInterface
    {
        $mock = Mockery::mock(SplFileObject::class . '[fwrite]', [self::TEST_FILE_PATH, 'w']);

        //@phpstan-ignore-next-line
        $this->fileHandler->shouldReceive('getLogFileObject')
            ->once()
            ->with(self::ERROR_ID)
            ->andReturn($mock);

        return $mock;
    }

    private function expectDataWrittenToTraceFile(
        MockInterface|LegacyMockInterface $file,
        string $message,
        bool $isException = false,
    ): void {
        //@phpstan-ignore-next-line
        $file->shouldReceive('fwrite')
            ->once()
            ->with(self::ERROR_ID . ' ' . $message . "\n\n");

        $expectedBlockCount = 0;

        if ($this->config->isCookieEnabled()) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Cookie -----\n\n");
        }

        if ($this->config->isEnvEnabled()) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Env -----\n\n");
        }

        if ($this->config->isExceptionEnabled() && $isException) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Exception -----\n\n");
        }

        if ($this->config->isGetEnabled()) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Get -----\n\n");
        }

        if ($this->config->isPostEnabled()) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Post -----\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Server -----\n\n");
        }

        if ($this->config->isTraceEnabled() && !$isException) {
            $expectedBlockCount++;
            //@phpstan-ignore-next-line
            $file->shouldReceive('fwrite')
                ->once()
                ->with("----- Debug backtrace -----\n\n");
        }

        if (0 === $expectedBlockCount) {
            return;
        }

        //@phpstan-ignore-next-line
        $this->varDumpHelper->shouldReceive('varDumpToFile')
            ->with($file, Mockery::any());

        //@phpstan-ignore-next-line
        $file->shouldReceive('fwrite')
            ->times($expectedBlockCount)
            ->with("\n\n");
    }

    private function setGlobals()
    {
        $_GET = [
            'foo' => 'bar',
        ];
        $_POST = $_SERVER = $_COOKIE = $_ENV = $_GET;
    }
}
