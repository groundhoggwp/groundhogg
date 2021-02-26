<?php

/**
 * @var $contact \Groundhogg\Contact
 */

global $is_IE;

use Groundhogg\Plugin;
use function Groundhogg\action_input;
use function Groundhogg\html;

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
            <div class="ic-section">
                <div class="ic-section-header additional-options">
					<?php _e( 'Additional options', 'groundhogg' ); ?>
                </div>
                <div class="ic-section-content">
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

