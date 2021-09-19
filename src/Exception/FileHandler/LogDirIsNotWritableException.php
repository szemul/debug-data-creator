<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Exception\FileHandler;

use JetBrains\PhpStorm\Pure;

class LogDirIsNotWritableException extends Exception
{
    #[Pure]
    public function __construct(string $logDir)
    {
        parent::__construct('The log directory ' . $logDir . ' is not writable');
    }
}
