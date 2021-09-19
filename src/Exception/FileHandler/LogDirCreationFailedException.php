<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Exception\FileHandler;

use JetBrains\PhpStorm\Pure;

class LogDirCreationFailedException extends Exception
{
    #[Pure]
    public function __construct(string $logDir)
    {
        parent::__construct('Failed to create log directory: ' . $logDir);
    }
}
