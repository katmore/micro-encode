<?php
declare(strict_types=1);

namespace MicroEncode;

use stdClass;

final class XmlDataStructure
{
    public const TYPE_SCALAR_VALUE = 'scalar';
    public const TYPE_NULL_VALUE = 'null';
    public const TYPE_TRUE_ARRAY = 'array';
    public const TYPE_GENERIC_OBJECT = 'object';
    public const TYPE_UNSERIALIZABLE = 'unserializable';

    private const CLASS_ANON_PREFIX = 'class@anonymous';

    public string $type;

    /**
     * @var array<int|string, static>|null
     */
    public ?array $node = null;

    public static function dataToXmlDataStructureType(mixed $data): string
    {
        if ($data === null) {
            return static::TYPE_NULL_VALUE;
        }
        if (is_scalar($data)) {
            return static::TYPE_SCALAR_VALUE;
        }
        if (is_array($data)) {
            $i = 0;
            foreach ($data as $k => $v) {
                if ($k !== $i) {
                    return static::TYPE_GENERIC_OBJECT;
                }
                $i++;
            }
            return static::TYPE_TRUE_ARRAY;
        }
        if (is_object($data)) {
            if ($data instanceof stdClass) {
                return static::TYPE_GENERIC_OBJECT;
            }
            $className = get_class($data);
            if (str_starts_with($className, self::CLASS_ANON_PREFIX)) {
                return static::TYPE_GENERIC_OBJECT;
            }

            return $className;
        }
        return static::TYPE_UNSERIALIZABLE;
    }

    public function __construct(mixed $data)
    {
        $this->type = self::dataToXmlDataStructureType($data);
        if (($this->type === static::TYPE_TRUE_ARRAY) || $this->type === static::TYPE_GENERIC_OBJECT) {
            $this->node = [];
            foreach ($data as $k => $v) {
                $this->node[$k] = new self($v);
            }
        }
    }
}
