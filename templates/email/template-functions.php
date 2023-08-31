<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Loads a part from the parts folder
 *
 * @param string $part
 */
function load_part( $part = '' ) {

	$file = __DIR__ . '/parts/' . $part . '.php';

	if ( file_exists( $file ) ){
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
		echo file_get_contents( $file );
	}
}