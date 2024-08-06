<?php

namespace Groundhogg\Utils;

class Replacer {

	protected array $replacements = [];

	protected array $data = [];

	/**
	 * Initialize with some replacements
	 *
	 * @param array $replacements
	 */
	public function __construct( array $replacements = [] ) {
		$this->replacements = $replacements;
	}

	public function __set( $name, $value ) {
		$this->data[$name] = $value;
	}

	public function __get( $name ){
		return $this->data[$name];
	}

	/**
	 * Add a replacement
	 *
	 * @param string $search
	 * @param        $replace
	 *
	 * @return void
	 */
	public function add( string $search, $replace ){
		$this->replacements[$search] = $replace;
	}

	/**
	 * Run the replacements on the content
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function replace( string $content ){

		foreach ( $this->replacements as $search => $replace ){

			if ( is_callable( $replace ) ){
				$replace = call_user_func( $replace, $this );
			}

			$content = str_replace( $search, $replace, $content );
		}

		return $content;
	}

}
