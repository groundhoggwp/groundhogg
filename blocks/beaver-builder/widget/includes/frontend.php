<div class="gh-form-wrapper-beaver-builder">
    <?php
        $form_id = intval( $settings->groundhogg_form_id );
        if ( $form_id ) {
            echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) );
        }
    ?>
</div>