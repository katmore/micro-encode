<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MicroEncode\XmlEncoder;

final class SampleDataClass {
   public $my_property;
   public function __construct() {
      $this->my_property = XmlStructureTest::randString(100);
   }
}

final class XmlStructureTest extends TestCase {
   
   const XML_FLAT = 'https://github.com/katmore/flat/wiki/xmlns';
   const XMLNS_FLAT_EXTXS = 'https://github.com/katmore/flat/wiki/xmlns-extxs';
   const XMLNS_FLAT_STRUCTURE = 'https://github.com/katmore/flat/wiki/xmlns-structure';
   
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
   
   public static function randStringStartingWithAlpha(int $len) : string {
      return static::randString(1,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ").static::randString($len-1);
   }
   
   const INTEGER_DATA_COUNT = 100;
   public function integerDataProvider() : array {
      $integerSet = [];
      for($i=0;$i<static::INTEGER_DATA_COUNT;$i++) {
         $integerSet []= [$i];
      }
      return $integerSet;
   }
   
   public function testNullDataStructure() {
      $xmlString = (string) new XmlEncoder(null,[
         XmlEncoder::OPT_GENERATE_STRUCTURE=>true,
      ]);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $attributes = $simpleXml->attributes(static::XML_FLAT);
      
      $metaVal = null;
      foreach($attributes as $attr=>$attrVal) {
         if ($attr==='meta') {
            $metaVal = (string) $attrVal;
            break 1;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($metaVal,'root element should contain "fx:meta" attribute');
      
      $this->assertEquals('NULL',$metaVal, 'root element "fx:meta" attribute value should have expected value');
   }
   
   /**
    * @dataProvider integerDataProvider
    */
   public function testIntegerDataStructure(int $integer) {
      $xmlString = (string) new XmlEncoder($integer,[
         XmlEncoder::OPT_GENERATE_STRUCTURE=>true,
      ]);
      
      //echo "xmlString: $xmlString\n";
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $attributes = $simpleXml->attributes(static::XML_FLAT);
      
      $metaVal = null;
      foreach($attributes as $attr=>$attrVal) {
         if ($attr==='meta') {
            $metaVal = (string) $attrVal;
            break 1;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($metaVal,'root element should contain "fx:meta" attribute');
      
      $this->assertEquals('integer',$metaVal, 'root element "fx:meta" attribute value should have expected value');
      
      $integerVal = trim($simpleXml->__toString());
      $this->assertEquals((string) $integer, $integerVal);
      
      
   }
   
   public function testSampleDataClassStructure() {
      $object = new SampleDataClass();
      $objectType = '\\'.get_class($object);
      
      $xmlString = (string) new XmlEncoder($object,[
         XmlEncoder::OPT_GENERATE_STRUCTURE=>true,
      ]);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $objectTypeAttrVal = null;
      foreach($simpleXml->attributes(static::XMLNS_FLAT_EXTXS) as $attr=>$attrVal) {
         if (($attr==='ObjectType')) {
            $objectTypeAttrVal = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($objectTypeAttrVal,'root element has "extxs:ObjectType" attribute');
      $this->assertEquals($objectTypeAttrVal, $objectTypeAttrVal,'root element "extxs:ObjectType" attribute has expected value');
      
   }
   
   public function testAnonymousClassStructure() {
      
      $object = new class() {
         public $my_property;
         public function __construct() {
            $this->my_property = XmlStructureTest::randString(100);
         }
      };
      
      $xmlString = (string) new XmlEncoder($object,[
         XmlEncoder::OPT_GENERATE_STRUCTURE=>true,
      ]);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $structureNode = $simpleXml->children(static::XMLNS_FLAT_STRUCTURE);
      
      $this->assertEquals(1, $structureNode->count());
      
      
      $structure = $structureNode->children(static::XMLNS_FLAT_STRUCTURE);
      
      $sType = null;
      $sNodeElement = null;
      foreach($structure as $element) {
         if ($element->getName()==='type') {
            $sType = $element->__toString();
         } else
            if ($element->getName()==='node') {
               $sNodeElement = $element;
            }
      }
      unset($element);
      
      $this->assertNotNull($sType,'structure element contains a "type" child element');
      $this->assertEquals('object', $sType);
      
      $this->assertNotNull($sNodeElement,'structure element contains a "node" child element');
      
      $sNodeProperties = $sNodeElement->children(static::XMLNS_FLAT_STRUCTURE);
      
      $myProperty = null;
      foreach($sNodeProperties as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'structure element contains a "node" child element that contains a "my_property" child element');
      
      $myPropertyStructure = $myProperty->children(static::XMLNS_FLAT_STRUCTURE);
      
      $sType = null;
      $sNodeElement = null;
      foreach($myPropertyStructure as $element) {
         if ($element->getName()==='type') {
            $sType = $element->__toString();
         } else
            if ($element->getName()==='node') {
               $sNodeElement = $element;
            }
      }
      unset($element);
      
      $this->assertNotNull($sType,'structure element contains a "node" child element that contains a "my_property" child element that contains a "type" child element');
      $this->assertEquals('scalar', $sType);
      
      $this->assertNotNull($sNodeElement,'structure element contains a "node" child element that contains a "my_property" child element that contains a "node" child element');
      $this->assertEmpty($sNodeElement->__toString(),'"structure element contains a "node" child element that contains a "my_property" child element that contains a "node" child element with an empty value');
      
      
   }
   
   public function testGenericObjectStructure() {
   
      $genericObject = (object) [
         'my_property'=>static::randString(100),
      ];
      
      $xmlString = (string) new XmlEncoder($genericObject,[
         XmlEncoder::OPT_GENERATE_STRUCTURE=>true,
      ]);
      
      $simpleXml = new SimpleXMLElement($xmlString);
      
      $structureNode = $simpleXml->children(static::XMLNS_FLAT_STRUCTURE);
      
      $this->assertEquals(1, $structureNode->count());
      
      
      $structure = $structureNode->children(static::XMLNS_FLAT_STRUCTURE);
      
      $sType = null;
      $sNodeElement = null;
      foreach($structure as $element) {
         if ($element->getName()==='type') {
            $sType = $element->__toString();
         } else
            if ($element->getName()==='node') {
               $sNodeElement = $element;
            }
      }
      unset($element);
      
      $this->assertNotNull($sType,'structure element contains a "type" child element');
      $this->assertEquals('object', $sType);
      
      $this->assertNotNull($sNodeElement,'structure element contains a "node" child element');
      
      $sNodeProperties = $sNodeElement->children(static::XMLNS_FLAT_STRUCTURE);
      
      $myProperty = null;
      foreach($sNodeProperties as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'structure element contains a "node" child element that contains a "my_property" child element');
      
      $myPropertyStructure = $myProperty->children(static::XMLNS_FLAT_STRUCTURE);
      
      $sType = null;
      $sNodeElement = null;
      foreach($myPropertyStructure as $element) {
         if ($element->getName()==='type') {
            $sType = $element->__toString();
         } else
            if ($element->getName()==='node') {
               $sNodeElement = $element;
            }
      }
      unset($element);
      
      $this->assertNotNull($sType,'structure element contains a "node" child element that contains a "my_property" child element that contains a "type" child element');
      $this->assertEquals('scalar', $sType);
      
      $this->assertNotNull($sNodeElement,'structure element contains a "node" child element that contains a "my_property" child element that contains a "node" child element');
      $this->assertEmpty($sNodeElement->__toString(),'"structure element contains a "node" child element that contains a "my_property" child element that contains a "node" child element with an empty value');
      
   }
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
}