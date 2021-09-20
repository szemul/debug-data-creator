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
            $data = $this->getExceptionData($exception);
            $file->fwrite("----- Exception -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizeTrace($data)); //@phpstan-ignore-line
            $file->fwrite("\n\n");
        }

        if (null !== $backTrace && $this->config->isTraceEnabled()) {
            $file->fwrite("----- Debug backtrace -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizeTrace($backTrace));
            $file->fwrite("\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Server -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizeServer($_SERVER));
            $file->fwrite("\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Get -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizeGet($_GET));
            $file->fwrite("\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Post -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizePost($_POST));
            $file->fwrite("\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Cookie -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizeCookie($_COOKIE));
            $file->fwrite("\n\n");
        }

        if ($this->config->isServerEnabled()) {
            $file->fwrite("----- Env -----\n\n");
            $this->varDumpHelper->varDumpToFile($file, $this->sanitizeEnv($_ENV));
            $file->fwrite("\n\n");
        }
    }

    /**
     * @param int[] $processedExceptions
     *
     * @return array<string,mixed>|string|null
     */
    protected function getExceptionData(?Throwable $exception, array &$processedExceptions = []): array|string|null
    {
        if (null === $exception) {
            return null;
        }

        $objectId = spl_object_id($exception);

        if (in_array($objectId, $processedExceptions)) {
            return '*** RECURSION ***';
        }

        $processedExceptions[] = $objectId;

        $data = [
            'exceptionClass' => get_class($exception),
            'message'        => $exception->getMessage(),
            'code'           => $exception->getCode(),
            'file'           => $exception->getFile(),
            'line'           => $exception->getLine(),
            'trace'          => $this->sanitizeTrace($exception->getTrace()),
            'previous'       => $this->getExceptionData($exception->getPrevious(), $processedExceptions),
        ];

        $reflection = new \ReflectionObject($exception);

        $builtInMethods = [
            'getMessage',
            'getCode',
            'getFile',
            'getLine',
            'getPrevious',
            'getTrace',
            'getTraceAsString',
        ];

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            $methodName = $method->getName();
            if (!str_starts_with($methodName, 'get') || in_array($methodName, $builtInMethods)) {
                continue;
            }

            $value = $exception->$methodName();

            if ($value instanceof Throwable) {
                $value = $this->getExceptionData($value, $processedExceptions);
            }

            $data[$methodName] = $value;
        }

        return $data;
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
     * @param array<int,array<string,mixed>> $backTrace
     *
     * @return array<int,array<string,mixed>>
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
