<?php

declare(strict_types=1);

namespace Libero\MediaType;

use function in_array;

/**
 * @internal
 */
function is(string ...$options) : callable
{
    return static function (string $string) use ($options) : bool {
        return in_array($string, $options, true);
    };
}

/**
 * @internal
 */
function not(callable $callable) : callable
{
    return static function (...$args) use ($callable) : bool {
        return !$callable(...$args);
    };
}
