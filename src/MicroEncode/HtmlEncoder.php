<?php
/*
 * part of the katmore/micro-encode project
 * 
 * Copyright (c) 2012-2018 Doug Bird. All Rights Reserved.
 */
namespace MicroEncode;

use stdClass;

/**
 * Serializes data to HTML
 * 
 * @author D. Bird <retran@gmail.com>
 */
class HtmlEncoder implements EncoderInterface {
   
   const OPT_PARENT_ELEMENT = 0;
   const OPT_CHILD_ELEMENT = 1;
   
   const DEFAULT_OPTVAL = [
      self::OPT_PARENT_ELEMENT=>'ul',
      self::OPT_CHILD_ELEMENT=>'li',
   ];
   
   /**
    * @return string HTML serialized data
    */
   public function __toString(): string {
      return $this->encodedValue;
   }
   
   /**
    * @return string HTML serialized data
    */
   public function getEncodedValue(): string {
      return $this->encodedValue;
   }
   
   /**
    * @var string
    */
   private $encodedValue;
   
   /**
    * @param bool|int|float|string|object|array $data data to serialize to HTML
    * @param array $options associative array of one or more options:
    * <ul>
    *    <li><b>string</b> <i>\MicroEncode\HtmlEncoder::OPT_PARENT_ELEMENT</i>: Specify parent element name; default: 'ul'.</li>
    *    <li><b>string</b> <i>\MicroEncode\HtmlEncoder::OPT_PARENT_ELEMENT</i>: Specify name of any child elements; default: 'li'.</li>
    * </ul>
    */
   public function __construct($data, array $options=self::DEFAULT_OPTVAL) {

      foreach (static::DEFAULT_OPTVAL as $opt=>$val) {
         if (!isset($options[$opt])) $options[$opt]=$val;
      }
      unset($opt);
      unset($val);
      
      $this->encodedValue = "";
      $this->encodedValue .= static::dataToHtml($data,$options[static::OPT_PARENT_ELEMENT],$options[static::OPT_CHILD_ELEMENT],0 );
      
      return $this->encodedValue;
   }

   const META_VALUE_GENERIC_OBJECT = 'object';
   protected static function dataToMetaValue($data) : string {
      
      if (is_object($data)) {
         if ($data instanceof stdClass) {
            return static::META_VALUE_GENERIC_OBJECT;
         } 
         return htmlspecialchars(get_class($data), ENT_QUOTES | ENT_SUBSTITUTE);
      }  
      return htmlspecialchars(gettype($data), ENT_QUOTES | ENT_SUBSTITUTE);
      
   }
   protected static function indent(int $level=1,int $size=3) : string {
      if ( ($level < 1) || ($size < 1) ) return "";
      return str_repeat(" ", $size*$level);
   }
   protected static function dataToHtml($data,$parent_element,$child_element,$indent_level=1, $indent_size=3) {
      
      if ($index = is_array($data) || is_object($data)) {
         $i=0;
         $html = static::indent($indent_level,$indent_size)."<$parent_element data-type=\"".static::dataToMetaValue($data)."\">\n";
         foreach ($data as $key=>$value) {
            $indent_level++;
            $html .= static::indent($indent_level,$indent_size)."<$child_element ";
            if ($index) $html .= "data-index=\"$i\" ";
            $html .= "data-key=\"".htmlspecialchars($key, ENT_QUOTES)."\" data-role=\"item\">";
            if (sprintf("%d",$key)!=$key) {
               $html .= "<span data-role=\"item-key\">".htmlspecialchars($key, ENT_QUOTES)."</span>".":&nbsp;";
            } else {
               $html .= "&nbsp;";
            }
            $value_type_str = "";
            if (is_scalar($value)) {
               $value_type_str = 'data-type="'.gettype($value).'"';
               if (is_bool($value)) {
                  $value_type_str .= ' data-boolean-value="'.($value?'true':'false').'"';
               }
            }
            if (is_null($value)) {
               $value_type_str = 'data-type="null"';
            }
            $html .=
            "<span data-role=\"item-value\" $value_type_str>". static::dataToHtml($value, $parent_element,$child_element,$indent_level);
            $html .= "</span></$child_element><!--/data-item: (".htmlspecialchars($key, ENT_QUOTES).")-->\n";
            $indent_level--;
            $i++;
         }
         $html .= static::indent($indent_level,$indent_size)."</$parent_element>\n";
         return $html;
      } 
      if (is_string($data) && $data=="") return "''";
      if (is_scalar($data) && ctype_print(str_replace(["\n","\r"],"",(string) $data))) {
         return htmlspecialchars($data, ENT_QUOTES);
      } 
      ob_start();
      var_dump($data);
      $dump = ob_get_clean();
      return "(dump) <br><pre>".nl2br(htmlspecialchars($dump, ENT_QUOTES))."</pre>";

      
   }


}