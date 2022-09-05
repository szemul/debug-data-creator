<?php
declare(strict_types=1);

namespace Szemul\DebugDataCreator\Sanitizer;

use ArrayAccess;
use Iterator;
use IteratorAggregate;

class RegexClassFilteringSanitizer extends NoopSanitizer
{
    /** @var string[] */
    protected array $filterPatterns;

    public function __construct(protected int $maxRecursionDepth, string ...$filterPatterns)
    {
        $this->filterPatterns = $filterPatterns;
    }

    public function sanitizeBackTrace(array $backtrace): array
    {
        return $this->sanitizeArray($backtrace);
    }

    /**
     * @param array<int|string,mixed>|Iterator<int|string,mixed>|IteratorAggregate<int|string,mixed> $array
     *
     * @return array<int|string,mixed>|Iterator<int|string,mixed>|IteratorAggregate<int|string,mixed>
     */
    protected function sanitizeArray(array|Iterator|IteratorAggregate $array, int $recursionDepth = 0): array|Iterator|IteratorAggregate
    {
        foreach ($array as $key => $value) {
            if (is_object($value)) {
                foreach ($this->filterPatterns as $pattern) {
                    $class = get_class($value);
                    if (preg_match($pattern, $class)) {
                        $value = $array[$key] = '*** Removed by blacklist. Class of ' . $class . ' ***';
                        break;
                    }
                }
            }

            if ($recursionDepth >= $this->maxRecursionDepth) {
                // We've reached the max depth, no more recursion
                continue;
            }

            // Run recursively over arrays and objects that are iterable and have ArrayAccess
            if (
                is_array($value)
                || (
                    $value instanceof ArrayAccess
                    && (
                        $value instanceof Iterator
                        || $value instanceof IteratorAggregate
                    )
                )
            ) {
                $array[$key] = $this->sanitizeArray($value, $recursionDepth + 1);
            }
        }

        return $array;
    }
}
