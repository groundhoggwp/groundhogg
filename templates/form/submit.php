<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

include GROUNDHOGG_PATH . 'templates/managed-page.php';

use Groundhogg\Form\Form;


$form_id = get_query_var( 'form_id' );
$form = new Form( [ 'id' => $form_id ] );
$step = new Step( $form_id );

add_action( 'wp_head', function (){
	wp_dequeue_script('fullframe');
}, 99 ) ;

add_action( 'wp_head', function(){
    ?>
    <style>
        #main {max-width: 650px;}
    </style>
    <?php
} );

managed_page_head( $step->get_title(), 'view' );

?>
    <div class="box">
        <?php

        form_errors( false );

        ?>
        <?php echo $form->get_iframe_embed_code(); ?>
    </div>
    <?php

managed_page_footer();