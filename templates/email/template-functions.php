<?php

if ( ! defined( 'ABSPATH' ) ) exit;

function the_campaign(){
	return new \Groundhogg\Campaign( get_query_var( 'campaign' ), 'slug' );
}

function the_broadcast(){
	return new \Groundhogg\Broadcast( get_query_var( 'broadcast' ) );
}

/**
 * Loads a part from the parts folder
 *
 * @param string $part
 */
function load_part( $part = '' ) {

	$file = __DIR__ . '/parts/' . $part . '.php';

	if ( file_exists( $file ) ){

		do_action( "groundhogg/templates/email/part/$part" );

		include $file;
	}

}

/**
 * Load a css file from the assets
 *
 * @param string $file
 */
function load_css( $file = '' ) {
	$file = __DIR__ . '/assets/' . $file . '.css';

	if ( file_exists( $file ) ){

		do_action( "groundhogg/templates/email/css/$file" );

		echo file_get_contents( $file );
	}
}
