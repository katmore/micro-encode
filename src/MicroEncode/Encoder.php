<?php
namespace MicroEncode;

abstract class Encoder {

   final public function __toString() {
      return $this->_encodedValue;
   }
   
   final public function getEncodedValue() : string {
      return $this->_encodedValue;
   }
   
   private $_encodedValue = "";
   
   final public function setInput($input) : void {
      $this->_encodedValue = $this->_serialize($input);
      return $this;
   }
   
   abstract protected function _serialize($input) : string;
   
   protected function _getOptions() : array {
      return $this->_options;
   }
   
   private $_options;
   
   final public function __construct(array $options=[]) {
      $this->_options = $options;
   }
   
}