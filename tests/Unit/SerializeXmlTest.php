<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MicroEncode\XmlEncoder;

final class SerializeXmlTest extends TestCase {
   
   const XMLNS_FLAT = 'https://github.com/katmore/flat/wiki/xmlns';
   const XMLNS_FLAT_EXTXS = 'https://github.com/katmore/flat/wiki/xmlns-extxs';
   const XMLNS_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
   
   public function setUp() {
      if (!class_exists('SimpleXMLElement')) {
         $this->markTestSkipped('missing class: SimpleXMLElement');
      }
   }
   public static function randString(int $len,string $char_pool="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789") : string {
      $charPoolIdxCeil=strlen($char_pool)-1;
      $randString="";
      for($i=0;$i<$len;$i++) {
         $randString .= $char_pool[mt_rand(0,$charPoolIdxCeil)];
      }
      return $randString;
   }
   
   public static function randLowerCaseStringStartingWithAlpha(int $len) : string {
      return static::randString(1,"abcdefghijklmnopqrstuvwxyz").static::randString($len-1,"abcdefghijklmnopqrstuvwxyz0123456789");
   }
   
   public static function randUpperCaseStringStartingWithAlpha(int $len) : string {
      return static::randString(1,"ABCDEFGHIJKLMNOPQRSTUVWXYZ").static::randString($len-1,"ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789");
   }
   
   const TRIVIAL_OBJECT_COUNT = 10;
   const TRIVIAL_OBJECT_PROPERTY_COUNT = 50;
   const TRIVIAL_OBJECT_PROPERTY_VALUE_LEN = 100;
   /**
    * provides trivial objects having lower-case property names
    */
   public function trivialObjectWithLowerCasePropertyNameProvider() : array {
      $objectSet = [];
      for($i=0;$i<static::TRIVIAL_OBJECT_COUNT;$i++) {
         $object = [];
         for($p=0;$p<static::TRIVIAL_OBJECT_PROPERTY_COUNT;$p++) {
            for(;;) {
               $propName = static::randLowerCaseStringStartingWithAlpha($p+10);
               if (!isset($object[$propName])) {
                  $object[$propName] = static::randString(static::TRIVIAL_OBJECT_PROPERTY_VALUE_LEN);
                  break 1;
               }
            }
         }
         $objectSet[] = [(object) $object];
      }
      return $objectSet;
   }
   /**
    * provides trivial objects having upper-case property names
    */
   public function trivialObjectWithUpperCasePropertyNameProvider() : array {
      $objectSet = [];
      for($i=0;$i<static::TRIVIAL_OBJECT_COUNT;$i++) {
         $object = [];
         for($p=0;$p<static::TRIVIAL_OBJECT_PROPERTY_COUNT;$p++) {
            for(;;) {
               $propName = static::randUpperCaseStringStartingWithAlpha($p+10);
               if (!isset($object[$propName])) {
                  $object[$propName] = static::randString(static::TRIVIAL_OBJECT_PROPERTY_VALUE_LEN);
                  break 1;
               }
            }
         }
         $objectSet[] = [(object) $object];
      }
      return $objectSet;
   }

   public function compareObjectWithSimpleXmlElement(object $object, SimpleXMLElement $simpleXml, bool $checkNodeKey) {
      
      $objectProperties = get_object_vars($object);
      
      $this->assertEquals(count($objectProperties), $simpleXml->count(),'element count equals object property count');
      $xmlChild = $simpleXml->children();
      
      $i=-1;
      foreach($objectProperties as $propName=>$propValue) {
         $i++;
         
         $element = $xmlChild[$i];
         
         if (is_object($propValue)) {
            $this->compareObjectWithSimpleXmlElement($propValue, $element, $checkNodeKey);
            continue;
         }
         
         if (is_array($propValue)) {
            $this->compareTrueArrayWithSimpleXmlElement($propValue, $element);
            continue;
         }
         
         $this->assertEquals(strtolower($propName), $element->getName(),'element name matches property name');
         $this->assertEquals($propValue, $element->__toString(),'element value matches property value');
         if ($checkNodeKey) {
            $nodeKey = null;
            foreach($element->attributes(static::XMLNS_FLAT_EXTXS) as $attr=>$attrVal) {
               if ($attr==='key') {
                  $nodeKey = (string) $attrVal;
                  break 1;
               }
            }
            unset($attr);
            unset($attrVal);
            $this->assertNotNull($nodeKey,'extxs:key attribute exists');
            $this->assertEquals($propName, $nodeKey,'extxs:key attribute matches property name');
         }
         
         $nodeType = null;
         foreach($element->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
            if ($attr==='type') {
               $nodeType = (string) $attrVal;
               break 1;
            }
         }
         unset($attr);
         unset($attrVal);
         $this->assertNotNull($nodeType,'xsi:type attribute exists');
         $this->assertEquals("xs:string", $nodeType,'xsi:type attribute equals "xs:string"');
         
      }
      unset($propName);
      unset($propValue);
      unset($element);
   }
   
   /**
    * @dataProvider trivialObjectWithLowerCasePropertyNameProvider
    */
   public function testSerializeTrivialObjectsWithLowerCasePropertyNames(object $object) {
      
      $xmlString = (string) new XmlEncoder($object);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $this->compareObjectWithSimpleXmlElement($object,$simpleXml,false);
      
   }
   
   /**
    * @dataProvider trivialObjectWithUpperCasePropertyNameProvider
    */
   public function testSerializeTrivialObjectsWithUpperCasePropertyNames(object $object) {
      
      $xmlString = (string) new XmlEncoder($object);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $this->compareObjectWithSimpleXmlElement($object,$simpleXml,true);
      
   }
   
   const NESTED_OBJECT_COUNT = 10;
   const NESTED_OBJECT_PROPERTY_WITH_STRING_VALUE_COUNT = 50;
   const NESTED_OBJECT_PROPERTY_WITH_OBJECT_VALUE_COUNT = 50;
   const NESTED_OBJECT_PROPERTY_VALUE_LEN = 100;
   public function nestedObjectProvier() : array {
      $objectSet = [];
      for($i=0;$i<static::NESTED_OBJECT_COUNT;$i++) {
         $object = [];
         for($p=0;$p<static::NESTED_OBJECT_PROPERTY_WITH_OBJECT_VALUE_COUNT;$p++) {
            for(;;) {
               $propName = static::randUpperCaseStringStartingWithAlpha($p+10);
               if (!isset($object[$propName])) {
                  $object[$propName] = (object) [ static::randUpperCaseStringStartingWithAlpha($p+10) => static::randString(static::NESTED_OBJECT_PROPERTY_VALUE_LEN)];
                  break 1;
               }
            }
         }
         for($p=0;$p<static::NESTED_OBJECT_PROPERTY_WITH_STRING_VALUE_COUNT;$p++) {
            for(;;) {
               $propName = static::randUpperCaseStringStartingWithAlpha($p+10);
               if (!isset($object[$propName])) {
                  $object[$propName] = static::randString(static::NESTED_OBJECT_PROPERTY_VALUE_LEN);
                  break 1;
               }
            }
         }
         $objectSet[] = [(object) $object];
      }
      return $objectSet;
   }

   /**
    * @dataProvider nestedObjectProvier
    */
   public function testSerializeNestedObjects(object $object) {
      $xmlString = (string) new XmlEncoder($object);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $this->compareObjectWithSimpleXmlElement($object,$simpleXml,true);
   }
   
   public function compareTrueArrayWithSimpleXmlElement(array $array,SimpleXMLElement $simpleXml) {

      $xmlChild = $simpleXml->children(static::XMLNS_FLAT);
      
      $this->assertEquals(count($array), count($xmlChild),'xml element count equals array element count');
      
      
      $i=-1;
      foreach($array as $arrElemIdx=>$arrElemValue) {
         $i++;
         
         $element = $xmlChild[$i];
         
         $this->assertEquals($arrElemValue, $element->__toString(),'xml element value matches array element value');
         
         $nodeIndex = null;
         foreach($element->attributes(static::XMLNS_FLAT_EXTXS) as $attr=>$attrVal) {
            if ($attr==='index') {
               $nodeIndex = (string) $attrVal;
               break 1;
            }
         }
         unset($attr);
         unset($attrVal);
         $this->assertNotNull($nodeIndex,'extxs:index attribute exists');
         $this->assertEquals($arrElemIdx, $nodeIndex,'extxs:index attribute matches array index');
         
         
         $nodeType = null;
         foreach($element->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
            if ($attr==='type') {
               $nodeType = (string) $attrVal;
               break 1;
            }
         }
         unset($attr);
         unset($attrVal);
         $this->assertNotNull($nodeType,'xsi:type attribute exists');
         $this->assertEquals("xs:string", $nodeType,'xsi:type attribute equals "xs:string"');
         
      }
      unset($arrElemIdx);
      unset($arrElemValue);
      unset($element);
   }
   
   const STRING_ARRAY_COUNT = 1;
   const STRING_ARRAY_ELEMENT_COUNT = 2;
   const STRING_ARRAY_ELEMENT_LEN = 100;
   
   public function stringArrayProvider() : array {
      $arraySet = [];
      for($i=0;$i<static::STRING_ARRAY_COUNT;$i++) {
         $array = [];
         for($p=0;$p<static::STRING_ARRAY_ELEMENT_COUNT;$p++) {
            $array []= static::randString(static::STRING_ARRAY_ELEMENT_LEN);
         }
         $arraySet[] = [$array];
      }
      return $arraySet;
   }
   
   /**
    * @dataProvider stringArrayProvider
    */
   public function testSerializeStringArray(array $array) {
      $xmlString = (string) new XmlEncoder($array);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $this->compareTrueArrayWithSimpleXmlElement($array,$simpleXml,true);
   }
   
   
   
   
   
   
   
   
}