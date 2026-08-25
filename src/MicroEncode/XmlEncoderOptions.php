<?php
declare(strict_types=1);

namespace MicroEncode;

/**
 * Options for {@see XmlEncoder}.
 *
 * @author D. Bird <retran@gmail.com>
 */
final class XmlEncoderOptions
{
    /**
     * @param string $rootNode root node element name
     * @param string $defaultNode node element name to use when one cannot be determined from the data itself
     * @param int $indentSize indentation size
     * @param bool $dumpOk whether to generate a special data-dump node for data that cannot be reliably serialized
     * @param string[] $checksumAlgos each array element value specifies a hash algo used to produce a data checksum with
     * @param bool $generateStructure whether to generate a node describing the data structure
     * @param bool $xsiTypeDetect whether to describe data node types using an extended XSI specification
     * @param bool $generateCreatedAttr whether to generate a "fx:created" attribute with the current timestamp
     */
    public function __construct(
        public readonly string $rootNode = 'fx:data',
        public readonly string $defaultNode = 'fx:data',
        public readonly int $indentSize = 3,
        public readonly bool $dumpOk = false,
        public readonly array $checksumAlgos = ['md5'],
        public readonly bool $generateStructure = false,
        public readonly bool $xsiTypeDetect = true,
        public readonly bool $generateCreatedAttr = false,
    ) {
    }
}
