<?php
/*
 * part of the katmore/micro-encode project
 *
 * Copyright (c) 2012-2026 Doug Bird. All Rights Reserved.
 */

declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use MicroEncode\HtmlEncoder;
use MicroEncode\XmlEncoder;

final class EncoderInterfaceTest extends TestCase {
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
   
   const SAMPLE_DATA_COUNT = 10;
   const SAMPLE_DATA_PROPERTY_COUNT = 50;
   const SAMPLE_DATA_PROPERTY_VALUE_LEN = 100;

   public static function sampleDataProvider() : array {
      $dataSet = [];
      for($i=0;$i<static::SAMPLE_DATA_COUNT;$i++) {
         $data = [];
         for($p=0;$p<static::SAMPLE_DATA_PROPERTY_COUNT;$p++) {
            for(;;) {
               $propName = static::randStringStartingWithAlpha($p+10);
               if (!isset($data[$propName])) {
                  $data[$propName] = static::randString(static::SAMPLE_DATA_PROPERTY_VALUE_LEN);
                  break 1;
               }
            }
         }
         $dataSet[] = [(object) $data];
      }
      return $dataSet;
   }
   
   #[DataProvider('sampleDataProvider')]
   public function testXmlEncodedValueMatchesMagicStringMethod($data) {
      $xmlEncoder = new XmlEncoder($data);
      $this->assertEquals($xmlEncoder->__toString(), $xmlEncoder->getEncodedValue());
   }

   #[DataProvider('sampleDataProvider')]
   public function testHtmlEncodedValueMatchesMagicStringMethod($data) {
      $htmlEncoder = new HtmlEncoder($data);
      $this->assertEquals($htmlEncoder->__toString(), $htmlEncoder->getEncodedValue());
   }
   
   
   
   
   
   
   
   
   
   
   
   
   
}