<?php

declare(strict_types=1);

namespace Libero\MediaType;

use Iterator;
use Libero\MediaType\Exception\InvalidMediaType;
use LogicException;
use OutOfBoundsException;
use function addcslashes;
use function mb_strtolower;
use function mb_substr;
use function preg_match;
use function rtrim;
use function trim;

final class MediaType
{
    private $type;
    private $subType;
    private $parameters;

    /**
     * @param array<string, string> $parameters
     */
    private function __construct(string $type, string $subType, array $parameters)
    {
        $this->type = $type;
        $this->subType = $subType;
        $this->parameters = $parameters;
    }

    public static function fromString(string $string) : MediaType
    {
        // 1. Remove any leading and trailing HTTP whitespace from input.

        $string = trim($string, " \t\n\r");

        // 2. Let position be a position variable for input, initially pointing at the start of input.

        $input = iterable_string($string);

        // 3. Let type be the result of collecting a sequence of code points that are not U+002F (/) from input, given
        // position.

        $type = read_until($input, is('/'));

        // 4. If type is the empty string or does not solely contain HTTP token code points, then return failure.

        if ('' === $type) {
            throw new InvalidMediaType('No type');
        }

        if (1 !== preg_match('/^[-!#$%&\'*+.^_`|~A-Za-z0-9]+$/', $type)) {
            throw new InvalidMediaType('Type contains invalid characters');
        }

        // 5. If position is past the end of input, then return failure.

        if (!$input->valid()) {
            throw new InvalidMediaType('No subtype');
        }

        // 6. Advance position by 1. (This skips past U+002F (/).)

        $input->next();

        // 7. Let subtype be the result of collecting a sequence of code points that are not U+003B (;) from input,
        // given position.

        $subType = read_until($input, is(';'));

        // 8. Remove any trailing HTTP whitespace from subtype.

        $subType = rtrim($subType, " \t\n\r");

        // 9. If subtype is the empty string or does not solely contain HTTP token code points, then return failure.

        if ('' === $subType) {
            throw new InvalidMediaType('No subtype');
        }

        if (1 !== preg_match('/^[-!#$%&\'*+.^_`|~A-Za-z0-9]+$/', $subType)) {
            throw new InvalidMediaType('Subtype contains invalid characters');
        }

        // 10. Let mimeType be a new MIME type record whose type is type, in ASCII lowercase, and subtype is subtype, in
        // ASCII lowercase.

        $type = mb_strtolower($type);
        $subType = mb_strtolower($subType);
        $parameters = [];

        // 11. While position is not past the end of input:

        while ($input->valid()) {
            // 11.1. Advance position by 1. (This skips past U+003B (;).)

            $input->next();

            // 11.2. Collect a sequence of code points that are HTTP whitespace from input given position.

            read_until($input, not(is(' ', "\t", "\n", "\r")));

            // 11.3. Let parameterName be the result of collecting a sequence of code points that are not U+003B (;) or
            // U+003D (=) from input, given position.

            $parameterName = read_until($input, is(';', '='));

            // 11.4. Set parameterName to parameterName, in ASCII lowercase.

            $parameterName = mb_strtolower($parameterName);

            // 11.5. If position is not past the end of input, then:

            if ($input->valid()) {
                // 11.5.1. If the code point at position within input is U+003B (;), then continue.

                if (';' === $input->current()) {
                    continue;
                }

                // 11.5.2 Advance position by 1. (This skips past U+003D (=).)

                $input->next();
            } else {
                // 11.6. If position is past the end of input, then break.

                break;
            }

            // 11.7. Let parameterValue be null.

            $parameterValue = null;

            // 11.8. If the code point at position within input is U+0022 ("), then:

            if ('"' === $input->current()) {
                // 11.8.1. Set parameterValue to the result of collecting an HTTP quoted string from input, given
                // position and the extract-value flag.

                $parameterValue = self::collectAnHttpQuotedString($input, true);

                // 11.8.2. Collect a sequence of code points that are not U+003B (;) from input, given position.

                read_until($input, is(';'));
            } else {
                // 11.9. Otherwise:

                // 11.9.1. Set parameterValue to the result of collecting a sequence of code points that are not
                // U+003B (;) from input, given position.

                $parameterValue = read_until($input, is(';'));

                // 11.9.2. Remove any trailing HTTP whitespace from parameterValue.

                $parameterValue = rtrim($parameterValue, " \t\n\r");

                // 11.9.3. If parameterValue is the empty string, then continue.

                if ('' === $parameterValue) {
                    continue;
                }
            }

            // 11.10. If all of the following are true
            // - parameterName is not the empty string
            // - parameterName solely contains HTTP token code points
            // - parameterValue solely contains HTTP quoted-string token code points
            // - mimeType’s parameters[parameterName] does not exist
            // then set mimeType’s parameters[parameterName] to parameterValue.

            if ('' !== $parameterName
                && 1 === preg_match('/^[-!#$%&\'*+.^_`|~A-Za-z0-9]*$/', $parameterName)
                && 1 === preg_match('/^[\x{0009}\x{0020}-\x{007E}\x{0080}-\x{00FF}]*$/uD', $parameterValue)
                && !isset($parameters[$parameterName])
            ) {
                $parameters[$parameterName] = $parameterValue;
            }
        }

        // 12. Return mimeType.

        return new MediaType($type, $subType, $parameters);
    }

    public function __toString() : string
    {
        // 1. Let serialization be the concatenation of mimeType’s type, U+002F (/), and mimeType’s subtype.

        $serialization = "{$this->type}/{$this->subType}";

        // 2. For each name → value of mimeType’s parameters:

        foreach ($this->parameters as $name => $value) {
            // 2.1. Append U+003B (;) to serialization.

            $serialization .= ';';

            // 2.2. Append name to serialization.

            $serialization .= $name;

            // 2.3. Append U+003D (=) to serialization.

            $serialization .= '=';

            // 2.4. If value does not solely contain HTTP token code points or value is the empty string, then:

            if (1 !== preg_match('/^[-!#$%&\'*+.^_`|~A-Za-z0-9]*$/', $value) || '' === $value) {
                // 2.4.1. Precede each occurence of U+0022 (") or U+005C (\) in value with U+005C (\).

                $value = addcslashes($value, '"\\');

                // 2.4.2. Prepend U+0022 (") to value.

                $value = '"'.$value;

                // 2.4.3. Append U+0022 (") to value.

                $value .= '"';
            }

            // 2.5. Append value to serialization.

            $serialization .= $value;
        }

        return $serialization;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function getSubType() : string
    {
        return $this->subType;
    }

    public function getEssence() : string
    {
        return "{$this->type}/{$this->subType}";
    }

    public function getParameters() : array
    {
        return $this->parameters;
    }

    public function hasParameter(string $name) : bool
    {
        return isset($this->parameters[$name]);
    }

    public function getParameter(string $name) : string
    {
        if (!$this->hasParameter($name)) {
            throw new OutOfBoundsException('No parameter');
        }

        return $this->parameters[$name];
    }

    /**
     * @param Iterator<int, string> $input
     */
    private static function collectAnHttpQuotedString(Iterator $input, bool $extractValue = false) : string
    {
        // 1. Let positionStart be position.

        $positionStart = $input->key();

        // 2. Let value be the empty string.

        $value = '';

        // 3. Assert: the code point at position within input is U+0022 (").

        if ('"' !== $input->current()) {
            throw new LogicException('Expected a quote');
        }

        // 4. Advance position by 1.

        $input->next();

        // 5. While true:

        while (true) {
            // 5.1. Append the result of collecting a sequence of code points that are not U+0022 (") or U+005C (\) from
            // input, given position, to value.

            $value .= read_until($input, is('"', '\\'));

            // 5.2. If position is past the end of input, then break.

            if (!$input->valid()) {
                break;
            }

            // 5.3. Let quoteOrBackslash be the code point at position within input.

            $quoteOrBackslash = $input->current();

            // 5.4. Advance position by 1.

            $input->next();

            // 5.5 If quoteOrBackslash is U+005C (\), then:

            if ('\\' === $quoteOrBackslash) {
                // 5.5.1. If position is past the end of input, then append U+005C (\) to value and break.

                if (!$input->valid()) {
                    $value .= '\\';
                    break;
                }

                // 5.5.2. Append the code point at position within input to value.

                $value .= $input->current();

                // 5.5.3 Advance position by 1.

                $input->next();
            } else {
                // 5.6. Otherwise:

                // 5.6.1 Assert: quoteOrBackslash is U+0022 (").

                if ('"' !== $quoteOrBackslash) {
                    throw new LogicException('Expected a quote or a backslash');
                }

                // phpcs:ignore
                // 5.6.2. Break.

                break;
            }
        }

        // 6. If the extract-value flag is set, then return value.

        if ($extractValue) {
            return $value;
        }

        // 7. Return the code points from positionStart to position, inclusive, within input.

        return mb_substr($value, $positionStart, $input->key());
    }
}
