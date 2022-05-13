<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include GROUNDHOGG_PATH . 'templates/managed-page.php';

use Groundhogg\Form\Form_v2;

$form_id = get_query_var( 'form_id' );

if ( ! $form_id ) {
	$form_id = get_query_var( 'form_uuid' );
}

$form = new Form_v2( [ 'id' => $form_id, 'fill' => true ] );

add_action( 'wp_head', function () {
	wp_dequeue_script( 'fullframe' );
}, 99 );

managed_page_head( wp_strip_all_tags( $form->get_title() ), 'submit-form' );

echo $form->shortcode();

managed_page_footer();
