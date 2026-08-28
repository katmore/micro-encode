<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

use stdClass;

/**
 * Serializes data to HTML
 *
 * @author D. Bird <retran@gmail.com>
 */
class HtmlEncoder implements EncoderInterface
{
    public const META_VALUE_GENERIC_OBJECT = 'object';

    /**
     * @var string
     */
    private readonly string $encodedValue;

    /**
     * @return string HTML serialized data
     */
    public function __toString(): string
    {
        return $this->encodedValue;
    }

    /**
     * @return string HTML serialized data
     */
    public function getEncodedValue(): string
    {
        return $this->encodedValue;
    }

    /**
     * @param mixed $data data to serialize to HTML
     * @param HtmlEncoderOptions $options encoding options
     */
    public function __construct(mixed $data, HtmlEncoderOptions $options = new HtmlEncoderOptions())
    {
        $this->encodedValue = static::dataToHtml($data, $options->parentElement, $options->childElement, 0);
    }

    protected static function dataToMetaValue(mixed $data): string
    {
        if (is_object($data)) {
            if ($data instanceof stdClass) {
                return static::META_VALUE_GENERIC_OBJECT;
            }
            return htmlspecialchars(get_class($data), ENT_QUOTES | ENT_SUBSTITUTE);
        }
        return htmlspecialchars(gettype($data), ENT_QUOTES | ENT_SUBSTITUTE);
    }

    protected static function indent(int $level = 1, int $size = 3): string
    {
        if (($level < 1) || ($size < 1)) {
            return '';
        }
        return str_repeat(' ', $size * $level);
    }

    protected static function dataToHtml(
        mixed $data,
        string $parent_element,
        string $child_element,
        int $indent_level = 1,
        int $indent_size = 3
    ): string {
        $out = '';
        static::appendHtml($out, $data, $parent_element, $child_element, $indent_level, $indent_size);
        return $out;
    }

    /**
     * Appends the HTML for $data to $out, which is passed by reference all the
     * way down.
     *
     * Every recursion level appends its own output exactly once to the single
     * shared $out, instead of returning a finished string for its caller to
     * re-concatenate into a growing one - which re-copied a subtree's bytes
     * once per ancestor level above it, i.e. O(depth^2) total.
     */
    protected static function appendHtml(
        string &$out,
        mixed $data,
        string $parent_element,
        string $child_element,
        int $indent_level = 1,
        int $indent_size = 3
    ): void {
        if (is_array($data) || is_object($data)) {
            $i = 0;
            $out .= static::indent($indent_level, $indent_size)."<$parent_element data-type=\"".static::dataToMetaValue($data)."\">\n";
            foreach ($data as $key => $value) {
                $key = (string) $key;
                $indent_level++;
                $out .= static::indent($indent_level, $indent_size)."<$child_element ";
                $out .= "data-index=\"$i\" ";
                $out .= 'data-key="'.htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE).'" data-role="item">';
                if (sprintf('%d', $key) !== $key) {
                    $out .= '<span data-role="item-key">'.htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE).'</span>'.':&nbsp;';
                } else {
                    $out .= '&nbsp;';
                }
                $value_type_str = '';
                if (is_scalar($value)) {
                    $value_type_str = 'data-type="'.gettype($value).'"';
                    if (is_bool($value)) {
                        $value_type_str .= ' data-boolean-value="'.($value ? 'true' : 'false').'"';
                    }
                }
                if ($value === null) {
                    $value_type_str = 'data-type="null"';
                }
                $out .= "<span data-role=\"item-value\" $value_type_str>";
                // note: $indent_size is deliberately not forwarded here, so the
                // recursion keeps using the default - exactly as before.
                static::appendHtml($out, $value, $parent_element, $child_element, $indent_level);
                $out .= "</span></$child_element><!--/data-item: (".htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE).")-->\n";
                $indent_level--;
                $i++;
            }
            $out .= static::indent($indent_level, $indent_size)."</$parent_element>\n";
            return;
        }

        if (is_string($data) && $data === '') {
            $out .= "''";
            return;
        }
        if (is_scalar($data)) {
            $str = (string) $data;
            $stripped = str_replace(["\n", "\r"], '', $str);
            // ctype_print() operates byte-wise under the "C" locale, where only
            // ASCII 0x20-0x7E counts as printable -- meaning any non-ASCII UTF-8
            // text (accented letters, CJK, emoji; i.e. most real-world strings)
            // fails it and falls through to the var_dump() branch below instead
            // of rendering cleanly. Check for valid UTF-8 and the absence of
            // actual control bytes instead, so non-ASCII text is treated the
            // same as ASCII text.
            $isPrintable = mb_check_encoding($stripped, 'UTF-8')
                && !preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $stripped);
            if ($isPrintable) {
                $out .= htmlspecialchars($str, ENT_QUOTES | ENT_SUBSTITUTE);
                return;
            }
        }
        ob_start();
        var_dump($data);
        $dump = ob_get_clean();
        $out .= '(dump) <br><pre>'.nl2br(htmlspecialchars($dump, ENT_QUOTES | ENT_SUBSTITUTE)).'</pre>';
    }
}
