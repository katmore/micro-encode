<?php
namespace MicroEncode;

use stdClass;

class XmlEncoder extends Encoder {
   const FLAT_XML_VER="0.2";
   const FLAT_XMLNS = 'https://github.com/katmore/flat/wiki/xmlns';

   const OPT_ROOT_NODE = 0;
   const OPT_DEFAULT_NODE = 1;
   const OPT_IDENT_SIZE = 2;
   const OPT_DUMP_OK = 3;
   const OPT_CHECKSUM_ALGOS = 4;
   const OPT_GENERATE_STRUCTURE = 5;
   const OPT_XSI_TYPE_DETECT = 6;
   
   const DEFAULT_OPTVAL = [
      self::OPT_ROOT_NODE=>'fx:data',
      self::OPT_DEFAULT_NODE=>'fx:data',
      self::OPT_IDENT_SIZE=>3,
      self::OPT_DUMP_OK=>false,
      self::OPT_CHECKSUM_ALGOS=>['md5'],
      self::OPT_GENERATE_STRUCTURE=>false,
      self::OPT_XSI_TYPE_DETECT=>true,
   ];

   protected function serialize($data) : string {

      $options = $this->getOptions();
      foreach (static::DEFAULT_OPTVAL as $opt=>$val) {
         if (!isset($options[$opt])) $options[$opt]=$val;
      }
      unset($opt);
      unset($val);
      
      $meta = "";
      $metatag = "";
      if ($options[static::OPT_GENERATE_STRUCTURE]===true) {
         if (static::META_VALUE_GENERIC_OBJECT!==($meta = self::dataToFlatXmlMetaValue($data))) {
            $meta = ' fx:meta="'.$meta.'"';
         } else {
            $meta = " fx:meta=\"extxs:structure\"";
            $structure = new stdClass();
            $dataStructure = json_decode(json_encode(self::createXmlDataStructure($data)));
            $structure->structure = $dataStructure;
            $metatag = self::dataToFlatXml(
               $structure,
               'structure',
               1,
               $options[static::OPT_IDENT_SIZE],
               false,
               [],
               [
                  'namespace'=>'s',
                  'namespaceindentifyer'=>self::FLAT_XMLNS.'-structure'
               ]
            );
         }
            
      }
      $xsins = "";
      if (!empty($options[static::OPT_XSI_TYPE_DETECT]) || !empty($options[static::OPT_XSI_TYPE_DETECT])) $xsins = ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:extxs="'.self::FLAT_XMLNS.'-extxs"';
      $checksum_attr = "";
      $checksum_data = NULL;
      foreach ($options[static::OPT_CHECKSUM_ALGOS] as $algo) {
         
         if (empty($checksum_data)) {
            $checksum_data = json_encode($checksum_data);
         }
         if ($hash = hash($algo,$checksum_data)) {
            if (!hexdec($hash)) continue;
            $attr = preg_replace("/[^a-zA-Z0-9]+/", "", $algo);
            if (is_numeric(substr($attr,0,1))) $attr="hash_".$attr;
            $checksum_attr .= " fx:$attr=\"$hash\"";
         }
      }
      unset($checksum_data);
      
      $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
      
      $xml .= '<' . $options[static::OPT_ROOT_NODE] . ' fx:created="'.date("c").'"'.$checksum_attr."$meta$xsins";
      $ftypeattr = "";
      if (is_null($data)) {
         $ftypeattr .= ' xsi:nil="true"';
      } else {
         //$ftypeattr .= self::typeToXsiAttribute(self::dataToXsiType($data));
         if (is_object($data)) {
            //$ftypeattr .= ' extxs:ObjectType="'.get_class($data).'"';
            $ftypeattr .= self::dataToObjectTypeAttributes($data);
         } elseif (is_array($data)) {
            $ftypeattr .= self::dataToArrayTypeAttributes($data);
         }
      }
      $xml .=" fx:flat-xml-ver=\"".self::FLAT_XML_VER."\" xmlns:fx=\"".self::FLAT_XMLNS."\" xmlns=\"".self::FLAT_XMLNS."-object\"$ftypeattr>\n";
      
      
      
      $xml .= self::dataToFlatXml(
            $data,
            $options[static::OPT_DEFAULT_NODE],
            1,
            $options[static::OPT_IDENT_SIZE],
            $options[static::OPT_DUMP_OK],
            $options[static::OPT_CHECKSUM_ALGOS],
            [
               'xsi_type'=>[
                  'string'=>true,
                  'DateTime'=>true,
                  'hexBinary'=>true,
                  'base64Binary'=>true,
                  'anyURI'=>true,
                  'nil'=>true
               ]
            ]
            );
      $xml .= $metatag;
      $xml .= '</' . $options[static::OPT_ROOT_NODE] . ">";
      
      return $xml;
      
   }
   private static function indent(int $level=1,int $size=3) : string {
      if ( ($level < 1) || ($size < 1) ) return "";
      return str_repeat(" ", $size*$level);
   }

   protected static function createXmlDataStructure($data) : XmlDataStructure {
      return new XmlDataStructure($data);
   }
   const META_VALUE_GENERIC_OBJECT = 'object';
   protected static function dataToFlatXmlMetaValue($data) : string {
      if (XmlDataStructure::dataToXmlDataStructureType($data)===XmlDataStructure::TYPE_GENERIC_OBJECT) {
         return static::META_VALUE_GENERIC_OBJECT;
      }
      if (is_object($data)) {
         return htmlspecialchars(get_class($data), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8');
      }
      return htmlspecialchars(gettype($data), ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8');
   }
   
   private static function typeToXsiAttribute($type) : string {
      
      return 'xsi:type="'.$type.'"';
      
   }
   
   private static function dataToXsiType($data,array $option=null) : string {
      
      $param = [
            'DateTime'=>true,
            'hexBinary'=>true,
            'base64Binary'=>true,
            'anyURI'=>true,
            'string'=>true,
            'Array'=>true,
            'Object'=>true,
            'other'=>true,
      ];
      if (is_array($option)) foreach($option as $key=>$val) {
         if (isset($param[$key])) $param[$key] = $val;
      }
      if (is_scalar($data)) {
         if (is_int($data)) {
            return "xs:integer";
         } else
            if (is_float($data)) {
               return "xs:decimal";
            } else
               if (is_bool($data)) {
                  return "xs:boolean";
               } else
                  if (is_string($data)) {
                     
                     if (is_numeric($data)) {
                        
                        if (intval($data)==$data) {
                           return "extxs:NumericStringInt";
                        } elseif (floatval($data)==$data) {
                           return "extxs:NumericStringFloat";
                        }
                        
                        return "extxs:NumericString";
                     }
                     
                     if ($param['DateTime'] ) {
                        if (false !== ($ts = strtotime($data))) {
                           if (!empty($ts)) {
                              return "xs:DateTime";
                           }
                        }
                     }
                     // if ($param['ip_addr']) {
                     // if (filter_var($ip, FILTER_VALIDATE_IP)) {
                     //
                        // }
                        // }
                        if ($param['hexBinary'] ) {
                           if (ctype_xdigit($data)) return "xs:hexBinary";
                        }
                        // if ($analyze['base64Binary']) {
                        // if (base64_decode($data)) return "base64Binary";
                        // }
                        if ($param['anyURI']) {
                           if(filter_var($data, FILTER_VALIDATE_URL)) {
                              return "xs:anyURI";
                           }
                        }
                        if ($param['string']) return "xs:string";
                        
                        //$xsi=" ".self::typeToXsiAttribute("string");
                  }
      } else {
         if (is_array($data)) {
            if ($param['Array']) {
               $i=0;
               foreach($data as $k=>$v) {
                  if ($i>9) break 1;
                  if (!is_int($k)) return "extxs:Hashmap";
                  $i++;
               }
               return "extxs:Array";
            }
         } else
            if (is_object($data)) {
               if ($param['Object']) return "extxs:Object";
            } else {
               if ($param['other']) return "extxs:".str_replace(" ","",ucwords(gettype($data)));
            }
      }
   }
   private static function dataToObjectTypeAttributes($data) : string {
      if (!is_object($data)) return "";
      $type = get_class($data);
      if ($type=='stdClass') {
         $type = "Generic";
      } else {
         $type = "\\$type";
      }
      //" xsi:type=\"extxs:Object\" extxs:ObjectType=\"".get_class($data)."\"";
      return " xsi:type=\"extxs:Object\" extxs:ObjectType=\"".preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$type)."\"";
   }
   private static function dataToArrayTypeAttributes($data) : string {
      if (!is_array($data)) return "";
      $all_same_type = true;
      $last_type = null;
      $first_value = true;
      $i=0;
      foreach($data as $k=>$v) {
         if ($i>9) break 1;
         if (!is_int($k)) {
            return " xsi:type=\"extxs:Hashmap\"";
         }
         if (!$first_value && (self::dataToXsiType($v,['string'=>true])!==$last_type)) {
            $all_same_type = false;
            break 1;
         }
         $first_value = false;
         $last_type = self::dataToXsiType($v,['string'=>true]);
         $i++;
      }
      if ($all_same_type) {
         return " xsi:type=\"extxs:Array\" extxs:ArrayType=\"".$last_type."[".count($data)."]\"";
      } else {
         return " xsi:type=\"extxs:Array\" extxs:ArrayType=\"extxs:Mixed[".count($data)."]\"";
      }
   }
   private static function dataToFlatXml($data, string $default_node, int $indent_level=1, int $indent_size=3, bool $dump_ok=true,array $checksum=self::DEFAULT_OPTVAL[self::OPT_CHECKSUM_ALGOS],array $options=[]) {
      
      $ns = "";
      $nsidattr="";
      if (!empty($options['namespace'])) {
         if (preg_match('/[a-z_0-9]/i', $options['namespace'])) {
            if (!is_numeric(substr($options['namespace'],0,1))) {
               
               
               
               $ns = $options['namespace'].":";
               
               if (empty($options['namespace_child'])) {
                  if (empty($options['namespaceindentifyer'])) {
                     $nsid = self::FLAT_XMLNS."-".$options['namespace'];
                  } else {
                     $nsid = htmlspecialchars($options['namespaceindentifyer'], ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8');
                  }
                  $nsidattr= " xmlns:".$options['namespace']."=\"$nsid\"";
                  $options['namespace_child']=true;
               }
               
            }
         }
      }
      
      if ($is_arr = is_array($data) || is_object($data)) {
         // if (method_exists( $data, '__toString' )) {
         // return self::dataToFlatXml($data->__toString(), $default_node,$indent_level,$indent_size,$dump_ok,$checksum);
         // }
         $i=0;
         $xml="";
         foreach ($data as $key=>$value) {
            $node = $key;
            $index = NULL;
            if (preg_match('/[^a-z_0-9]/i', $node)) {
               $node = $default_node;
            } else {
               if (is_numeric(substr($node,0,1))) {
                  $node = $default_node;
               }
            }
            if (empty($node)) $node = $default_node;
            $node = strtolower($node);
            $xml .= self::indent($indent_level,$indent_size)."<$ns$node$nsidattr";
            if ($is_arr){
               if (is_int($key)) {
                  $index = $key;
                  //if ($key!=$i) {
                  $xml .= " extxs:index=\"$key\"";
                  //}
               }
               
            }
            if ($key != $node) {
               
               
               if (!$keyval = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8')) {
                  if ($keyval = base64_encode($keyval)) {
                     $keyval = "data:application/octet-stream;base64,$keyval";
                  } else {
                     if($dump_ok) {
                        ob_start();
                        var_dump($key);
                        $dump = ob_get_clean();
                        if ($dump = base64_encode($dump)) {
                           $keyval = "data:application/php-object-dump;base64,$dump";
                        } else {
                           $keyval ="";
                        }
                     } else {
                        $keyval="";
                     }
                  }
               }
               if (!empty($keyval)) {
                  if ($keyval!=$index)
                     $xml .= " extxs:key=\"$keyval\"";
               }
            }
            if (is_array($value) || is_object($value)) {
               $indent_level++;
               if (isset($options[static::OPT_XSI_TYPE_DETECT]) && $options[static::OPT_XSI_TYPE_DETECT]===true) {
                  if (is_array($value)) {
                     $xml .= self::dataToArrayTypeAttributes($value);
                  } else {
                     //$xml .= " xsi:type=\"extxs:Object\" extxs:ObjectType=\"".get_class($value)."\"";
                     $xml .= self::dataToObjectTypeAttributes($value);
                  }
               }
               $xml .= ">\n".
                     self::dataToFlatXml($value, $default_node,$indent_level,$indent_size,$dump_ok,$checksum,$options);
                     $indent_level--;
                     $xml .= self::indent($indent_level,$indent_size)."</$ns$node>\n";
                     
            } else {
               if ($value===null) {
                  if (!empty($options['xsi_type']['nil'])) {
                     $xml .=' xsi:nil="true"'." ";
                  }
                  $xml .= "/>\n";
               } else {
                  if (empty($value)) {
                     if (is_string($value)) {
                        $xml .= ' xsi:nil="true"';
                     }
                     // ob_start();
                     // var_dump($value);
                     // $empty=" empty=\"".htmlspecialchars(trim(ob_get_clean()),ENT_QUOTES | ENT_SUBSTITUTE)."\"";
                     $xml .= "/>\n";
                  } else {
                     $xsi = "";
                     if (!empty($options['xsi_type'])) {
                        if ($xsitype = self::dataToXsiType($value,$options['xsi_type'])) {
                           $xsi=" ".self::typeToXsiAttribute($xsitype);
                        }
                     }
                     
                     $xml .= "$xsi>".self::dataToFlatXml($value, $default_node,$indent_level,$indent_size,$dump_ok,$checksum,$options)."</$ns$node>\n";
                  }
               }
            }
            
            $i++;
         }
         return $xml;
         
      } else {
         if (empty($data)) return "<!--empty-->";
         if ($xml = htmlspecialchars($data, ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8')) return trim($xml);
         $checksum_attr = "";
         foreach ($checksum as $algo) {
            if ($hash = hash($algo,$data)) {
               if (!hexdec($hash)) continue;
               $attr = preg_replace("/[^a-zA-Z0-9]+/", "", $algo);
               if (is_numeric(substr($attr,0,1))) $attr="hash_".$attr;
               $checksum_attr .= " $attr=\"$hash\"";
            }
         }
         if (!$dump_ok) {
            return "<$default_node$checksum_attr><!--unserializable--></$default_node>";
         }
         $type = self::dataToXsiType($data);
         ob_start();
         var_dump($data);
         $dump = ob_get_clean();
         $encoding = "";
         if ($data = htmlspecialchars($dump, ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8')) {
            $encoding = " extxs:encoding=\"none\"";
            $dump = $data;
         } else {
            if ($data = base64_encode($dump)) {
               $dump = $data;
               $encoding = " extxs:encoding=\"base64\"";
            } else {
               //$dump = "<!--unserializable dump-->";
               $dump = "";
               $encoding = " extxs:encoding=\"unserializable\" /";
            }
         }
         $indent_level++;
         $xml = "\n".self::indent($indent_level,$indent_size);
         $xml .= "<extxs:dump extxs:DumpObject=\"$type\"$checksum_attr$encoding>$dump";
         $indent_level--;
         $xml .= "</extxs:dump>\n".self::indent($indent_level,$indent_size);
         return $xml;
      }
   }
   
}
















