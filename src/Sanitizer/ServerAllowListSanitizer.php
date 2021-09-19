<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Sanitizer;

class ServerAllowListSanitizer extends NoopSanitizer
{
    public const DEFAULT_SERVER_KEYS = [
        'PHP_SELF',
        'GATEWAY_INTERFACE',
        'SERVER_ADDR',
        'SERVER_NAME',
        'SERVER_SOFTWARE',
        'SERVER_PROTOCOL',
        'REQUEST_METHOD',
        'REQUEST_TIME',
        'REQUEST_TIME_FLOAT',
        'QUERY_STRING',
        'DOCUMENT_ROOT',
        'HTTP_PROXY',
        'HTTPS',
        'REMOTE_ADDR',
        'REMOTE_HOST',
        'REMOTE_PORT',
        'REMOTE_USER',
        'REDIRECT_REMOTE_USER',
        'SCRIPT_FILENAME',
        'SERVER_ADMIN',
        'SERVER_PORT',
        'SERVER_SIGNATURE',
        'PATH_TRANSLATED',
        'SCRIPT_NAME',
        'REQUEST_URI',
        'PHP_AUTH_USER',
        'AUTH_TYPE',
        'PATH_INFO',
        'ORIG_PATH_INFO',
    ];

    public const SENSITIVE_DEFAULT_SERVER_KEYS = [
        'PHP_AUTH_DIGEST',
        'PHP_AUTH_PW',
    ];

    public const ARG_SERVER_KEYS = [
        'argv',
        'argc',
    ];

    /**
     * @param string[] $allowedKeys
     * @param string[] $bannedHeaders
     */
    public function __construct(
        protected bool $allowHttpHeaders = false,
        protected bool $allowSensitiveServerKeys = false,
        protected bool $allowArgs = false,
        protected array $allowedKeys = [],
        protected array $bannedHeaders = [],
    ) {
    }

    public function sanitizeServer(array $server): array
    {
        $keys = array_keys($server);

        $allowedKeys = array_merge(self::DEFAULT_SERVER_KEYS, $this->allowedKeys);

        if ($this->allowArgs) {
            $allowedKeys = array_merge($allowedKeys, self::ARG_SERVER_KEYS);
        }

        if ($this->allowSensitiveServerKeys) {
            $allowedKeys = array_merge($allowedKeys, self::SENSITIVE_DEFAULT_SERVER_KEYS);
        }

        if ($this->allowHttpHeaders) {
            $allowedKeys = array_merge(
                $allowedKeys,
                array_filter($keys, fn (string $key) => preg_match('/^HTTP_[-_A-Z0-9]+$/', $key) > 0),
            );
        }

        $allowedKeys = array_diff(
            $allowedKeys,
            array_map(
                fn (string $header) => preg_replace('/[^_A-Z0-9]+/', '_', strtoupper(preg_replace('/^HTTP_/', '', $header))),
                $this->bannedHeaders,
            ),
        );

        foreach (array_diff($keys, $allowedKeys) as $key) {
            unset($server[$key]);
        }

        return $server;
    }
}
