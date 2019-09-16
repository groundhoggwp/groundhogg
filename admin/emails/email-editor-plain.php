<?php

namespace Groundhogg\Admin\Emails;

use Groundhogg\Email_Parser;
use function Groundhogg\get_request_var;
use function Groundhogg\groundhogg_url;
use function Groundhogg\html;
use Groundhogg\Plugin;
use Groundhogg\Email;
use function Groundhogg\managed_page_url;

/**
 * Email Editor
 *
 * Allow the user to edit the email
 * rather than just hardcoded.
 *
 * @package     Admin
 * @subpackage  Admin/Emails
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$email_id = absint( get_request_var( 'email' ) );
$email    = new Email( $email_id );

?>
<form method="post" id="email-form">

    <!-- Before-->
	<?php wp_nonce_field( 'edit_plain' ); ?>
	<?php

	$test_email = get_user_meta( get_current_user_id(), 'preferred_test_email', true );
	$test_email = $test_email ? $test_email : wp_get_current_user()->user_email;

    echo Plugin::$instance->utils->html->input( [ 'type' => 'hidden', 'id' => 'test-email', 'value' => $test_email ] ); ?>

    <div id='poststuff'>

        <div id="post-body" class="metabox-holder columns-2" style="clear: both">

            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="save" class="postbox">
                    <span class="spinner"></span>
                    <h2><?php _e( 'Save & Preview', 'groundhogg' ); ?></h2>
                    <div class="inside">
	                    <?php submit_button( __( 'Update', 'groundhogg' ), 'primary', 'update', false ); ?>
	                    <?php submit_button( __( 'Update & Test', 'groundhogg' ), 'secondary', 'update_and_test', false ); ?>
	                    <?php echo html()->modal_link( [
		                    'title'     => __( 'Mobile Preview' ),
		                    'text'      => '<span class="dashicons dashicons-smartphone"></span>',
		                    'footer_button_text' => __( 'Close' ),
		                    'id'        => '',
		                    'class'     => 'button button-secondary dash-button',
		                    'source'    => managed_page_url( 'emails/' . $email->get_id() ),
		                    'height'    => 580,
		                    'width'     => 340,
		                    'footer'    => 'true',
		                    'preventSave'    => 'true',
	                    ] ); ?>
	                    <?php echo html()->modal_link( [
		                    'title'     => __( 'Desktop Preview' ),
		                    'text'      => '<span class="dashicons dashicons-desktop"></span>',
		                    'footer_button_text' => __( 'Close' ),
		                    'id'        => '',
		                    'class'     => 'button button-secondary dash-button',
		                    'source'    => managed_page_url( 'emails/' . $email->get_id() ),
		                    'height'    => 600,
		                    'width'     => 700,
		                    'footer'    => 'true',
		                    'preventSave'    => 'true',
	                    ] ); ?>
                    </div>
                </div>

                <h3><?php _e( 'Status:', 'groundhogg' ); ?></h3>
                <p>
	                <?php echo Plugin::$instance->utils->html->toggle( [
		                'name'          => 'email_status',
		                'id'            => 'status-toggle',
		                'value'         => 'ready',
		                'checked'       => $email->get_status() === 'ready',
		                'on'            => 'Ready',
		                'off'           => 'Draft',
	                ]); ?>
                </p>
                <h3><?php _e( 'From:', 'groundhogg' ); ?></h3>
				<?php $args = array(
					'option_none' => __( 'The Contact\'s Owner' ),
					'id'          => 'from_user',
					'name'        => 'from_user',
					'selected'    => $email->from_user,
					'style'       => [ 'max-width' => '100%' ]
				); ?>
                <p><?php echo Plugin::$instance->utils->html->dropdown_owners( $args ); ?></p>
	            <?php echo html()->description( __( 'Choose who this email comes from.' ) ); ?>

                <h3><?php _e( 'Reply To:', 'groundhogg' ); ?></h3>
				<?php $args = [
					'type'  => 'email',
					'name'  => 'reply_to_override',
					'id'    => 'reply_to_override',
					'value' => $email->get_meta( 'reply_to_override' ),
					'style' => [ 'max-width' => '100%' ]
				]; ?>
                <p><?php echo Plugin::$instance->utils->html->input( $args ); ?></p>
				<?php echo html()->description( __( 'Override the email address replies are sent to. Leave empty to default to the sender address.' ) ); ?>

                <h3><?php _e( 'Alignment' ); ?></h3>
                <p>
                    <select id="email-align" name="email_alignment">
                        <option value="left" <?php if ( $email->get_meta( 'alignment' ) === 'left' ) {
							echo 'selected';
						} ?> ><?php _e( 'Left' ); ?></option>
                        <option value="center" <?php if ( $email->get_meta( 'alignment' ) === 'center' ) {
							echo 'selected';
						} ?>><?php _e( 'Center' ); ?></option>
                    </select>
                </p>
                <p>
		            <?php echo Plugin::$instance->utils->html->checkbox( [
			            'label'   => __('Enable browser view', 'groundhogg' ),
			            'name'    => 'browser_view',
			            'id'      => 'browser_view',
			            'class'   => '',
			            'value'   => '1',
			            'checked' => $email->browser_view_enabled( false ),
		            ] ); ?>
                </p>
                <p>
		            <?php echo Plugin::$instance->utils->html->checkbox( [
			            'label'   => __( 'Save as template', 'groundhogg' ),
			            'name'    => 'save_as_template',
			            'id'      => 'save_as_template',
			            'class'   => '',
			            'value'   => '1',
			            'checked' => $email->is_template(),
		            ] ); ?>
                </p>
            </div>
            <div id="post-body-content">

                <div id="title-wrap">
                    <!-- Title -->
                    <input placeholder="<?php echo __( 'Admin Title', 'groundhogg' ); ?>" type="text" name="email_title"
                           size="30" value="<?php echo esc_attr( $email->get_title() ); ?>" id="title" spellcheck="true"
                           autocomplete="off" required>
                </div>

                <div id="subject-wrap">
                    <h3><?php _e( 'Subject & Pre-Header:', 'groundhogg' ); ?></h3>
                    <!-- Subject Line -->
                    <input placeholder="<?php echo __( 'Subject Line: Used to capture the attention of the reader.', 'groundhogg' ); ?>"
                           type="text" name="subject" size="30"
                           value="<?php echo esc_attr( $email->get_subject_line() ); ?>" id="subject" spellcheck="true"
                           autocomplete="off" required>

                    <!-- Pre Header-->
                    <input placeholder="<?php echo __( 'Pre Header Text: Used to summarize the content of the email.', 'groundhogg' ); ?>"
                           type="text" name="pre_header" size="30"
                           value="<?php echo esc_attr( $email->get_pre_header() ); ?>" id="pre_header" spellcheck="true"
                           autocomplete="off">
                </div>

                <div id="content-wrap">
					<?php echo html()->editor( [
						'id'                  => 'email_content',
						'content'             => $email->get_content(),
						'settings'            => [
						        'editor_height' => 500,
                        ],
						'replacements_button' => true,
					] ); ?>
                </div>
            </div>
        </div>
    </div>
</form>
