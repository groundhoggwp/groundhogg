<?php

namespace Groundhogg;

class CSV_Handler {

	public function __construct( $args = [] ) {

		[
			'offset'       => $offset,
			'limit'        => $limit,
			'row_callback' => $row_callback,
		] = $args;

	}



}
