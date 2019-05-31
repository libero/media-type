<?php

declare(strict_types=1);

namespace Libero\MediaType;

use Iterator;
use function mb_strlen;
use function mb_substr;

/**
 * @internal
 */
final class StringIterator implements Iterator
{
    private $characters;
    private $length;
    private $pointer;

    public function __construct(string $characters)
    {
        $this->characters = $characters;
        $this->pointer = 0;
        $this->length = mb_strlen($this->characters);
    }

    public function current()
    {
        return mb_substr($this->characters, $this->pointer, 1);
    }

    public function next()
    {
        $this->pointer++;
    }

    public function key()
    {
        return $this->pointer;
    }

    public function valid()
    {
        return $this->pointer >= 0 && $this->pointer < $this->length;
    }

    public function rewind()
    {
        $this->pointer = 0;
    }

    public function sliceUntil(callable $test) : string
    {
        $value = '';

        while ($this->valid()) {
            $character = $this->current();

            if (true === $test($character)) {
                break;
            }

            $value .= $character;

            $this->next();
        }

        return $value;
    }
}
