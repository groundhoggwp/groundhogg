<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * Props to https://github.com/salathe/spl-examples/wiki/RecursiveDOMIterator for this and making a vital part of this
 * software work.
 */
class RecursiveDOMIterator implements RecursiveIterator {
	/**
	 * Current Position in DOMNodeList
	 * @var Integer
	 */
	protected $_position;

	/**
	 * The DOMNodeList with all children to iterate over
	 * @var DOMNodeList
	 */
	protected $_nodeList;

	/**
	 * @param DOMNode $domNode
	 *
	 * @return void
	 */
	public function __construct( DOMNode $domNode ) {
		$this->_position = 0;
		$this->_nodeList = $domNode->childNodes;
	}

	/**
	 * Returns the current DOMNode
	 * @return DOMNode
	 */
	public function current() {
		return $this->_nodeList->item( $this->_position );
	}

	/**
	 * Returns an iterator for the current iterator entry
	 * @return RecursiveDOMIterator
	 */
	public function getChildren() {
		return new self( $this->current() );
	}

	/**
	 * Returns if an iterator can be created for the current entry.
	 * @return Boolean
	 */
	public function hasChildren() {
		return $this->current()->hasChildNodes();
	}

	/**
	 * Returns the current position
	 * @return Integer
	 */
	public function key() {
		return $this->_position;
	}

	/**
	 * Moves the current position to the next element.
	 * @return void
	 */
	public function next() {
		$this->_position ++;
	}

	/**
	 * Rewind the Iterator to the first element
	 * @return void
	 */
	public function rewind() {
		$this->_position = 0;
	}

	/**
	 * Checks if current position is valid
	 * @return Boolean
	 */
	public function valid() {
		return $this->_position < $this->_nodeList->length;
	}
}