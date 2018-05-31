<?php
namespace MicroEncode;

use stdClass;

class XmlDataStructure {
   /**
    * @string
    */
   public $type;
   
   /**
    * @array
    */
   public $node;
   
   const TYPE_SCALAR_VALUE = 'scalar';
   const TYPE_NULL_VALUE = 'null';
   const TYPE_TRUE_ARRAY = 'array';
   const TYPE_GENERIC_OBJECT = 'object';
   const TYPE_UNSERIALIZABLE = 'unserializable';
   
   
   public static function dataToXmlDataStructureType($data) : string {
      if ($data === null) {
         return static::TYPE_NULL_VALUE;
      }
      if (is_scalar($data)) {
         return static::TYPE_SCALAR_VALUE;
      } 
      if (is_array($data)) {
         $i=0;
         foreach($data as $k=>$v) {
            if ($k!==$i) return static::TYPE_GENERIC_OBJECT;
            $i++;
         }
         unset($k);
         unset($v);
         return static::TYPE_TRUE_ARRAY;
      } 
      if (is_object($data)) {
         if ($data instanceof stdClass) {
            return static::TYPE_GENERIC_OBJECT;
         }
         $className = get_class($data);
         $classAnonPrefix = 'class@anonymous';
         $classAnonPrefixLen = strlen($classAnonPrefix);
         if (substr($className,0,$classAnonPrefixLen)===$classAnonPrefix) {
            return static::TYPE_GENERIC_OBJECT;
         }
         
         return $className;
      }
      return static::TYPE_UNSERIALIZABLE;
   }
   
   public function __construct($data) {
      $this->type = self::dataToXmlDataStructureType($data);
      if (($this->type === static::TYPE_TRUE_ARRAY) || $this->type === static::TYPE_GENERIC_OBJECT) {
         $this->node = [];
         foreach ($data as $k=>$v) {
            $this->node[$k] = new static($v);
         }
         unset($k);
         unset($v);
      }
   }
   
}