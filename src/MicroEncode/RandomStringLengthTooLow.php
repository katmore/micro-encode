<?php
namespace MicroEncode;

class RandomStringLengthTooLow extends RandomStringError {
   public function __construct() {
      parent::__construct('length must be 1 or greater');
   }
}