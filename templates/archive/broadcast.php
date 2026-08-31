<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUNDHOGG_IS_BROWSER_VIEW', true );

include_once __DIR__ . '/../managed-page.php';

$broadcast = the_thing( 'broadcast' );
$email     = $broadcast->get_object();

if ( ! $email->exists() ) {
    return;
}

$contact = get_contactdata();

if ( ! $contact ){
	$contact = new Contact();
}

$email->set_contact( $contact );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
echo $email->build();
