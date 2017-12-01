<?php
namespace MicroEncode;

class RandomStringController {
   
   /**
    * int the default length of the random string
    */
   const DEFAULT_LENGTH=20;
   
   /**
    * string the fallback value for the pool of characters randomly chosen from while 
    *    generating the random string
    */
   const CHARPOOL_FALLBACK = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
   
   /**
    * Provides an array containing each unique character of the specified charpool
    * 
    * @return string[]
    * 
    * @throws \MicroEncode\RandomStringCharpoolTooShort if charpool does not contain at least 2 unique characters
    * @static
    */
   private static function _filterCharpool(string $charpool) : array {
      
      $charpool = array_unique(str_split($charpool));
      
      if (count($charpool) < 2) {
         throw new RandomStringCharpoolTooShort;
      }
      
      return $charpool;
      
   }
   
   /**
    * Generates a random string
    *
    * @param int $length length of the random string
    * @param string $charpool the pool of characters randomly chosen from while generating the random string
    *
    * @return string
    * @static
    */
   private static function _generateRandomString(int $length, array $filteredCharpool) : string {
      
      $max = count($filteredCharpool) - 1;
      
      $str = "";
      
      for ($l=0;$l<$length;$l++) {
         $str .= $filteredCharpool[random_int(0,$max)];
      }
      
      return $str;
      
   }
   
   /**
    * Generates a random string
    * 
    * @param int $length length of the random string; must be 1 or greater
    * @param string $charpool the pool of characters randomly chosen from 
    *    while generating the random string; must contain at least 2 unique characters
    * 
    * @return string
    * @throws \MicroEncode\RandomStringCharpoolTooShort if charpool does not contain at least 2 unique characters
    * @throws \MicroEncode\RandomStringLengthTooLow specified length is less than 1
    * @static
    */
   public static function generateRandomString(int $length=self::DEFAULT_LENGTH, string $charpool=self::CHARPOOL_FALLBACK) : string {
      
      if ($length < 1) {
         throw new RandomStringLengthTooLow;
      }
      
      return static::_generateRandomString($length, static::_filterCharpool($charpool));

   }

   /**
    * @var array the pool of characters to be randomly chosen from while generating the random string
    */
   private $_filteredCharpool;
   
   /**
    * @var int length of the random string
    */
   private $_length;
   
   /**
    * @param int $length length of the random string; must be 1 or greater
    * @param string $charpool the pool of characters randomly chosen from 
    *    while generating the random string; must contain at least 2 unique characters
    * 
    * @throws \MicroEncode\RandomStringCharpoolTooShort if charpool does not contain at least 2 unique characters
    * @throws \MicroEncode\RandomStringLengthTooLow specified length is less than 1
    */
   public function __construct(int $length=self::DEFAULT_LENGTH, string $charpool=self::CHARPOOL_FALLBACK) {
      
      if ($length < 1) {
         throw new RandomStringLengthTooLow;
      }
      
      $this->_length = $length;
      
      $this->_filteredCharpool = static::_filterCharpool($charpool);
      
   }
   
   /**
    * Generates a random string
    * @return string
    */
   public function getString() : string {
      
      return static::_generateRandomString($this->_length, $this->_filteredCharpool);
      
   }
   
   /**
    * @see \MicroEncode\RandomStringController::getString()
    */
   public function __toString() {
      
      return $this->getString();
      
   }
   
   /**
    * @see \MicroEncode\RandomStringController::getString()
    */
   public function __invoke() {
      
      return $this->getString();
      
   }
   

}















