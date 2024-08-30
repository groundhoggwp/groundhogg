<?php

namespace Groundhogg\Background;

abstract class Task implements \JsonSerializable {

	public function __construct(){}

	abstract public function can_run();

	/**
	 * Process the task
	 *
	 * @return bool true for complete, false otherwise
	 */
	abstract public function process();

	public function stop(){}

	public function __serialize(): array {
		return get_object_vars( $this );
	}

	public function __unserialize( array $data ): void {
		foreach ( $data as $prop => $value ){
			if ( property_exists( $this, $prop ) ){
				$this->$prop = $value;
			}
		}
	}

	public function jsonSerialize(): array {
		return array_merge( [
			'task' => str_replace( '_', ' ', strtolower( substr( get_class( $this ), strrpos( get_class( $this ), '\\' ) + 1 ) ) )
		], $this->__serialize() );
	}
}
