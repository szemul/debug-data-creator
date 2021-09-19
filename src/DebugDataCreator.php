<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator;

use Szemul\DebugDataCreator\Config\DebugDataCreatorConfig;
use Szemul\DebugDataCreator\FileHandler\FileHandlerInterface;
use Szemul\DebugDataCreator\Sanitizer\SanitizerInterface;
use Szemul\ErrorHandler\Handler\ErrorHandlerInterface;
use Szemul\ErrorHandler\Helper\ErrorHandlerLevelConverter;
use Szemul\Helper\VarDumpHelper;
use Throwable;

class DebugDataCreator implements ErrorHandlerInterface
{
    public function __construct(
        protected DebugDataCreatorConfig $config,
        protected FileHandlerInterface $fileHandler,
        protected VarDumpHelper $varDumpHelper,
        protected ErrorHandlerLevelConverter $levelConverter,
    ) {
    }

    public function handleError(
        int $errorLevel,
        string $message,
        string $file,
        int $line,
        string $errorId,
        bool $isErrorFatal,
        array $backTrace = [],
    ): void {
        if ($this->fileHandler->doesLogFileExist($errorId)) {
            return;
        }

        $errorLevelDescription = $this->levelConverter->getPhpErrorLevelDescription($errorLevel);

        $errorMessage = '[' . $errorLevelDescription . '(' . $errorLevel . ')]: ' . $message . ' on line ' . $line
            . ' in ' . $file;

        $this->writeDumpData($errorId, $errorMessage, null, $backTrace);
    }

    public function handleException(Throwable $exception, string $errorId): void
    {
        if ($this->fileHandler->doesLogFileExist($errorId)) {
            return;
        }

        $errorMessage = '[' . ErrorHandlerLevelConverter::E_EXCEPTION_DESCRIPTION . ']: Unhandled '
            . get_class($exception) . ': ' . $exception->getMessage() . '(' . $exception->getCode() . ') on line '
            . $exception->getLine() . ' in ' . $exception->getFile();

        $this->writeDumpData($errorId, $errorMessage, $exception);
    }

    public function handleShutdown(int $errorLevel, string $message, string $file, int $line, string $errorId): void
    {
        $this->handleError($errorLevel, $message, $file, $line, $errorId, true, []);
    }

    /**
     * @param array[]|null $backTrace
     */
    protected function writeDumpData(
        string $errorId,
        string $errorMessage,
        ?Throwable $exception,
        ?array $backTrace = null,
    ): void {
        $file = $this->fileHandler->getLogFileObject($errorId);

        $file->fwrite($errorId . ' ' . $this->sanitizeErrorMessage($errorMessage) . "\n\n");

        if ($this->config->isExceptionEnabled() && $this->checkIfExceptionShouldBeIncluded($exception)) {
            $file->fwrite("----- Exception -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($exception) . "\n\n");
        }

        if (null !== $backTrace && $this->config->isTraceEnabled()) {
            $file->fwrite("----- Debug backtrace -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($this->sanitizeTrace($backTrace)) . "\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Server -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($this->sanitizeServer($_SERVER)) . "\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Get -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($this->sanitizeGet($_GET)) . "\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Post -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($this->sanitizePost($_POST)) . "\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Cookie -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($this->sanitizeCookie($_COOKIE)) . "\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Env -----\n\n");
            $file->fwrite($this->varDumpHelper->captureVarDumpToString($this->sanitizeEnv($_ENV)) . "\n\n");
        }
    }

    protected function sanitizeErrorMessage(string $errorMessage): string
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (string $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizeErrorMessage($carry),
            $errorMessage,
        );
    }

    /**
     * @param array<string,mixed> $backTrace
     *
     * @return array<string,mixed>
     */
    protected function sanitizeTrace(array $backTrace): array
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (array $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizeBackTrace($carry),
            $backTrace,
        );
    }

    /**
     * @param array<string,mixed> $server
     *
     * @return array<string,mixed>
     */
    protected function sanitizeServer(array $server): array
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (array $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizeServer($carry),
            $server,
        );
    }

    /**
     * @param array<string,mixed> $get
     *
     * @return array<string,mixed>
     */
    protected function sanitizeGet(array $get): array
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (array $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizeGet($carry),
            $get,
        );
    }

    /**
     * @param array<string,mixed> $post
     *
     * @return array<string,mixed>
     */
    protected function sanitizePost(array $post): array
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (array $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizePost($carry),
            $post,
        );
    }

    /**
     * @param array<string,mixed> $cookie
     *
     * @return array<string,mixed>
     */
    protected function sanitizeCookie(array $cookie): array
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (array $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizeCookie($carry),
            $cookie,
        );
    }

    /**
     * @param array<string,mixed> $env
     *
     * @return array<string,mixed>
     */
    protected function sanitizeEnv(array $env): array
    {
        return array_reduce(
            $this->config->getSanitizers(),
            fn (array $carry, SanitizerInterface $sanitizer) => $sanitizer->sanitizeEnv($carry),
            $env,
        );
    }

    protected function checkIfExceptionShouldBeIncluded(?Throwable $exception): bool
    {
        if (null === $exception) {
            return false;
        }

        return array_reduce(
            $this->config->getSanitizers(),
            fn (bool $carry, SanitizerInterface $sanitizer) => $carry && $sanitizer->includeException($exception),
            true,
        );
    }
}
