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
        $logDir = rtrim($logDir, '/');

        if (file_exists($logDir)) {
            if (!is_dir($logDir)) {
                throw new LogDirIsNotADirectoryException($logDir);
            }
            if (!is_writable($logDir)) {
                throw new LogDirIsNotWritableException($logDir);
            }
        } else {
            if (!@mkdir($logDir, $directoryCreateMode, true)) {
                throw new LogDirCreationFailedException($logDir);
            }

            chmod($logDir, $directoryCreateMode);
        }

        $this->logDir = $logDir . '/';
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
