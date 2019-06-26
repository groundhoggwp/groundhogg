<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) exit;

include GROUNDHOGG_PATH . 'templates/managed-page.php';

use Groundhogg\Form\Form;


$form_id = get_query_var( 'form_id' );
$form = new Form( [ 'id' => $form_id ] );
$step = new Step( $form_id );

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

        if ( Plugin::$instance->submission_handler->has_errors() ){

            $errors = Plugin::$instance->submission_handler->get_errors();
            $err_html = "";

            foreach ( $errors as $error ){
                $err_html .= sprintf( '<li id="%s">%s</li>', $error->get_error_code(), $error->get_error_message() );
            }

            $err_html = sprintf( "<ul class='gh-form-errors'>%s</ul>", $err_html );
            echo sprintf( "<div class='gh-form-errors-wrapper'>%s</div>", $err_html );

        }

        ?>
        <?php echo $form->get_iframe_embed_code(); ?>
    </div>
    <?php

managed_page_footer();