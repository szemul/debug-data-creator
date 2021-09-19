<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Exception\FileHandler;

use JetBrains\PhpStorm\Pure;

class LogDirIsNotADirectoryException extends Exception
{
    #[Pure]
    public function __construct(string $logDir)
    {
        parent::__construct($logDir . ' is not a directory');
    }
}
