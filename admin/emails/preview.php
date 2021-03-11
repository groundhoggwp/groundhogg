<?php

use Groundhogg\Email;
use function Groundhogg\managed_page_url;

defined( 'ABSPATH' ) || exit;

/**
 * Include the eail preview modal
 *
 * @var $email Email
 */

if ( isset( $email ) && is_object( $email ) && $email->exists() ): ?>
	<div id="preview-modal">
		<div class="preview-modal-overlay"></div>
		<div class="preview-modal-content">
			<div class="preview-modal-content-inner">
				<div class="desktop-preview-wrap preview-container"
				     title="<?php esc_attr_e( 'Desktop preview', 'groundhogg' ); ?>">
					<h2 class="subject"><?php esc_html_e( $email->get_subject_line() ); ?></h2>
					<iframe class="desktop-preview"
					        src="<?php echo managed_page_url( 'emails/' . $email->get_id() ); ?>"></iframe>
				</div>
				<div class="mobile-preview-wrap preview-container"
				     title="<?php esc_attr_e( 'Mobile preview', 'groundhogg' ); ?>">
					<h2 class="subject"><?php esc_html_e( $email->get_subject_line() ); ?></h2>
					<iframe class="mobile-preview"
					        src="<?php echo managed_page_url( 'emails/' . $email->get_id() ); ?>"></iframe>
				</div>
				<div class="wp-clearfix"></div>
			</div>
		</div>
		<div class="preview-popup-close-wrap">
			<button id="preview-popup-close" type="button">
				<span class="dashicons dashicons-no"></span>
			</button>
		</div>
	</div>
<?php endif;

wp_enqueue_style( 'groundhogg-admin-email-preview' );
wp_enqueue_script( 'groundhogg-admin-email-preview' );