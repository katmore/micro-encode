<?php
namespace MicroEncode;

abstract class Encoder implements EncoderInterface {

   final public function __toString() : string {
      return $this->encodedValue;
   }
   
   final public function getEncodedValue() : string {
      return $this->encodedValue;
   }
   
   private $encodedValue;
   
   abstract protected function serialize($data) : string;
   
   final protected function getOptions() : array {
      return $this->options;
   }
   
   private $options;
   
   final public function __construct($data, array $options=[]) {
      $this->options = $options;
      $this->encodedValue = $this->serialize($data);
   }
   
}