<?php

declare(strict_types=1);

namespace Libero\MediaType;

use Iterator;
use function in_array;
use function mb_strlen;
use function mb_substr;

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

/**
 * @internal
 *
 * @return Iterator<int, string>
 */
function iterable_string(string $string) : Iterator
{
    for ($i = 0, $length = mb_strlen($string); $i < $length; ++$i) {
        yield mb_substr($string, $i, 1);
    }
}

/**
 * @internal
 *
 * @param Iterator<int, string> $iterator
 */
function read_until(Iterator $iterator, callable $until) : string
{
    $value = '';

    while ($iterator->valid()) {
        $character = $iterator->current();

        if (true === $until($character)) {
            break;
        }

        $value .= $character;

        $iterator->next();
    }

    return $value;
}
