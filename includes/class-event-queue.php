<?php
/**
 * Event Queue Class
 *
 * This class is for the manipulation and running of the event queue.
 *
 * @package     wp-funnels
 * @subpackage  Includes/Events
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

class WPFN_Event_Queue implements Iterator
{
	/**
	 * @var int $position the current position of the queue
	 */
	private $position = 0;

	/**
	 * @var array $array the list of events
	 */
	private $array;

	public function __construct() {
		$this->position = 0;
	}

	public function rewind() {
		var_dump(__METHOD__);
		$this->position = 0;
	}

	public function current() {
		var_dump(__METHOD__);
		return $this->array[$this->position];
	}

	public function key() {
		var_dump(__METHOD__);
		return $this->position;
	}

	public function next() {
		var_dump(__METHOD__);
		++$this->position;
	}

	public function valid() {
		var_dump(__METHOD__);
		return isset($this->array[$this->position]);
	}


}