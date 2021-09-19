<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\FileHandler;

use SplFileObject;
use Szemul\DebugDataCreator\Exception\FileHandler\LogDirCreationFailedException;
use Szemul\DebugDataCreator\Exception\FileHandler\LogDirIsNotADirectoryException;
use Szemul\DebugDataCreator\Exception\FileHandler\LogDirIsNotWritableException;

class FileHandler implements FileHandlerInterface
{
    protected string $logDir;

    public function __construct(
        string $logDir,
        int $directoryCreateMode = 0755,
        protected string $fileNameSuffix = '.log',
    ) {
        $this->logDir = rtrim($logDir, '/') . '/';

        if (file_exists($this->logDir)) {
            if (!is_dir($this->logDir)) {
                throw new LogDirIsNotADirectoryException($this->logDir);
            }
            if (!is_writable($this->logDir)) {
                throw new LogDirIsNotWritableException($this->logDir);
            }
        } else {
            if (!mkdir($this->logDir, $directoryCreateMode, true)) {
                throw new LogDirCreationFailedException($this->logDir);
            }

            chmod($this->logDir, $directoryCreateMode);
        }
    }

    public function doesLogFileExist(string $errorId): bool
    {
        $filePath = $this->getLogFilePath($errorId);

        return file_exists($filePath) && filesize($filePath) > 0;
    }

    public function getLogFileObject(string $errorId): SplFileObject
    {
        return new SplFileObject($this->getLogFilePath($errorId), 'w+');
    }

    protected function getLogFilePath(string $errorId): string
    {
        return $this->logDir . $errorId . $this->fileNameSuffix;
    }
}
