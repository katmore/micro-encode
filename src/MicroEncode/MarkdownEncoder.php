<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

/**
 * Generates readable Markdown from arbitrary data
 *
 * @author D. Bird <retran@gmail.com>
 */
class MarkdownEncoder implements EncoderInterface
{
    private const EMPTY_LIST_LABEL = '_(empty list)_';
    private const EMPTY_MAP_LABEL = '_(empty)_';

    /**
     * @var string
     */
    private readonly string $encodedValue;

    /**
     * @return string Markdown serialized data
     */
    public function __toString(): string
    {
        return $this->encodedValue;
    }

    /**
     * @return string Markdown serialized data
     */
    public function getEncodedValue(): string
    {
        return $this->encodedValue;
    }

    /**
     * @param mixed $data data to serialize to Markdown
     * @param MarkdownEncoderOptions $options encoding options
     */
    public function __construct(mixed $data, MarkdownEncoderOptions $options = new MarkdownEncoderOptions())
    {
        $this->encodedValue = static::dataToMarkdown($data, $options->orderedLists);
    }

    protected static function dataToMarkdown(mixed $data, bool $orderedLists): string
    {
        if (is_array($data)) {
            if ($data === []) {
                return self::EMPTY_LIST_LABEL;
            }
            return array_is_list($data)
                ? static::renderList($data, $orderedLists)
                : static::renderMap($data, $orderedLists);
        }

        if (is_object($data)) {
            $pairs = static::objectToPairs($data);
            return $pairs === [] ? self::EMPTY_MAP_LABEL : static::renderMap($pairs, $orderedLists);
        }

        if (is_string($data) && str_contains($data, "\n")) {
            return static::fencedCodeBlock($data);
        }

        return static::renderScalar($data);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected static function objectToPairs(object $data): array
    {
        $pairs = [];
        foreach ($data as $key => $value) {
            $pairs[$key] = $value;
        }
        return $pairs;
    }

    /**
     * @param list<mixed> $items
     */
    protected static function renderList(array $items, bool $orderedLists): string
    {
        // A "-" marker only ever works when it's immediately followed by inline
        // content. The moment an item needs block layout (a nested array,
        // object, or multiline string), its marker line has to be left bare,
        // and CommonMark cannot reliably tell a bare "-" item's own nested
        // content apart from a new sibling item using that same character - it
        // can even fold a preceding label into a heading. Ordered markers don't
        // have this problem: "1.", "2." are self-disambiguating. So a list
        // containing any non-scalar element always renders as ordered,
        // regardless of $orderedLists, as a structural necessity rather than
        // a style choice. Mixing marker characters within one list isn't an
        // option either way - CommonMark would read that as two separate lists.
        $ordered = $orderedLists || static::containsBlockElement($items);

        $lines = [];
        $index = 1;
        foreach ($items as $value) {
            $marker = $ordered ? $index.'.' : '-';
            $lines[] = static::renderItem($marker, null, $value, $orderedLists);
            $index++;
        }
        return implode("\n", $lines);
    }

    /**
     * @param list<mixed> $items
     */
    protected static function containsBlockElement(array $items): bool
    {
        foreach ($items as $value) {
            if (static::isBlock($value)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param array<int|string, mixed> $pairs
     */
    protected static function renderMap(array $pairs, bool $orderedLists): string
    {
        $lines = [];
        foreach ($pairs as $key => $value) {
            $label = '**'.static::escapeMarkdown((string) $key).':**';
            $lines[] = static::renderItem('-', $label, $value, $orderedLists);
        }
        return implode("\n", $lines);
    }

    protected static function renderItem(string $marker, ?string $label, mixed $value, bool $orderedLists): string
    {
        $prefix = $label === null ? $marker : "$marker $label";

        if (static::isBlock($value)) {
            $block = static::indentLines(static::dataToMarkdown($value, $orderedLists), strlen($marker) + 1);

            // A label is rendered as an open paragraph. If the nested block's own
            // first line is itself a bare, content-less list marker (a list of
            // containers), CommonMark cannot let it interrupt that paragraph and
            // the marker gets swallowed as literal text instead of starting a
            // list. A blank line forces it to be recognized as a new block.
            // Bare markers never need this: they don't open a paragraph, so a
            // nested bare marker beneath them is already recognized correctly
            // - and inserting a blank line there would instead disconnect it.
            $separator = ($label !== null && static::startsWithBareMarker($value)) ? "\n\n" : "\n";

            return "$prefix$separator$block";
        }

        return $prefix.' '.static::renderInlineValue($value);
    }

    protected static function isBlock(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }
        if (is_object($value)) {
            return static::objectToPairs($value) !== [];
        }
        return is_string($value) && str_contains($value, "\n");
    }

    protected static function startsWithBareMarker(mixed $value): bool
    {
        if (!is_array($value) || $value === [] || !array_is_list($value)) {
            return false;
        }
        return static::isBlock($value[array_key_first($value)]);
    }

    protected static function renderInlineValue(mixed $value): string
    {
        if (is_array($value)) {
            return self::EMPTY_LIST_LABEL;
        }
        if (is_object($value)) {
            return self::EMPTY_MAP_LABEL;
        }
        return static::renderScalar($value);
    }

    protected static function renderScalar(mixed $value): string
    {
        return match (true) {
            $value === null => 'null',
            is_bool($value) => $value ? 'true' : 'false',
            is_int($value), is_float($value) => (string) $value,
            default => static::escapeMarkdown((string) $value),
        };
    }

    protected static function fencedCodeBlock(string $value): string
    {
        $fenceLength = 3;
        if (preg_match_all('/`+/', $value, $matches)) {
            foreach ($matches[0] as $run) {
                $fenceLength = max($fenceLength, strlen($run) + 1);
            }
        }
        $fence = str_repeat('`', $fenceLength);
        return "$fence\n$value\n$fence";
    }

    protected static function indentLines(string $block, int $width): string
    {
        $indent = str_repeat(' ', $width);
        return implode("\n", array_map(
            static fn (string $line): string => $line === '' ? $line : $indent.$line,
            explode("\n", $block)
        ));
    }

    protected static function escapeMarkdown(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        $escaped = str_replace('\\', '\\\\', $value);
        $escaped = preg_replace('/([*_`\[\]])/', '\\\\$1', $escaped);

        if ($escaped[0] === '-' || $escaped[0] === '#') {
            $escaped = '\\'.$escaped;
        }

        return $escaped;
    }
}
