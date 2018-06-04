<?php
namespace MicroEncode;

use stdClass;
use finfo;

/**
 * Serializes data to XML
 *
 * @author D. Bird <retran@gmail.com>
 */
class XmlEncoder implements  EncoderInterface {

   const FLAT_XMLNS = 'https://github.com/katmore/flat/wiki/xmlns';

   const OPT_ROOT_NODE = 0;
   const OPT_DEFAULT_NODE = 1;
   const OPT_IDENT_SIZE = 2;
   const OPT_DUMP_OK = 3;
   const OPT_CHECKSUM_ALGOS = 4;
   const OPT_GENERATE_STRUCTURE = 5;
   const OPT_XSI_TYPE_DETECT = 6;
   const OPT_GENERATE_CREATED_ATTR = 7;
   
   const DEFAULT_OPTVAL = [
      self::OPT_ROOT_NODE=>'fx:data',
      self::OPT_DEFAULT_NODE=>'fx:data',
      self::OPT_IDENT_SIZE=>3,
      self::OPT_DUMP_OK=>false,
      self::OPT_CHECKSUM_ALGOS=>['md5'],
      self::OPT_GENERATE_STRUCTURE=>false,
      self::OPT_XSI_TYPE_DETECT=>true,
      self::OPT_GENERATE_CREATED_ATTR=>false,
   ];
   
   /**
    * @return string XML serialized data
    */
   public function __toString(): string {
      return $this->encodedValue;
   }
   
   /**
    * @return string XML serialized data
    */
   public function getEncodedValue(): string {
      return $this->encodedValue;
   }
   
   /**
    * @var string
    */
   private $encodedValue;
   
   /**
    * 
    * @param bool|int|float|string|object|array $data data to serialize to XML
    * @param array $options associative array of one or more options:
    * <ul>
    *    <li><b>string</b> <i>\MicroEncode\XmlEncoder::OPT_ROOT_NODE</i>: Specify root node element name; default: 'fx:data'.</li>
    *    <li><b>string</b> <i>\MicroEncode\XmlEncoder::OPT_DEFAULT_NODE</i>: Specify node element name to use when one cannot be determined from the data itself; default 'fx:data'.</li>
    *    <li><b>int</b> <i>\MicroEncode\XmlEncoder::OPT_IDENT_SIZE</i>: Specify indentation size; default: 3.</li>
    *    <li><b>bool</b> <i>\MicroEncode\XmlEncoder::OPT_DUMP_OK</i>: Wheather to generate a special data-dump node for data that cannot be reliably serialized; default: false.</li>
    *    <li><b>string[]</b> <i>\MicroEncode\XmlEncoder::OPT_CHECKSUM_ALGOS</i>: Each array element value specifies a hash algo used to produce a data checksum with; default: ['md5'].</li>
    *    <li><b>bool</b> <i>\MicroEncode\XmlEncoder::OPT_GENERATE_STRUCTURE</i>: Wheather to generate a node describing the data structure; default: false.</li>
    *    <li><b>bool</b> <i>\MicroEncode\XmlEncoder::OPT_XSI_TYPE_DETECT</i>: Wheather to describe data node types using an extended XSI specification; default: true.</li>
    * </ul>
    */
   public function __construct($data, array $options=self::DEFAULT_OPTVAL) {

      foreach (static::DEFAULT_OPTVAL as $opt=>$val) {
         if (!isset($options[$opt])) $options[$opt]=$val;
      }
      unset($opt);
      unset($val);
      
      $meta = "";
      $metatag = "";
      if ($options[static::OPT_GENERATE_STRUCTURE]===true) {
         
         if (static::META_VALUE_GENERIC_OBJECT!==($meta = static::dataToFlatXmlMetaValue($data))) {
            $meta = ' fx:meta="'.$meta.'"';
         } else {
            $meta = " fx:meta=\"extxs:structure\"";
            $structure = new stdClass();
            $dataStructure = json_decode(json_encode(static::createXmlDataStructure($data)));
            $structure->structure = $dataStructure;
            $metatag = static::dataToFlatXml(
               $structure,
               'structure',
               1,
               $options[static::OPT_IDENT_SIZE],
               false,
               [],
               [
                  'namespace'=>'s',
                  'namespaceindentifyer'=>static::FLAT_XMLNS.'-structure'
               ]
            );
         }
            
      }
      $xsins = "";
      if (!empty($options[static::OPT_XSI_TYPE_DETECT]) || !empty($options[static::OPT_XSI_TYPE_DETECT])) $xsins = ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:extxs="'.static::FLAT_XMLNS.'-extxs"';
      $checksum_attr = "";
      $checksum_data = null;
      foreach ($options[static::OPT_CHECKSUM_ALGOS] as $algo) {
         
         if ($checksum_data===null) {
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
      
      $created_attr = "";
      if (!empty($options[static::OPT_GENERATE_CREATED_ATTR])) {
         $created_attr = ' fx:created="'.date("c").'"';
      }
      
      $this->encodedValue = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
      
      $this->encodedValue .= '<' . $options[static::OPT_ROOT_NODE] . " xmlns:fx=\"".static::FLAT_XMLNS."\" xmlns=\"".static::FLAT_XMLNS."-object\"$created_attr$checksum_attr$meta$xsins";
      $ftypeattr = "";
      if (is_null($data)) {
         $ftypeattr .= ' xsi:nil="true"';
      } else {
         if (is_object($data)) {
            $ftypeattr .= static::dataToObjectTypeAttributes($data);
         } elseif (is_array($data)) {
            $ftypeattr .= static::dataToArrayTypeAttributes($data);
         }
      }
      $this->encodedValue .="$ftypeattr>\n";
      
      $this->encodedValue .= static::dataToFlatXml(
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
      $this->encodedValue .= $metatag;
      $this->encodedValue .= '</' . $options[static::OPT_ROOT_NODE] . ">";
      
      return $this->encodedValue;
      
   }
   protected static function indent(int $level=1,int $size=3) : string {
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
   
   protected static function typeToXsiAttribute($type) : string {
      
      return 'xsi:type="'.$type.'"';
      
   }
   
   protected static function dataToXsiType($data,array $option=null) : string {
      
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
                        } 
                        
                        
                        
                        return "extxs:NumericStringFloat";
                     }
                     
                     if ($param['DateTime'] ) {
                        if (false !== ($ts = strtotime($data))) {
                           if (!empty($ts)) {
                              return "xs:DateTime";
                           }
                        }
                     }
                        if ($param['hexBinary'] ) {
                           if (ctype_xdigit($data)) return "xs:hexBinary";
                        }
                        if ($param['anyURI']) {
                           if(filter_var($data, FILTER_VALIDATE_URL)) {
                              return "xs:anyURI";
                           }
                        }
                        
                        if ($param['string']) {
                           if (ctype_print($data)) {
                              return "xs:string";
                           }
                        }
                        
                        return "extxs:Binary";
                  }
      }
   }
   protected static function dataToObjectTypeAttributes($data) : string {
      if (!is_object($data)) return "";
      $type = get_class($data);
      if ($type=='stdClass') {
         $type = "Generic";
      } else {
         $type = "\\$type";
      }
      return " xsi:type=\"extxs:Object\" extxs:ObjectType=\"".preg_replace('/[\x00-\x1F\x80-\xFF]/', '',$type)."\"";
   }
   protected static function dataToArrayTypeAttributes($data) : string {
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
         if (!$first_value && (static::dataToXsiType($v,['string'=>true])!==$last_type)) {
            $all_same_type = false;
            break 1;
         }
         $first_value = false;
         $last_type = static::dataToXsiType($v,['string'=>true]);
         $i++;
      }
      if ($all_same_type) {
         return " xsi:type=\"extxs:Array\" extxs:ArrayType=\"".$last_type."[".count($data)."]\"";
      } else {
         return " xsi:type=\"extxs:Array\" extxs:ArrayType=\"extxs:Mixed[".count($data)."]\"";
      }
   }
   protected static function dataToFlatXml($data, string $default_node, int $indent_level=1, int $indent_size=3, bool $dump_ok=true,array $checksum=self::DEFAULT_OPTVAL[self::OPT_CHECKSUM_ALGOS],array $options=[]) {
      $ns = "";
      $nsidattr="";
      if (!empty($options['namespace'])) {
         if (preg_match('/[a-z_0-9]/i', $options['namespace'])) {
            if (!is_numeric(substr($options['namespace'],0,1))) {
               
               
               
               $ns = $options['namespace'].":";
               
               if (empty($options['namespace_child'])) {
                  if (empty($options['namespaceindentifyer'])) {
                     $nsid = static::FLAT_XMLNS."-".$options['namespace'];
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
         $i=0;
         $xml="";
         foreach ($data as $key=>$value) {
            
            $node = $key;
            
            $nodeNsidattr = $nsidattr;
            
            if (is_array($value)) {
               $nodeNsidattr .= static::dataToArrayTypeAttributes($value);
            } else if (is_object($value)) {
               $nodeNsidattr .= static::dataToObjectTypeAttributes($value);
            }
            
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
            $xml .= static::indent($indent_level,$indent_size)."<$ns$node$nodeNsidattr";
            if ($is_arr){
               if (is_int($key)) {
                  $index = $key;
                  //if ($key!=$i) {
                  $xml .= " extxs:index=\"$key\"";
                  //}
               }
               
            }
            if ($key != $node) {
               $keyval = htmlspecialchars($key, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XML1 | ENT_DISALLOWED,'UTF-8');
               if ($keyval!=$index) {
                  $xml .= " extxs:key=\"$keyval\"";
               }
            }
            
            if (is_array($value) || is_object($value)) {
               
               $indent_level++;
//                if (isset($options[static::OPT_XSI_TYPE_DETECT]) && $options[static::OPT_XSI_TYPE_DETECT]===true) {
//                   if (is_array($value)) {
//                      $xml .= static::dataToArrayTypeAttributes($value);
//                   } else {
//                      //$xml .= " xsi:type=\"extxs:Object\" extxs:ObjectType=\"".get_class($value)."\"";
//                      $xml .= static::dataToObjectTypeAttributes($value);
//                   }
//                }
               
               $xml .= ">\n".
                     static::dataToFlatXml($value, $default_node,$indent_level,$indent_size,$dump_ok,$checksum,$options);
                     $indent_level--;
                     $xml .= static::indent($indent_level,$indent_size)."</$ns$node>\n";
                     
            } else {
               if ($value===null) {
                  if (!empty($options['xsi_type']['nil'])) {
                     $xml .=' xsi:nil="true"'." ";
                  }
                  $xml .= "/>\n";
               } else {
                  if (is_string($value) && ($value==="")) {
                     $xml .= ' xsi:nil="true"';
                     $xml .= "/>\n";
                  } else {
                     $xsi = "";
                     if (!empty($options['xsi_type'])) {
                        if ($xsitype = static::dataToXsiType($value,$options['xsi_type'])) {
                           $xsi=" ".static::typeToXsiAttribute($xsitype);
                        }
                     }
                     
                     $xml .= "$xsi>".static::dataToFlatXml($value, $default_node,$indent_level,$indent_size,$dump_ok,$checksum,$options)."</$ns$node>\n";
                  }
               }
            }
            
            $i++;
         }
         return $xml;
         
      } else {
         //if (empty($data)) return "<!--empty-->";
         
         
         if (""!==($xml = htmlspecialchars($data, ENT_XML1 | ENT_DISALLOWED,'UTF-8'))) {
            
            return trim($xml);
         }
         
         
         $checksum_attr = "";
         foreach ($checksum as $algo) {
            if ($hash = hash($algo,$data)) {
               if (!hexdec($hash)) continue;
               $attr = preg_replace("/[^a-zA-Z0-9]+/", "", $algo);
               if (is_numeric(substr($attr,0,1))) $attr="hash_".$attr;
               $checksum_attr .= " $attr=\"$hash\"";
            }
         }
         
         if (false!==($base64 = base64_encode($data))) {
            $encoding = " extxs:encoding=\"base64\"";
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            if (false!==($mtype = $finfo->buffer($data))) {
               $encoding .= ' extxs:mtype="'.$mtype.'"';
            }
            $indent_level++;
            $xml = "\n".static::indent($indent_level,$indent_size);
            $xml .= "<$default_node extxs:DumpObject=\"xs:base64Binary\"$checksum_attr$encoding>$base64";
            $indent_level--;
            $xml .= "</$default_node>\n".static::indent($indent_level,$indent_size);
            return $xml;
         }
         
         return "<$default_node$checksum_attr><!--unserializable--></$default_node>";return "<$default_node$checksum_attr><!--unserializable--></$default_node>";
      }
   }
   
}
















