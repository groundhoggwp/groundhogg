<?php

use function Groundhogg\the_email;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$email = the_email();

if ( $email->has_posts() ){
	echo file_get_contents( __DIR__ . '/../assets/posts.css' );
}