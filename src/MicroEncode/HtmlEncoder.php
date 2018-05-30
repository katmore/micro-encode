<?php
namespace MicroEncode;

use stdClass;

class HtmlEncoder extends Encoder {
   
   protected function serialize($data) : string {
      $param = [
            'top_element'=>'div',
            'parent_element'=>'ul',
            'child_element'=>'li'
      ];
      
      $html = "";
      $html .= self::dataToHtml($data,$param['parent_element'],$param['child_element'],0 );
      
      return $html;
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
   private static function indent(int $level=1,int $size=3) : string {
      if ( ($level < 1) || ($size < 1) ) return "";
      return str_repeat(" ", $size*$level);
   }
   private static function dataToHtml($data,$parent_element,$child_element,$indent_level=1, $indent_size=3) {
      
      if ($index = is_array($data) || is_object($data)) {
         $i=0;
         $html = self::indent($indent_level,$indent_size)."<$parent_element data-type=\"".self::dataToMetaValue($data)."\">\n";
         foreach ($data as $key=>$value) {
            $indent_level++;
            $html .= self::indent($indent_level,$indent_size)."<$child_element ";
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
            "<span data-role=\"item-value\" $value_type_str>". self::dataToHtml($value, $parent_element,$child_element,$indent_level);
            $html .= "</span></$child_element><!--/data-item: (".htmlspecialchars($key, ENT_QUOTES).")-->\n";
            $indent_level--;
            $i++;
         }
         $html .= self::indent($indent_level,$indent_size)."</$parent_element>\n";
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