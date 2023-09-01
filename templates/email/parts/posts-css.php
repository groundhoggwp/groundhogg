<?php

use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( $email->has_columns() ){
	echo file_get_contents( __DIR__ . '/../assets/responsive.css' );
}