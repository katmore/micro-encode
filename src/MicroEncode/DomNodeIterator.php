<?php
namespace flat\core\xml\dom;
/*
 * refactored from 
 *    https://github.com/salathe/spl-examples/wiki/RecursiveDOMIterator derived from github project as retrieved 2014-12-02
 */
class DomNodeIterator extends \RecursiveIteratorIterator implements \RecursiveIterator {
   
   public function __construct (\DOMNodeList $nodeList) {
      
      $this->_position = 0;
      $this->_nodeList = $nodeList;
      
      self::__construct($this,\RecursiveIteratorIterator::SELF_FIRST);
      
   }
   
   
   public function get($item) {
      if (is_int($item)) {
         return $this->_nodeList->item($item);
      }
   }
   /**
    * Current Position in DOMNodeList
    * @var Integer
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
    * @return \MicroEncode\DomNodeIterator
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
    * provides node name if not empty, otherwise, returns position
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