<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GROUNDHOGG_IS_BROWSER_VIEW', true );

include_once __DIR__ . '/../managed-page.php';

$broadcast = new Broadcast( get_query_var( 'broadcast' ) );
$campaign  = new Campaign( get_query_var( 'campaign' ), 'slug' );

if ( ! $broadcast->exists() ){
    return;
}

$email = $broadcast->get_object();

if ( ! $email->exists() ) {
    return;
}

$contact = get_contactdata();

$email->set_contact( $contact );
//$email->set_event( $event );

echo $email->build();
