<?php
namespace MicroEncode;
class HtmlEncoder extends Encoder {
   
   protected function _serialize($input) : string {
      $param = array(
            'top_element'=>'div',
            'parent_element'=>'ul',
            'child_element'=>'li'
      );
      
      $html = "";
      //$html .= '<' . $param['top_element'] . ' data-meta="'.htmlspecialchars($type_info, ENT_QUOTES).'">'."\n";
      $html .= self::_data_to_html($input,$param['parent_element'],$param['child_element'],0 );
      //$html .= '</' . $param['top_element'] . '>'."\n";
      
      return $html;
   }
   protected static function _get_meta_value($input) {
      if (is_object($input)) {
         if ("stdClass" == ($className= get_class($input))) {
            return "object";
            //data:application/json;base64,$dump
            // $structure = json_encode(self::_get_structure( $input));
            // //return 'data:application/json;base64,'.base64_encode($structure);
            // return 'data:application/json;'.htmlspecialchars($structure,ENT_QUOTES | ENT_SUBSTITUTE);
         } else {
            return htmlspecialchars($className, ENT_QUOTES | ENT_SUBSTITUTE);
         }
      } else {
         //$type = gettype($input);
         return htmlspecialchars(gettype($input), ENT_QUOTES | ENT_SUBSTITUTE);
      }
   }
   private static function _ident($level=1,$size=3) {
      $ident="";
      for($i=0;$i<$size*$level;$i++) $ident.=" ";
      return $ident;
   }
   private static function _data_to_html($data,$parent_element,$child_element,$indent_level=1, $indent_size=3) {
      
      if ($index = is_array($data) || is_object($data)) {
         $i=0;
         $html = self::_ident($indent_level,$indent_size)."<$parent_element data-type=\"".self::_get_meta_value($data)."\">\n";
         foreach ($data as $key=>$value) {
            $indent_level++;
            $html .= self::_ident($indent_level,$indent_size)."<$child_element ";
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
            "<span data-role=\"item-value\" $value_type_str>". self::_data_to_html($value, $parent_element,$child_element,$indent_level);
            $html .= "</span></$child_element><!--/data-item: (".htmlspecialchars($key, ENT_QUOTES).")-->\n";
            $indent_level--;
            $i++;
         }
         $html .= self::_ident($indent_level,$indent_size)."</$parent_element>\n";
         return $html;
      } else {
         if (is_string($data) && $data=="") return "''";
         if (is_scalar($data) && ctype_print(str_replace(array("\n","\r"),"",(string) $data))) {
            return htmlspecialchars($data, ENT_QUOTES);
         } else {
            //               if (is_bool($data)) {
            //                  return ($data?"true":"false");
            //               }
            ob_start();
            var_dump($data);
            $dump = ob_get_clean();
            return "(dump) <br><pre>".nl2br(htmlspecialchars($dump, ENT_QUOTES))."</pre>";
         }
      }
      
      
   }
}