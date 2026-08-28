<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

namespace MicroEncode;

use stdClass;
use finfo;

/**
 * Serializes data to XML
 *
 * @author D. Bird <retran@gmail.com>
 */
class XmlEncoder implements EncoderInterface
{
    public const FLAT_XMLNS = 'https://github.com/katmore/flat/wiki/xmlns';

    public const META_VALUE_GENERIC_OBJECT = 'object';

    /**
     * @var string
     */
    private readonly string $encodedValue;

    /**
     * @return string XML serialized data
     */
    public function __toString(): string
    {
        return $this->encodedValue;
    }

    /**
     * @return string XML serialized data
     */
    public function getEncodedValue(): string
    {
        return $this->encodedValue;
    }

    /**
     * @param mixed $data data to serialize to XML
     * @param XmlEncoderOptions $options encoding options
     */
    public function __construct(mixed $data, XmlEncoderOptions $options = new XmlEncoderOptions())
    {
        $meta = '';
        $metatag = '';
        if ($options->generateStructure === true) {
            if (static::META_VALUE_GENERIC_OBJECT !== ($meta = static::dataToFlatXmlMetaValue($data))) {
                $meta = ' fx:meta="'.$meta.'"';
            } else {
                $meta = ' fx:meta="extxs:structure"';
                $structure = new stdClass();
                $dataStructure = json_decode(json_encode(static::createXmlDataStructure($data)));
                $structure->structure = $dataStructure;
                $metatag = static::dataToFlatXml(
                    $structure,
                    'structure',
                    1,
                    $options->indentSize,
                    false,
                    [],
                    [
                        'namespace' => 's',
                        'namespaceindentifyer' => static::FLAT_XMLNS.'-structure',
                    ]
                );
            }
        }

        $xsins = '';
        if ($options->xsiTypeDetect) {
            $xsins = ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:extxs="'.static::FLAT_XMLNS.'-extxs"';
        }

        $checksum_attr = '';
        $checksum_data = null;
        foreach ($options->checksumAlgos as $algo) {
            if ($checksum_data === null) {
                // hash the actual payload being encoded, not a constant. json_encode()
                // can fail on data it can't represent (invalid UTF-8 strings, NAN/INF
                // floats) — serialize() is used as a fallback so the checksum still
                // reflects the real input rather than silently becoming a constant again.
                $checksum_data = json_encode($data);
                if ($checksum_data === false) {
                    $checksum_data = serialize($data);
                }
            }
            $hash = hash($algo, $checksum_data);
            if (!hexdec($hash)) {
                continue;
            }
            $attr = preg_replace('/[^a-zA-Z0-9]+/', '', $algo);
            if (is_numeric(substr($attr, 0, 1))) {
                $attr = 'hash_'.$attr;
            }
            $checksum_attr .= " fx:$attr=\"$hash\"";
        }
        unset($checksum_data);

        $created_attr = '';
        if ($options->generateCreatedAttr) {
            $created_attr = ' fx:created="'.date('c').'"';
        }

        $encodedValue = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $encodedValue .= '<'.$options->rootNode." xmlns:fx=\"".static::FLAT_XMLNS."\" xmlns=\"".static::FLAT_XMLNS."-object\"$created_attr$checksum_attr$meta$xsins";

        $ftypeattr = '';
        if ($data === null) {
            $ftypeattr .= ' xsi:nil="true"';
        } elseif (is_object($data)) {
            $ftypeattr .= static::dataToObjectTypeAttributes($data);
        } elseif (is_array($data)) {
            $ftypeattr .= static::dataToArrayTypeAttributes($data);
        }
        $encodedValue .= "$ftypeattr>\n";

        $encodedValue .= static::dataToFlatXml(
            $data,
            $options->defaultNode,
            1,
            $options->indentSize,
            $options->dumpOk,
            $options->checksumAlgos,
            [
                'xsi_type' => [
                    'string' => true,
                    'DateTime' => true,
                    'hexBinary' => true,
                    'base64Binary' => true,
                    'anyURI' => true,
                    'nil' => true,
                ],
            ]
        );
        $encodedValue .= $metatag;
        $encodedValue .= '</'.$options->rootNode.'>';

        $this->encodedValue = $encodedValue;
    }

    protected static function indent(int $level = 1, int $size = 3): string
    {
        if (($level < 1) || ($size < 1)) {
            return '';
        }
        return str_repeat(' ', $size * $level);
    }

    protected static function createXmlDataStructure(mixed $data): XmlDataStructure
    {
        return new XmlDataStructure($data);
    }

    protected static function dataToFlatXmlMetaValue(mixed $data): string
    {
        if (XmlDataStructure::dataToXmlDataStructureType($data) === XmlDataStructure::TYPE_GENERIC_OBJECT) {
            return static::META_VALUE_GENERIC_OBJECT;
        }
        if (is_object($data)) {
            return htmlspecialchars(get_class($data), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED, 'UTF-8');
        }
        return htmlspecialchars(gettype($data), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED, 'UTF-8');
    }

    protected static function typeToXsiAttribute(string $type): string
    {
        return 'xsi:type="'.$type.'"';
    }

    /**
     * @param mixed $data
     * @param ?array<string,bool> $option
     */
    protected static function dataToXsiType(mixed $data, ?array $option = null): string
    {
        $param = [
            'DateTime' => true,
            'hexBinary' => true,
            'base64Binary' => true,
            'anyURI' => true,
            'string' => true,
            'Array' => true,
            'Object' => true,
            'other' => true,
        ];
        if (is_array($option)) {
            foreach ($option as $key => $val) {
                if (isset($param[$key])) {
                    $param[$key] = $val;
                }
            }
        }

        if (!is_scalar($data)) {
            return '';
        }

        if (is_int($data)) {
            return 'xs:integer';
        }

        if (is_float($data)) {
            return 'xs:decimal';
        }

        if (is_bool($data)) {
            return 'xs:boolean';
        }

        // is_string($data)
        if (is_numeric($data)) {
            return intval($data) == $data ? 'extxs:NumericStringInt' : 'extxs:NumericStringFloat';
        }

        if ($param['DateTime'] && trim($data) !== '' && false !== ($ts = strtotime($data)) && !empty($ts)) {
            return 'xs:DateTime';
        }

        if ($param['hexBinary'] && ctype_xdigit($data)) {
            return 'xs:hexBinary';
        }

        if ($param['anyURI'] && filter_var($data, FILTER_VALIDATE_URL)) {
            return 'xs:anyURI';
        }

        if ($param['string'] && ctype_print($data)) {
            return 'xs:string';
        }

        return 'extxs:Binary';
    }

    protected static function dataToObjectTypeAttributes(mixed $data): string
    {
        if (!is_object($data)) {
            return '';
        }
        $type = get_class($data);
        // an anonymous class's name embeds its defining file's path and line
        // number (e.g. "class@anonymous /app/src/Foo.php:20$0") — treat it as
        // generic, same as XmlDataStructure does, instead of leaking that path.
        $isAnonymous = str_starts_with($type, 'class@anonymous');
        $type = ($type === 'stdClass' || $isAnonymous) ? 'Generic' : "\\$type";
        return ' xsi:type="extxs:Object" extxs:ObjectType="'.preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $type).'"';
    }

    protected static function dataToArrayTypeAttributes(mixed $data): string
    {
        if (!is_array($data)) {
            return '';
        }
        $all_same_type = true;
        $last_type = null;
        $first_value = true;
        $i = 0;
        foreach ($data as $k => $v) {
            if ($i > 9) {
                break;
            }
            if (!is_int($k)) {
                return ' xsi:type="extxs:Hashmap"';
            }
            if (!$first_value && (static::dataToXsiType($v, ['string' => true]) !== $last_type)) {
                $all_same_type = false;
                break;
            }
            $first_value = false;
            $last_type = static::dataToXsiType($v, ['string' => true]);
            $i++;
        }
        if ($all_same_type) {
            return ' xsi:type="extxs:Array" extxs:ArrayType="'.$last_type.'['.count($data).']"';
        }
        return ' xsi:type="extxs:Array" extxs:ArrayType="extxs:Mixed['.count($data).']"';
    }

    /**
     * @param mixed $data
     * @param string[] $checksum
     * @param array<string,mixed> $options
     */
    protected static function dataToFlatXml(
        mixed $data,
        string $default_node,
        int $indent_level = 1,
        int $indent_size = 3,
        bool $dump_ok = true,
        array $checksum = ['md5'],
        array $options = []
    ): string {
        $ns = '';
        $nsidattr = '';
        if (!empty($options['namespace'])) {
            if (preg_match('/[a-z_0-9]/i', $options['namespace']) && !is_numeric(substr($options['namespace'], 0, 1))) {
                $ns = $options['namespace'].':';

                if (empty($options['namespace_child'])) {
                    if (empty($options['namespaceindentifyer'])) {
                        $nsid = static::FLAT_XMLNS.'-'.$options['namespace'];
                    } else {
                        $nsid = htmlspecialchars($options['namespaceindentifyer'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED, 'UTF-8');
                    }
                    $nsidattr = ' xmlns:'.$options['namespace']."=\"$nsid\"";
                    $options['namespace_child'] = true;
                }
            }
        }

        if (is_array($data) || is_object($data)) {
            $xml = '';
            foreach ($data as $key => $value) {
                $node = (string) $key;

                $nodeNsidattr = $nsidattr;

                if (is_array($value)) {
                    $nodeNsidattr .= static::dataToArrayTypeAttributes($value);
                } elseif (is_object($value)) {
                    $nodeNsidattr .= static::dataToObjectTypeAttributes($value);
                }

                $index = null;
                if (preg_match('/[^a-z_0-9]/i', $node)) {
                    $node = $default_node;
                } elseif (is_numeric(substr($node, 0, 1))) {
                    $node = $default_node;
                }
                if (empty($node)) {
                    $node = $default_node;
                }
                $node = strtolower($node);
                $xml .= static::indent($indent_level, $indent_size)."<$ns$node$nodeNsidattr";
                if (is_int($key)) {
                    $index = $key;
                    $xml .= " extxs:index=\"$key\"";
                }
                if ($key != $node) {
                    $keyval = htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED, 'UTF-8');
                    if ($keyval != $index) {
                        $xml .= " extxs:key=\"$keyval\"";
                    }
                }

                if (is_array($value) || is_object($value)) {
                    $indent_level++;
                    $xml .= ">\n".
                        static::dataToFlatXml($value, $default_node, $indent_level, $indent_size, $dump_ok, $checksum, $options);
                    $indent_level--;
                    $xml .= static::indent($indent_level, $indent_size)."</$ns$node>\n";
                } elseif ($value === null) {
                    if (!empty($options['xsi_type']['nil'])) {
                        $xml .= ' xsi:nil="true"'.' ';
                    }
                    $xml .= "/>\n";
                } elseif (is_string($value) && ($value === '')) {
                    $xml .= ' xsi:nil="true"';
                    $xml .= "/>\n";
                } else {
                    $xsi = '';
                    if (!empty($options['xsi_type'])) {
                        if ($xsitype = static::dataToXsiType($value, $options['xsi_type'])) {
                            $xsi = ' '.static::typeToXsiAttribute($xsitype);
                        }
                    }

                    $xml .= "$xsi>".static::dataToFlatXml($value, $default_node, $indent_level, $indent_size, $dump_ok, $checksum, $options)."</$ns$node>\n";
                }
            }
            return $xml;
        }

        // A nested null value never reaches this point at all -- the per-key
        // dispatch loop above already intercepts it before recursing (mirroring
        // how it handles an empty string). The only way $data can be null here
        // is a *top-level* `new XmlEncoder(null)`, whose root element already
        // carries xsi:nil="true" (set in the constructor) -- so its content is
        // legitimately empty, not "undumpable".
        if ($data === null) {
            return '';
        }

        // Booleans stringify asymmetrically ((string) true === '1', but
        // (string) false === ''), which otherwise misroutes an ordinary `false`
        // leaf value into the "undumpable data" branch below, right alongside
        // genuinely empty strings. Normalize explicitly so both boolean values
        // render as ordinary text content.
        $data = is_bool($data) ? ($data ? '1' : '0') : (string) $data;

        // Invalid UTF-8 can never be represented as well-formed XML text content
        // (XML 1.0 requires it). Route it to the same dumpOk-gated handling as
        // other non-representable values instead of letting htmlspecialchars()
        // silently substitute lossy U+FFFD replacement characters for it -- the
        // dump path below preserves the original bytes exactly (base64 doesn't
        // care about UTF-8 validity), which ENT_SUBSTITUTE's approach would not.
        $isValidUtf8 = mb_check_encoding($data, 'UTF-8');

        if ($isValidUtf8 && '' !== ($xml = htmlspecialchars($data, ENT_XML1 | ENT_DISALLOWED | ENT_SUBSTITUTE, 'UTF-8'))) {
            // note: intentionally not trim()'d — leading/trailing whitespace in
            // the original value is meaningful text content, not incidental
            // formatting, and stripping it silently discarded whitespace-only
            // values entirely (see the dumpOk branch below for what's left once
            // booleans, null, and invalid UTF-8 are all handled above).
            return $xml;
        }

        if (!$dump_ok) {
            throw new UndumpableDataException(
                "encountered a value with no safe textual XML representation (an empty string, or a type such as a resource); pass XmlEncoderOptions(dumpOk: true) to allow a binary dump fallback instead"
            );
        }

        $checksum_attr = '';
        foreach ($checksum as $algo) {
            $hash = hash($algo, $data);
            if (!hexdec($hash)) {
                continue;
            }
            $attr = preg_replace('/[^a-zA-Z0-9]+/', '', $algo);
            if (is_numeric(substr($attr, 0, 1))) {
                $attr = 'hash_'.$attr;
            }
            $checksum_attr .= " $attr=\"$hash\"";
        }

        $base64 = base64_encode($data);
        $encoding = ' extxs:encoding="base64"';
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (false !== ($mtype = $finfo->buffer($data))) {
            $encoding .= ' extxs:mtype="'.$mtype.'"';
        }
        $indent_level++;
        $xml = "\n".static::indent($indent_level, $indent_size);
        $xml .= "<$default_node extxs:DumpObject=\"xs:base64Binary\"$checksum_attr$encoding>$base64";
        $indent_level--;
        $xml .= "</$default_node>\n".static::indent($indent_level, $indent_size);
        return $xml;
    }
}
