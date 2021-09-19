<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\FileHandler;

use SplFileObject;

interface FileHandlerInterface
{
    public function doesLogFileExist(string $errorId): bool;

    public function getLogFileObject(string $errorId): SplFileObject;
}
