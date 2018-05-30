<?php
namespace MicroEncode;

interface EncoderInterface {
   public function __toString() : string;
   public function getEncodedValue() : string;
   public function __construct($data, array $options=[]);
}