<?php

/**
 * @var $contact \Groundhogg\Contact
 */

global $is_IE;

use Groundhogg\Plugin;
use function Groundhogg\action_input;
use function Groundhogg\html;

//$_content_editor_dfw = true;
//$_wp_editor_expand   = true;
//
//$_wp_editor_expand_class = '';
//if ( $_wp_editor_expand ) {
//	$_wp_editor_expand_class = ' wp-editor-expand';
//}

//add_action( 'media_buttons', [
//	Plugin::$instance->replacements,
//	'show_replacements_button'
//] );

//remove_editor_styles();
//
//add_filter( 'mce_css', function ( $mce_css ) {
//	return $mce_css . ', ' . GROUNDHOGG_ASSETS_URL . 'css/admin/email-wysiwyg-style.css';
//} );

wp_enqueue_editor();

?>
    <div id="email-form-wrap">
        <form method="post" id="personal-email-form"><?php

			action_input( 'send_personal_email' );
			wp_nonce_field( 'send_personal_email' );

			?>
            <div class="section"><?php
				echo html()->e( 'label', [ 'for' => 'subject' ], __( 'Subject:', 'groundhogg' ) );
				echo html()->input( [
					'id'          => 'subject',
					'name'        => 'subject',
					'placeholder' => __( 'Following up...', 'groundhogg' )
				] );
				?></div>
            <div class="section"><?php

				echo html()->e( 'label', [ 'for' => 'from' ], __( 'From:', 'groundhogg' ) );
				echo html()->dropdown_owners( [
					'id'       => 'from_user',
					'name'     => 'from_user',
					'selected' => get_current_user_id()
				] );
				?></div><?php

            Plugin::$instance->replacements->show_replacements_dropdown();

			echo html()->wrap( html()->textarea( [
				'name'  => 'email_content',
				'id'    => 'email_content',
				'class' => 'wp-editor-area',
				'cols'  => '',
				'rows'  => ''
			] ), 'div', [ 'class' => 'mce-wrap' ] )

			?>
            <div class="container-info">
                <div class="header-info additional-options">
					<?php _e( 'Additional options', 'groundhogg' ); ?>
                    <i class="dashicons dashicons-arrow-down-alt2"></i>
                </div>
                <div class="content-info">
                    <div class="section"><?php
						echo html()->e( 'label', [ 'for' => 'cc' ], __( 'Cc:', 'groundhogg' ) );
						echo html()->input( [
							'id'          => 'cc',
							'name'        => 'cc',
							'placeholder' => __( '', 'groundhogg' )
						] );
						?></div>
                    <div class="section"><?php
						echo html()->e( 'label', [ 'for' => 'bcc' ], __( 'Bcc:', 'groundhogg' ) );
						echo html()->input( [
							'id'          => 'bcc',
							'name'        => 'bcc',
							'placeholder' => __( '', 'groundhogg' )
						] );
						?></div>
                </div>
            </div>
			<?php

			echo html()->button( [
				'id'   => 'send-email',
				'type' => 'submit',
				'text' => sprintf( __( 'Send to %s', 'groundhogg' ), $contact->get_email() )
			] )

			?></form>
    </div>
<?php

