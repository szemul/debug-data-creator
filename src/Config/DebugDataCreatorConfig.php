<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Config;

use Szemul\DebugDataCreator\Sanitizer\SanitizerInterface;

class DebugDataCreatorConfig
{
    protected bool $traceEnabled     = true;
    protected bool $exceptionEnabled = true;
    protected bool $serverEnabled    = false;
    protected bool $getEnabled       = true;
    protected bool $postEnabled      = false;
    protected bool $cookieEnabled    = false;
    protected bool $envEnabled       = false;

    /** @var SanitizerInterface[] */
    protected array $sanitizers;

    public function __construct(SanitizerInterface ...$sanitizers)
    {
        $this->sanitizers = $sanitizers;
    }

    /** @return SanitizerInterface[] */
    public function getSanitizers(): array
    {
        return $this->sanitizers;
    }

    public function isExceptionEnabled(): bool
    {
        return $this->exceptionEnabled;
    }

    public function setExceptionEnabled(bool $exceptionEnabled): static
    {
        $this->exceptionEnabled = $exceptionEnabled;

        return $this;
    }

    public function isTraceEnabled(): bool
    {
        return $this->traceEnabled;
    }

    public function setTraceEnabled(bool $traceEnabled): static
    {
        $this->traceEnabled = $traceEnabled;

        return $this;
    }

    public function isServerEnabled(): bool
    {
        return $this->serverEnabled;
    }

    public function setServerEnabled(bool $serverEnabled): static
    {
        $this->serverEnabled = $serverEnabled;

        return $this;
    }

    public function isGetEnabled(): bool
    {
        return $this->getEnabled;
    }

    public function setGetEnabled(bool $getEnabled): static
    {
        $this->getEnabled = $getEnabled;

        return $this;
    }

    public function isPostEnabled(): bool
    {
        return $this->postEnabled;
    }

    public function setPostEnabled(bool $postEnabled): static
    {
        $this->postEnabled = $postEnabled;

        return $this;
    }

    public function isCookieEnabled(): bool
    {
        return $this->cookieEnabled;
    }

    public function setCookieEnabled(bool $cookieEnabled): static
    {
        $this->cookieEnabled = $cookieEnabled;

        return $this;
    }

    public function isEnvEnabled(): bool
    {
        return $this->envEnabled;
    }

    public function setEnvEnabled(bool $envEnabled): static
    {
        $this->envEnabled = $envEnabled;

        return $this;
    }
}
