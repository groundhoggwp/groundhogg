<?php

namespace Groundhogg\Admin\Emails;

use function Groundhogg\html;
use Groundhogg\Plugin;

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

global $is_IE;

$_content_editor_dfw = true;
$_wp_editor_expand   = true;

$_wp_editor_expand_class = '';
if ( $_wp_editor_expand ) {
	$_wp_editor_expand_class = ' wp-editor-expand';
}

wp_enqueue_script( 'groundhogg-admin-email-editor-expand' );

function get_dummy_text() {
	return "Hi {first}!
    
    This is where you write the content of your email. Your email can include as much content as you want.
    
    It can be short, long, or anywhere in between.
    
    Use {replacement codes} to personalize your emails. You can see a list of the available codes by clicking the little person next to the media button above the editor.
    
    That's pretty much everything you need to know. Good Luck!
    
    Sincerely,
    
    The Groundhogg Team";
}

?>
<form method="post" id="email-form">
    <!-- Before-->
	<?php wp_nonce_field( 'add' );

	$test_email = get_user_meta( get_current_user_id(), 'preferred_test_email', true );
	$test_email = $test_email ? $test_email : wp_get_current_user()->user_email;

	echo Plugin::$instance->utils->html->input( [
		'type'  => 'hidden',
		'id'    => 'test-email',
		'value' => $test_email
	] ); ?>
    <div id='poststuff'>
        <div id="post-body" class="metabox-holder columns-2  align-email-center" style="clear: both">
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="save" class="postbox">
                    <span class="spinner"></span>
                    <h2><?php _e( 'Save', 'groundhogg' ); ?></h2>
                    <div class="inside">
						<?php submit_button( __( 'Create', 'groundhogg' ), 'primary', 'update', false ); ?>
                    </div>
                </div>

                <h3><?php _e( 'Status', 'groundhogg' ); ?></h3>
                <p>
					<?php echo Plugin::$instance->utils->html->toggle( [
						'name'    => 'email_status',
						'id'      => 'status-toggle',
						'value'   => 'ready',
						'checked' => false,
						'on'      => 'Ready',
						'off'     => 'Draft',
					] ); ?>
                </p>
                <h3><?php _e( 'From', 'groundhogg' ); ?></h3>
				<?php $args = array(
					'option_none' => __( 'The Contact\'s Owner' ),
					'id'          => 'from_user',
					'name'        => 'from_user',
					'selected'    => get_current_user_id(),
					'style'       => [ 'max-width' => '100%' ]
				); ?>
                <p><?php echo Plugin::$instance->utils->html->dropdown_owners( $args ); ?></p>
				<?php echo html()->description( __( 'Choose who this email comes from.' ) ); ?>

                <h3><?php _e( 'Reply To', 'groundhogg' ); ?></h3>
				<?php $args = [
					'type'  => 'email',
					'name'  => 'reply_to_override',
					'id'    => 'reply_to_override',
					'style' => [ 'max-width' => '100%' ]
				]; ?>
                <p><?php echo Plugin::$instance->utils->html->input( $args ); ?></p>
				<?php echo html()->description( __( 'Override the email address replies are sent to. Leave empty to default to the sender address.' ) ); ?>

                <h3><?php _e( 'Alignment' ); ?></h3>
                <p>
                    <select id="email-align" name="email_alignment">
                        <option value="left"><?php _e( 'Left' ); ?></option>
                        <option value="center"><?php _e( 'Center' ); ?></option>
                    </select>
                </p>
                <h3><?php _e( 'Message Type' ); ?></h3>
				<?php $args = [
					'type'              => 'email',
					'name'              => 'message_type',
					'id'                => 'message-type',
					'options'           => [
						'marketing'     => __( 'Marketing', 'groundhogg' ),
						'transactional' => __( 'Transactional', 'groundhogg' )
					],
					'selected'          => 'marketing',
					'required'          => true,
					'option_none'       => '',
					'option_none_value' => '',
				]; ?>
                <p><?php echo Plugin::$instance->utils->html->dropdown( $args ); ?></p>
                <h3><?php _e( 'Additional' ); ?></h3>
                <p>
					<?php echo Plugin::$instance->utils->html->checkbox( [
						'label' => __( 'Enable browser view', 'groundhogg' ),
						'name'  => 'browser_view',
						'id'    => 'browser_view',
						'class' => '',
						'value' => '1',
					] ); ?>
                </p>
                <p>
					<?php echo Plugin::$instance->utils->html->checkbox( [
						'label' => __( 'Save as template', 'groundhogg' ),
						'name'  => 'save_as_template',
						'id'    => 'save_as_template',
						'class' => '',
						'value' => '1',
					] ); ?>
                </p>
                <p>
					<?php echo Plugin::$instance->utils->html->checkbox( [
						'label' => __( 'Use custom Alt-Body', 'groundhogg' ),
						'name'  => 'use_custom_alt_body',
						'id'    => 'use_custom_alt_body',
						'class' => '',
						'value' => '1',
					] ); ?>
                </p>
            </div>
            <div id="post-body-content">

                <div id="title-wrap">
                    <!-- Title -->
                    <input placeholder="<?php echo __( 'Admin title', 'groundhogg' ); ?>" type="text" name="title"
                           size="30" value="" id="title" spellcheck="true"
                           autocomplete="off" required>
                </div>

                <div id="subject-wrap">
                    <h3><?php _e( 'Subject & Pre-Header', 'groundhogg' ); ?></h3>
                    <!-- Subject Line -->
                    <input placeholder="<?php echo __( 'Subject line: Used to capture the attention of the reader.', 'groundhogg' ); ?>"
                           type="text" name="subject" size="30"
                           value="" id="subject" spellcheck="true"
                           autocomplete="off" required>

                    <!-- Pre Header-->
                    <input placeholder="<?php echo __( 'Pre-header text: Used to summarize the content of the email.', 'groundhogg' ); ?>"
                           type="text" name="pre_header" size="30"
                           value="" id="pre_header" spellcheck="true"
                           autocomplete="off">
                </div>
                <div id="content-wrap">
                    <div id="postdivrich" class="postarea<?php echo $_wp_editor_expand_class; ?>">
						<?php

						add_action( 'media_buttons', [
							\Groundhogg\Plugin::$instance->replacements,
							'show_replacements_dropdown'
						] );

						wp_editor( wpautop( get_dummy_text() ), 'email_content', [
							'_content_editor_dfw' => $_content_editor_dfw,
							'drag_drop_upload'    => true,
							'tabfocus_elements'   => 'content-html,save-post',
							'editor_height'       => 500,
							'tinymce'             => array(
								'resize'                  => false,
								'wp_autoresize_on'        => $_wp_editor_expand,
								'add_unload_trigger'      => false,
								'wp_keep_scroll_position' => ! $is_IE,
							),
						] );


						?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
