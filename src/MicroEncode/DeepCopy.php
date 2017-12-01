<?php
namespace MicroEncode;

class DeepCopy {
   
   private static function _only_bool_false(array $arr=null,$key=null,$invalid_return=null) {
      if ($arr && $key) {
         if (isset($arr[$key])) {
            if ($arr[$key]===false) return false;
         }
      }
      return $invalid_return;
   }
   
   public static function data($data=NULL,array $options=NULL) {
      $objects_to_stdClass = false;
      $objects_to_assoc = false;
      if (!$objects_to_stdClass = static::only_bool_true($options,'objects_to_stdClass')) {
         $objects_to_stdClass = static::only_bool_true($options,'objects_to_object');
      }
      if (!$objects_to_stdClass) {
         $objects_to_assoc = static::only_bool_true($options,'objects_to_assoc');
      }
      if ($data) {
         if (is_object($data)) {
            if ($objects_to_stdClass || $objects_to_assoc) {
               $clone = (array) $data;
               if ($objects_to_stdClass) {
                  $obj = new \stdClass();
                  foreach($clone as $prop=>$val) {
                     $obj->$prop = self::data($val,$options);
                  }
               } else {
                  $obj = [];
                  foreach($clone as $prop=>$val) {
                     $obj[$prop] = self::data($val,$options);
                  }
               }
               return $obj;
            } else {
               return clone $data;
            }
         } else
            if (is_array($data)) {
               return self::arr($data,$options);
            } else
               if (is_scalar($data)) {
                  return $data;
               }
      }
   }
   public static function arr(array $arr=NULL,array $options=NULL) {
      if ($arr) {
         /*
          * deep recursive copy array
          */
         return array_map(function($el) use(& $options){
            if (is_array($el)) return self::arr($el,$options);
            return self::data($el,$options);
         }, $arr);
      }
   }
}