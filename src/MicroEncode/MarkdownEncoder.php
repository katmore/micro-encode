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
        $buffer = [];
        static::renderData($buffer, $data, $orderedLists, 0);
        return implode('', $buffer);
    }

    /**
     * Renders $data into $buffer, one append per piece of output.
     *
     * Every recursion level writes its own output exactly once into the single
     * shared $buffer instead of returning a finished string for its caller to
     * re-concatenate (which copied a subtree's bytes once per ancestor level,
     * i.e. O(depth^2) total). Indentation is carried down as the running
     * $indent column rather than applied afterwards to already-rendered text.
     *
     * Contract: this is always called at the start of a fresh output line, and
     * is responsible for writing the indentation of every line it emits -
     * including its first - except for lines that are empty, which stay empty
     * (matching what the previous post-hoc line-indenting did).
     *
     * @param array<int, string> $buffer
     */
    protected static function renderData(array &$buffer, mixed $data, bool $orderedLists, int $indent): void
    {
        if (is_array($data)) {
            if ($data === []) {
                static::appendBlock($buffer, self::EMPTY_LIST_LABEL, $indent);
                return;
            }
            if (array_is_list($data)) {
                static::renderList($buffer, $data, $orderedLists, $indent);
            } else {
                static::renderMap($buffer, $data, $orderedLists, $indent);
            }
            return;
        }

        if (is_object($data)) {
            $pairs = static::objectToPairs($data);
            if ($pairs === []) {
                static::appendBlock($buffer, self::EMPTY_MAP_LABEL, $indent);
                return;
            }
            static::renderMap($buffer, $pairs, $orderedLists, $indent);
            return;
        }

        if (is_string($data) && (str_contains($data, "\n") || str_contains($data, "\r"))) {
            static::appendBlock($buffer, static::fencedCodeBlock($data), $indent);
            return;
        }

        static::appendBlock($buffer, static::renderScalar($data), $indent);
    }

    /**
     * Appends an already-rendered leaf block, indenting each of its non-empty
     * lines to $indent. Leaf blocks are terminal (a fenced code block or a
     * single scalar line), so this scans each one exactly once, at the level
     * where it occurs.
     *
     * @param array<int, string> $buffer
     */
    protected static function appendBlock(array &$buffer, string $block, int $indent): void
    {
        if ($indent < 1) {
            $buffer[] = $block;
            return;
        }

        $prefix = str_repeat(' ', $indent);
        $first = true;
        foreach (explode("\n", $block) as $line) {
            if (!$first) {
                $buffer[] = "\n";
            }
            $first = false;
            if ($line !== '') {
                $buffer[] = $prefix;
                $buffer[] = $line;
            }
        }
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
     * @param array<int, string> $buffer
     * @param list<mixed> $items
     */
    protected static function renderList(array &$buffer, array $items, bool $orderedLists, int $indent): void
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

        $index = 1;
        foreach ($items as $value) {
            if ($index > 1) {
                $buffer[] = "\n";
            }
            $marker = $ordered ? $index.'.' : '-';
            static::renderItem($buffer, $marker, null, $value, $orderedLists, $indent);
            $index++;
        }
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
     * @param array<int, string> $buffer
     * @param array<int|string, mixed> $pairs
     */
    protected static function renderMap(array &$buffer, array $pairs, bool $orderedLists, int $indent): void
    {
        $first = true;
        foreach ($pairs as $key => $value) {
            if (!$first) {
                $buffer[] = "\n";
            }
            $first = false;
            $label = '**'.static::escapeMarkdown((string) $key).':**';
            static::renderItem($buffer, '-', $label, $value, $orderedLists, $indent);
        }
    }

    /**
     * @param array<int, string> $buffer
     */
    protected static function renderItem(
        array &$buffer,
        string $marker,
        ?string $label,
        mixed $value,
        bool $orderedLists,
        int $indent
    ): void {
        if ($indent > 0) {
            $buffer[] = str_repeat(' ', $indent);
        }
        $buffer[] = $label === null ? $marker : "$marker $label";

        if (static::isBlock($value)) {
            // The separator can only be decided after the nested block has been
            // rendered (startsWithBareMarker() is deliberately evaluated in that
            // same order as before), so reserve its slot in the buffer now and
            // fill it in below rather than buffering the block separately.
            $separatorSlot = count($buffer);
            $buffer[] = "\n";

            static::renderData($buffer, $value, $orderedLists, $indent + strlen($marker) + 1);

            // A label is rendered as an open paragraph. If the nested block's own
            // first line is itself a bare, content-less list marker (a list of
            // containers), CommonMark cannot let it interrupt that paragraph and
            // the marker gets swallowed as literal text instead of starting a
            // list. A blank line forces it to be recognized as a new block.
            // Bare markers never need this: they don't open a paragraph, so a
            // nested bare marker beneath them is already recognized correctly
            // - and inserting a blank line there would instead disconnect it.
            if ($label !== null && static::startsWithBareMarker($value)) {
                $buffer[$separatorSlot] = "\n\n";
            }
            return;
        }

        $buffer[] = ' '.static::renderInlineValue($value);
    }

    protected static function isBlock(mixed $value): bool
    {
        if (is_array($value)) {
            return $value !== [];
        }
        if (is_object($value)) {
            return static::objectToPairs($value) !== [];
        }
        return is_string($value) && (str_contains($value, "\n") || str_contains($value, "\r"));
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

    protected static function escapeMarkdown(string $value): string
    {
        if ($value === '') {
            return '""';
        }

        $escaped = str_replace('\\', '\\\\', $value);
        // *_`[] are escaped because they're CommonMark inline-markup syntax.
        // <, >, and & are escaped for a different reason: CommonMark permits
        // raw inline HTML by default, and many renderers pass it straight
        // through unless explicitly configured not to (e.g. Parsedown, or
        // marked without a sanitizer) - so an unescaped value containing
        // something like "<script>...</script>" would render as live HTML in
        // whatever eventually consumes this output. Backslash-escaping these
        // is valid per the CommonMark spec (any ASCII punctuation character
        // may be backslash-escaped) and renders as literal text instead.
        $escaped = preg_replace('/([*_`\[\]<>&])/', '\\\\$1', $escaped);

        if ($escaped[0] === '-' || $escaped[0] === '#') {
            $escaped = '\\'.$escaped;
        }

        return $escaped;
    }
}
