<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use MicroEncode\HtmlEncoder;

final class SerializeHtmlTest extends TestCase {
   
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
   
   private static function randStringStartingWithAlpha(int $len) : string {
      return static::randString(1,"abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ").static::randString($len-1);
   }
   
   const TRIVIAL_OBJECT_COUNT = 10;
   const TRIVIAL_OBJECT_PROPERTY_COUNT = 50;
   const TRIVIAL_OBJECT_PROPERTY_VALUE_LEN = 100;
   /**
    * provides trivial objects
    */
   public function trivialObjectProvider() : array {
      $objectSet = [];
      for($i=0;$i<static::TRIVIAL_OBJECT_COUNT;$i++) {
         $object = [];
         for($p=0;$p<static::TRIVIAL_OBJECT_PROPERTY_COUNT;$p++) {
            for(;;) {
               $propName = static::randStringStartingWithAlpha($p+10);
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
    * @dataProvider trivialObjectProvider
    */
   public function testSerializeTrivialObjects(object $object) {
      
      
      $htmlString = (string) new HtmlEncoder($object);
      $doc = new DOMDocument();
      $doc->loadHTML($htmlString);
      $simpleXml = simplexml_import_dom($doc);
      
      /**
       * @var $li SimpleXmlElement
       */
      $li = null;
      if (isset($simpleXml->{'body'}) && isset($simpleXml->{'body'}->{'ul'})) {
         $li = $simpleXml->{'body'}->{'ul'}->children();
      }
      
      
      $this->assertNotNull($li,'root element "ul" exists with children');

      
      $this->assertEquals(static::TRIVIAL_OBJECT_PROPERTY_COUNT, $li->count(),'element count equals object property count');
      
      
      $i=0;
      foreach($object as $propName=>$propValue) {
         
         $element = $li[$i];
         
         $nodeKey = null;
         $nodeRole = null;
         $nodeIndex = null;
         foreach($element->attributes() as $attr=>$attrVal) {
            if ($attr==='data-key') {
               $nodeKey = (string) $attrVal;
            }
            if ($attr==='data-role') {
               $nodeRole = (string) $attrVal;
            }
            if ($attr==='data-index') {
               $nodeIndex = (string) $attrVal;
            }
         }
         unset($attr);
         unset($attrVal);
         
         $this->assertNotNull($nodeKey,'data-key attribute exists');
         $this->assertEquals($propName, $nodeKey,'data-key attribute matches property name');
         
         $this->assertNotNull($nodeRole,'data-role attribute exists');
         $this->assertEquals('item', $nodeRole,'data-role attribute equals "item"');
         
         $this->assertNotNull($nodeIndex,'data-index attribute exists');
         $this->assertEquals($i, $nodeIndex,'data-index attribute matches expected index "'.$i.'"');
         
         $itemKeyElement = null;
         $itemValueElement = null;
         foreach($element->children() as $liChild) {
            foreach($liChild->attributes() as $attr=>$attrVal) {
               if (($attr==='data-role') && ($attrVal=='item-key')) {
                  $itemKeyElement = $liChild;
               } else
               if (($attr==='data-role') && ($attrVal=='item-value')) {
                  $itemValueElement = $liChild;
               }
            }
            unset($attr);
            unset($attrVal);
         }
         unset($liChild);
         
         $this->assertNotNull($itemKeyElement,'element with data-role="item-key" attribute exists');
         $this->assertEquals($propName, $itemKeyElement->__toString(),'element with data-role="item-key" attribute has value equal to property name');
         
         $this->assertNotNull($itemValueElement,'element with data-role="item-value" attribute exists');
         $this->assertEquals($propValue, $itemValueElement->__toString(),'element with data-role="item-value" attribute has value equal to property value');
         
         $itemValueDataType = null;
         foreach($itemValueElement->attributes() as $attr=>$attrVal) {
            if ($attr==='data-type') {
               $itemValueDataType = $attrVal->__toString();
               break 1;
            }
         }
         unset($attr);
         unset($attrVal);
         $this->assertNotNull($itemValueDataType,'element with data-role="item-value" attribute has "data-type" attribute');
         $this->assertEquals("string", $itemValueDataType,'element with data-role="item-value" attribute has "data-type" attribute equal to "string"');
         //var_dump($itemValueDataType);
         //foreach($element->children() as $li;
         
         
         $i++;
      }
      unset($propName);
      unset($propValue);
      unset($element);
      
   }
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
   
}