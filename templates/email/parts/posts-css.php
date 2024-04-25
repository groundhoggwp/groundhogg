<?php

use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( $email->has_columns() ){
	load_css( 'responsive' );
}
