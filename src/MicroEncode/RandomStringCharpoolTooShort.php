<?php
namespace MicroEncode;

class RandomStringCharpoolTooShort extends RandomStringError {
   public function __construct() {
      parent::__construct('charpool must contain at least 2 unique characters');
   }
}