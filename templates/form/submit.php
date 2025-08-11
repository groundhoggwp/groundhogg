<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include GROUNDHOGG_PATH . 'templates/managed-page.php';

use Groundhogg\Form\Form;
use Groundhogg\Form\Form_v2;


$slug = get_query_var( 'slug' );
$step = new Step( $slug );

if ( ! $step->exists() || ! ( $step->type_is( 'form_fill' ) || $step->type_is( 'web_form' ) ) ) {
	wp_die( 'Form does not exist.' );
}

if ( $step->type_is( 'form_fill' ) ) {
	$form = new Form( [ 'id' => $step->get_id() ] );
} else if ( $step->type_is( 'web_form' ) ) {
	$form = new Form_v2( [ 'id' => $step->get_id() ] );
} else {
	wp_die( 'Invalid form type.' );
}


add_action( 'wp_head', function () {
	wp_dequeue_script( 'fullframe' );
}, 99 );

add_action( 'wp_head', function () {
	?>
    <style>
        #main {
            max-width: 650px;
        }
    </style>
	<?php
} );

managed_page_head( $step->get_title(), 'view' );

?>
    <div class="box">
		<?php

		form_errors( false );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Generated HTML
		echo $form->get_iframe_embed_code(); ?>
    </div>
	<?php

add_filter( 'groundhogg/managed_page/footer_links', function ( $links ) use ( $step ) {

	if ( ! current_user_can( 'edit_funnels' ) ) {
		return $links;
	}

	$links[] = html()->e( 'a', [
		'href' => admin_page_url( 'gh_funnels', [
			'funnel' => $step->get_funnel_id(),
			'action' => 'edit'
		], $step->get_id() )
	], __( 'Edit Form', 'groundhogg' ) );

	return $links;
} );

managed_page_footer();
