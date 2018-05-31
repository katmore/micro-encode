<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MicroEncode\XmlEncoder;

final class XmlDataTest extends TestCase {
   
   const XMLNS_SCHEMA_INSTANCE = 'http://www.w3.org/2001/XMLSchema-instance';
   
   public function setUp() {
      if (!class_exists('SimpleXMLElement')) {
         $this->markTestSkipped('missing class: SimpleXMLElement');
      }
   }
   
   
   public function testEmptyStringlData() {
      $object = (object) [
         'my_property'=>"",
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyNil = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='nil') {
            $myPropertyNil = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyNil,'the "my_property" element should have an "xsi:nil" attribute');
      $this->assertEquals('true', $myPropertyNil,'the "my_property" element should have an "xsi:nil" attribute that equals "true"');
      
      
      
   }
      
   public function testNullData() {
      $object = (object) [
         'my_property'=>null,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyNil = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='nil') {
            $myPropertyNil = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyNil,'the "my_property" element should have an "xsi:nil" attribute');
      $this->assertEquals('true', $myPropertyNil,'the "my_property" element should have an "xsi:nil" attribute that equals "true"');
      
      
      
   }
   
   const BINARY_DATA_BASE64 = 'R0lGODlhEAAOALMAAOazToeHh0tLS/7LZv/0jvb29t/f3//Ub//ge8WSLf/rhf/3kdbW1mxsbP//mf///yH5BAAAAAAALAAAAAAQAA4AAARe8L1Ekyky67QZ1hLnjM5UUde0ECwLJoExKcppV0aCcGCmTIHEIUEqjgaORCMxIC6e0CcguWw6aFjsVMkkIr7g77ZKPJjPZqIyd7sJAgVGoEGv2xsBxqNgYPj/gAwXEQA7';
   
   public function testBinaryData() {
      $object = (object) [
         'my_property'=>base64_decode(static::BINARY_DATA_BASE64),
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:Binary', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:Binary"');
      
      
   }
   
   public function testMixedTrueArrayData() {
      $array = [0,1,2,3,4,5,6,7,8,'sample-string'];
      $object = (object) [
         'my_property'=>$array,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:Array', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:Array"');
      
   }
   
   public function testObjectData() {
      $propertyObject = (object) ['a-a'=>0,'b-b'=>1,'c-c'=>2,'d-d'=>3,'e-e'=>4,'f-f'=>5,'g-g'=>6,'h-h'=>7,'i-i'=>8,'j-j'=>9];
      $object = (object) [
         'my_property'=>$propertyObject,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:Object', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:Object"');
      
   }
   
   public function testAssocArrayData() {
      $array = ['a'=>0,'b'=>1,'c'=>2,'d'=>3,'e'=>4,'f'=>5,'g'=>6,'h'=>7,'i'=>8,'j'=>9];
      $object = (object) [
         'my_property'=>$array,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:Hashmap', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:Hashmap"');
      
   }
   
   public function testTrueArrayData() {
      $array = [0,1,2,3,4,5,6,7,8,9];
      $object = (object) [
         'my_property'=>$array,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:Array', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:Array"');

   }
   
   public function testAnyUriData() {
      $uri = 'https://example.com/my_uri';
      $object = (object) [
         'my_property'=>$uri,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('xs:anyURI', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "xs:anyURI"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $uri,$myPropertyVal ,'the "my_property" element should have expected value');
   }
   
   const DATETIME_STRING_DATA_COUNT = 10;
   public function dateTimeStringProvider() : array {
      $time = time();
      $dateTimeSet = [[date('c',$time)]];
      $dateTimeSetCeil = static::DATETIME_STRING_DATA_COUNT-1;
      for($i=0;$i<$dateTimeSetCeil;$i++) {
         $time = strtotime("+1 DAY",$time);
         $dateTimeSet []= [date('c',$time)];
      }
      return $dateTimeSet;
   }
   /**
    * @dataProvider dateTimeStringProvider
    */
   public function testDateTimeData(string $dateTimeString) {
      
      $object = (object) [
         'my_property'=>$dateTimeString,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('xs:DateTime', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "xs:DateTime"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $dateTimeString,$myPropertyVal ,'the "my_property" element should have expected value');
   }
   
   public function booleanDataProvider() : array {
      return [
         [true],
         [false],
      ];
   }
   
   /**
    * @dataProvider booleanDataProvider
    */
   public function testBooleanData(bool $boolean) {
      $object = (object) [
         'my_property'=>$boolean,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('xs:boolean', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "xs:boolean"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $boolean,$myPropertyVal ,'the "my_property" element should have expected boolean value');
      
   }
   
   const DECIMAL_DATA_COUNT = 100;
   public function decimalDataProvider() : array {
      $decimalSet = [];
      $decimalSetCeil = static::DECIMAL_DATA_COUNT-1;
      for($i=1;$i<$decimalSetCeil;$i++) {
         $decimalSet []= [(float) ($i / 100)];
      }
      return $decimalSet;
   }
   
   /**
    * @dataProvider decimalDataProvider
    */
   public function testDecimalData(float $decimal) {
      $object = (object) [
         'my_property'=>$decimal,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('xs:decimal', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "xs:decimal"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $decimal,$myPropertyVal ,'the "my_property" element should have expected decimal value');

   }
   
   /**
    * @dataProvider decimalDataProvider
    */
   public function testNumericStringFloatData(float $decimal) {
      $object = (object) [
         'my_property'=>(string) $decimal,
      ];
      
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:NumericStringFloat', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:NumericStringFloat"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $decimal,$myPropertyVal ,'the "my_property" element should have expected value');
      
   }
   
   const INTEGER_DATA_COUNT = 100;
   public function integerDataProvider() : array {
      $integerSet = [];
      for($i=0;$i<static::INTEGER_DATA_COUNT;$i++) {
         $integerSet []= [$i];
      }
      return $integerSet;
   }
   
   /**
    * @dataProvider integerDataProvider
    */
   public function testNumericStringIntData(float $decimal) {
      $object = (object) [
         'my_property'=>(string) $decimal,
      ];
      
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('extxs:NumericStringInt', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "extxs:NumericStringInt"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $decimal,$myPropertyVal ,'the "my_property" element should have expected value');
      
   }
   
   /**
    * @dataProvider integerDataProvider
    */
   public function testIntegerData(int $integer) {
      $object = (object) [
         'my_property'=>$integer,
      ];
      $xmlString = (string) new XmlEncoder($object);
      $simpleXml = new SimpleXMLElement($xmlString);
      
      //echo $simpleXml->asXML()."\n";
      
      $this->assertEquals(1, $simpleXml->count());
      
      $myProperty = null;
      foreach($simpleXml->children() as $element) {
         if ($element->getName()==='my_property') {
            $myProperty = $element;
         }
      }
      unset($element);
      
      $this->assertNotNull($myProperty,'the "my_property" element should exist');
      
      $myPropertyType = null;
      foreach($myProperty->attributes(static::XMLNS_SCHEMA_INSTANCE) as $attr=>$attrVal) {
         if ($attr==='type') {
            $myPropertyType = (string) $attrVal;
         }
      }
      unset($attr);
      unset($attrVal);
      
      $this->assertNotNull($myPropertyType,'the "my_property" element should have an "xsi:type" attribute');
      $this->assertEquals('xs:integer', $myPropertyType,'the "my_property" element should have an "xsi:type" attribute that equals "xs:integer"');
      
      $myPropertyVal = trim((string) $myProperty);
      
      $this->assertEquals((string) $integer,$myPropertyVal ,'the "my_property" element should have expected integer value');
      
      
   }
   
}