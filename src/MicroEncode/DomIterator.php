<?php
namespace MicroEncode;
use flat\core\xml\dom\DomNodeIterator;

/**
 * alias for deepcopy utility class
 */
//use \flat\core\util\deepcopy;
/**
 * provides iterative DOM object
 *
 * @package    flat/core/xml
 * @author     D. Bird <retran@gmail.com> Adapated for the flat framework.
 * @author     Peter Cowburn <petercowburn@gmail.com> 2010-10-27 Peter Cowburn, Bloxx Ltd. Edinburgh, Scotland.
 * @copyright  Copyright (c) 2014 Doug Bird. All Rights Reserved.
 * @version    0.1.0-alpha
 * @link https://github.com/salathe/spl-examples/wiki/RecursiveDOMIterator derived from github project as retrieved 2014-12-02
 *
 */
class DomIterator extends \RecursiveIteratorIterator
implements
\RecursiveIterator
{
   
   private static function _only_bool_false(array $arr=null,$key=null,$invalid_return=null) {
      if ($arr && $key) {
         if (isset($arr[$key])) {
            if ($arr[$key]===false) return false;
         }
      }
      return $invalid_return;
   }
   
   private static function _only_bool_true(array $arr=null,$key=null,$invalid_return=null) {
      if ($arr && $key) {
         if (isset($arr[$key])) {
            if ($arr[$key]===true) return true;
         }
      }
      return $invalid_return;
   }
   
   private static function _non_empty_scalar(array $arr=null,$key=null,$return_valid_value=true,$invalid_return=null) {
      if ($arr && $key)
         if (!empty($arr[$key])) {
            if (is_scalar($arr[$key])) {
               if ($return_valid_value) return $arr[$key];
               return true;
            }
         }
      if ($invalid_return) return $invalid_return;
      if ($return_valid_value) return null;
      return false;
   }
   /**
    * creates stdClass from given \MicroEncode\DomIterator object
    *
    * @return \stdClass | array
    *
    * @param array $option assoc array of optional parameters;
    *    $option['node_name_attr'] : property names for object derived from
    *       given attribute value if it exists exists an an XML element;
    *    $option['index_name_attr'] : index for array object used if given
    *       attribute exists an an XML element;
    *    $option['preserve_parent_value'] : if a node with children has any
    *       orphaned value (not contained within a child node), preserve it
    *       within the object;
    *    $option['assoc'] : return as assoc array.
    */
   private static function _dom_to_object(DomIterator $dom,array $option=null) {
      
      $node_name_attr = static::_non_empty_scalar($option,'node_name_attr');
      $index_name_attr = static::_non_empty_scalar($option,'index_name_attr');
      $preserve_parent_value = static::_non_empty_scalar($option,'preserve_parent_value');
      $assoc = static::_only_bool_true($option,'assoc');
      
      $obj = new \stdClass();
      foreach ($dom as $node) {
         if($node->nodeType === \XML_ELEMENT_NODE) {
            $name = $node->nodeName;
            if ($node_name_attr) {
               $nameval = $node->getAttribute($node_name_attr);
               if (!empty($nameval)) $name = $nameval;
            }
            
            $val = null;
            if ($node->firstChild) {
               $val = self::_dom_to_object(new static($node),$option);
               
               if (!empty($preserve_parent_value)) {
                  $parentval = trim($node->nodeValue);
                  if (!empty($parentval)) {
                     $val->$preserve_parent_value = $parentval;
                  }
               }
            } else {
               $val = $node->nodeValue;
            }
            
            if (isset($obj->$name)) {
               if (!is_array($obj->$name)) {
                  $obj->$name = array(
                        deepcopy::data($obj->$name)
                  );
               }
            }
            $idx = null;
            if (!empty($index_name_attr)) {
               $idx = $node->getAttribute($index_name_attr);
               if (!empty($idx)) {
                  if (!isset($obj->$name)) {
                     $obj->$name = array();
                  } else {
                     if (isset($obj->$name[$idx])) $idx = null;
                  }
               } else {
                  $idx = null;
               }
            }
            
            if (isset($obj->$name)) {
               if ($idx!==null) {
                  $obj->{$name}[$idx] = $val;
               } else {
                  $obj->{$name}[] = $val;
               }
            } else {
               $obj->$name = $val;
            }
            
         }
      }
      if ($assoc) return (array) $obj;
      return $obj;
   }
   
   /**
    * creates DOMNode from given XML
    *
    * @return \DOMDocument
    *
    * @param string $xml valid XML as string
    */
   private static function _xml_to_DOMNode($xml,array $option=null) : \DOMDocument {
      $node = new \DOMDocument();
      $node->encoding = 'UTF-8';
      $node->preserveWhiteSpace = false;
      $node->strictErrorChecking = false;
      $node->loadXML($xml);
      return $node;
   }
   /**n
    * converts input to XML. if input specified is not XML string
    *    attempts to see if it's file that contains XML.
    *
    * @return \DOMDocument
    *
    * @param string $input XML string or path to file containing XML
    */
   private static function _input_to_DOMNode($input=null,array $option=null) : \DOMDocument {
      $node=null;
      //$exception = validate\assoc::only_bool_true($option,'exception');
      if (is_a($input,"\\DOMNode")) {
         return $input;
      } else
         if (empty($input)) {
            $node = new \DOMDocument();
            $node->encoding = 'UTF-8';
            $node->preserveWhiteSpace = false;
            return $node;
         } else
            if (is_string($input)) {
               if (substr($input,0,5)=="<?xml") {
                  return self::_xml_to_DOMNode($input,$option);
               } else
                  if (is_file($input) && is_readable($input)) {
                     $node = new \DOMDocument();
                     $node->encoding = 'UTF-8';
                     $node->preserveWhiteSpace = false;
                     $node->strictErrorChecking = false;
                     $node->load($input);
                     return $node;
                  }
            }
         /*
          * see if input loads as HTML
          */
         try {
            if ($node = self::_html_to_DOMNode($input)) {
               return $node;
            }
         } catch (\Exception $e) {
            //ok...
         }
         new \DOMDocument();
         
   }
   /**
    * converts an XML string into stdClass
    *
    * @return \stdClass | array
    *
    * @param string $xml valid XML as string
    * @param array $option assoc array of optional parameters;
    *    $option['node_name_attr'] : property names for object derived from
    *       given attribute value if it exists exists an an XML element;
    *    $option['index_name_attr'] : index for array object used if given
    *       attribute exists an an XML element;
    *    $option['preserve_parent_value'] : if a node with children has any
    *       orphaned value (not contained within a child node), preserve it
    *       within the object;
    *    $option['assoc'] : return as assoc array.
    *
    */
   public static function xml_to_object($xml,array $option=null) {
      
      return self::_dom_to_object(
            self::load_xml($xml),
            $option
            );
   }
   
   
   private static function _html_to_DOMNode($html) {
      $use_internal = libxml_use_internal_errors (true);
      try {
         
         /*
          * force UTF-8
          */
         $node = new \DOMDocument();
         $node->preserveWhiteSpace = false;
         $node->loadHTML('<?xml encoding="UTF-8">' . $html);
         
         // dirty fix
         foreach ($node->childNodes as $item)
            if ($item->nodeType == \XML_PI_NODE)
               $node->removeChild($item); // remove hack
               $node->encoding = 'UTF-8';
               $node->preserveWhiteSpace = false;
               libxml_clear_errors();
               libxml_use_internal_errors ($use_internal);
               return $node;
      } catch (\Exception $e) {
         return new \DOMDocument();
      }
   }
   
   
   /**
    * creates \MicroEncode\DomIterator object from given HTML
    *
    * @return \MicroEncode\DomIterator
    *
    * @param string $html HTML as string
    *
    */
   public static function load_html($html) {
      if ($node = self::_html_to_DOMNode($html)) {
         return new static($node);
      }
   }
   
   /**
    * creates \MicroEncode\DomIterator object from given XML
    *
    * @return \MicroEncode\DomIterator
    *
    * @param string $xml XML as string
    * @param array $option
    */
   public static function load_xml($xml,array $option=null) {
      if ($node = self::_xml_to_DOMNode($xml,$option)) {
         return new static($node);
      }
   }
   
   /**
    * creates \MicroEncode\DomIterator object from given input
    *
    * @param string $input XML string or path to file containing XML
    */
   public static function load($input=null) : DomIterator {
      return new static($input);
   }
   
   private $_DOMDocument;
   private $_DOMNode;
   private $_xpath;
   
   /**
    * converts current DOMDocument into a stdClass
    *
    * @return \stdClass|null
    */
   public function to_object(array $option=null) {
      return self::_dom_to_object($this,$option);
   }
   
   /**
    * provides an HTML document from current DOMDocument
    *
    * @return string
    *
    */
   public function html(array $flags=null) {
      $orig = true;
      if (in_array('no_attributes',$flags)) {
         /**
          The RegExp broken down:
          
          /              # Start Pattern
          <             # Match '<' at beginning of tags
          (             # Start Capture Group $1 - Tag Name
          [a-z]         # Match 'a' through 'z'
          [a-z0-9]*     # Match 'a' through 'z' or '0' through '9' zero or more times
          )             # End Capture Group
          [^>]*?        # Match anything other than '>', Zero or More times, not-greedy (wont eat the /)
          (\/?)         # Capture Group $2 - '/' if it is there
          >             # Match '>'
          /i            # End Pattern - Case Insensitive
          *
          * @link http://stackoverflow.com/questions/3026096/remove-all-attributes-from-an-html-tag
          */
         return preg_replace("/<([a-z][a-z0-9]*)[^>]*?(\/?)>/i",'<$1$2>', $this->get_DOMDocument()->saveHTML());
      }
      return $this->get_DOMDocument()->saveHTML();
   }
   
   /**
    * provides an XML document from current DOMDocument
    *
    * @return string
    */
   public function xml() {
      return $this->get_DOMDocument()->saveXML();
   }
   
   /**
    * retrieves current DOMNode, if none exists returns null.
    *
    * @return \DOMNode|null
    */
   public function get_DOMNode() {
      return $this->_DOMNode;
   }
   /**
    * retrieves current DOMDocument, if none exists returns null.
    *
    * @return \DOMDocument|null
    */
   public function get_DOMDocument() {
      
      if (!empty($this->_DOMDocument)) return $this->_DOMDocument;
      
      /*
       * copy DOMNode constructor argument if it's also a DOMDocument
       */
      if (is_a($this->_DOMNode,"\\DOMDocument")) {
         $this->_DOMDocument = $this->_DOMNode;
         return $this->_DOMDocument;
      }
      
      /*
       * create DOMDocument from DOMNode
       */
      if (is_a($this->_DOMNode,"\\DOMNode")) {
         $doc = new \DOMDocument;
         $doc->preserveWhiteSpace = false;
         $doc->appendChild(
               $doc->importNode($this->_DOMNode, true)
               );
         $this->_DOMDocument = $doc;
         return $this->_DOMDocument;
      }
   }
   
   /**
    * performs xpath query on current DOMDocument, if none exists returns null.
    *
    * @param string $query XPath query
    * @return \MicroEncode\DomNodeIterator|null
    */
   public function xpath($query) {
      if (!is_a($this->_xpath,"\\DOMXPath")) {
         if ($doc = $this->get_DOMDocument()) {
            $this->_xpath = new \DOMXPath($doc);
         } else {
            return;
         }
      }
      return new DomNodeIterator($this->_xpath->query($query));
   }
   
   /**
    *
    * @param string $input XML string, or path to XML file
    *
    */
   public function __construct($input) {
      $this->_DOMNode = self::_input_to_DOMNode($input);
      $this->_position = 0;
      $this->_nodeList = $this->_DOMNode->childNodes;
      parent::__construct(new \RecursiveArrayIterator($this),\RecursiveIteratorIterator::SELF_FIRST);
   }
   
   /**
    * 
    * @var int Current Position in DOMNodeList
    */
   protected $_position;
   
   
   
   /**
    * The DOMNodeList with all children to iterate over
    * @var \DOMNodeList
    */
   protected $_nodeList;
   /**
    * Returns the current DOMNode
    * @return \DOMNode
    */
   public function current()
   {
      return $this->_nodeList->item($this->_position);
   }
   
   /**
    * Returns an iterator for the current iterator entry
    * @return \MicroEncode\DOMIterator
    */
   public function getChildren()
   {
      return new self($this->current());
   }
   
   /**
    * Returns if an iterator can be created for the current entry.
    * @return bool
    */
   public function hasChildren()
   {
      return $this->current()->hasChildNodes();
   }
   
   /**
    * returns node name if not empty otherwise returns position
    * @return string|int
    */
   public function key()
   {
      $node = $this->current();
      if($node->nodeType === \XML_ELEMENT_NODE) {
         if (!empty($node->nodeName)) {
            return $node->nodeName;
         }
      }
      return $this->_position;
   }
   
   /**
    * Moves the current position to the next element.
    * @return void
    */
   public function next()
   {
      $this->_position++;
   }
   
   /**
    * Rewind the Iterator to the first element
    * @return void
    */
   public function rewind()
   {
      $this->_position = 0;
   }
   
   /**
    * Checks if current position is valid
    * @return bool
    */
   public function valid()
   {
      return $this->_position < $this->_nodeList->length;
   }
   
}