<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MicroEncode\XmlEncoder;

final class SerializeXmlTest extends TestCase {
   
   const XMLNS_FLAT_EXTXS = 'https://github.com/katmore/flat/wiki/xmlns-extxs';
   const XMLNS_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
   
   public function setUp() {
      if (!class_exists('SimpleXMLElement')) {
         $this->markTestSkipped('missing class: SimpleXMLElement');
      }
   }
   private static function randString(int $len,string $char_pool="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789") : string {
      $charPoolIdxCeil=strlen($char_pool)-1;
      $randString="";
      for($i=0;$i<$len;$i++) {
         $randString .= $char_pool[mt_rand(0,$charPoolIdxCeil)];
      }
      return $randString;
   }
   
   private static function randLowerCaseStringStartingWithAlpha(int $len) : string {
      return static::randString(1,"abcdefghijklmnopqrstuvwxyz").static::randString($len-1,"abcdefghijklmnopqrstuvwxyz0123456789");
   }
   
   private static function randUpperCaseStringStartingWithAlpha(int $len) : string {
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

   
   /**
    * @dataProvider trivialObjectWithLowerCasePropertyNameProvider
    */
   public function testSerializeTrivialObjectsWithLowerCasePropertyNames(object $object) {

      
      $xmlString = (string) new XmlEncoder($object);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $this->assertEquals(static::TRIVIAL_OBJECT_PROPERTY_COUNT, $simpleXml->count(),'element count equals object property count');
      $xmlChild = $simpleXml->children();
      
      $i=0;
      foreach($object as $propName=>$propValue) {
         
         $element = $xmlChild[$i];
         
         $this->assertEquals(strtolower($propName), $element->getName(),'element name matches property name');
         $this->assertEquals($propValue, $element->__toString(),'element value matches property value');
         
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
         
         $i++;
      }
      unset($propName);
      unset($propValue);
      unset($element);

   }
   
   
   
   /**
    * @dataProvider trivialObjectWithUpperCasePropertyNameProvider
    */
   public function testSerializeTrivialObjectsWithUpperCasePropertyNames(object $object) {
      
      
      $xmlString = (string) new XmlEncoder($object);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $this->assertEquals(static::TRIVIAL_OBJECT_PROPERTY_COUNT, $simpleXml->count(),'element count equals object property count');
      $xmlChild = $simpleXml->children();
      
      $i=0;
      foreach($object as $propName=>$propValue) {
         
         $element = $xmlChild[$i];
         
         $this->assertEquals(strtolower($propName), $element->getName(),'element name matches property name');
         
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
         
         $i++;
      }
      unset($propName);
      unset($propValue);
      unset($element);
      
   }
   
   
   
   
   
   
   
   
   
   
   
   
   
}