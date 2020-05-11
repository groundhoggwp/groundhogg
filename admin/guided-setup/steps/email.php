<?php

namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\SendWp;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\isset_not_empty;

/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */
class Email extends Step {

	public function get_title() {
		return _x( 'Sending Email', 'guided_setup', 'groundhogg' );
	}

	public function get_slug() {
		return 'email_info';
	}

	public function scripts() {
		wp_enqueue_script( 'groundhogg-sendwp' );
	}

	protected function step_nav() {
	}

	public function get_description() {
		return _x( 'There are different ways to send email with Groundhogg! We recommend using a trusted sending service like SendGrid or AWS. Luckily, we provide integration for both!', 'guided_setup', 'groundhogg' );
	}

	public function get_content() {

		$downloads = License_Manager::get_store_products( [
			'tag' => 'sending-service'
		] );

		foreach ( $downloads->products as $download ):
			$extension = (object) $download;

			?>
			<div class="postbox">
				<div class="card-top">
					<h3 class="extension-title">
						<?php esc_html_e( $download->info->title ); ?>
						<img class="thumbnail" src="<?php echo esc_url( $extension->info->thumbnail ); ?>"
						     alt="<?php esc_attr_e( $extension->info->title ); ?>">
					</h3>
					<p class="extension-description">
						<?php esc_html_e( $extension->info->excerpt ); ?>
					</p>
				</div>
				<div class="install-actions">

					<?php

					echo html()->e( 'a', [
						'href' => $extension->info->link,
						'class' => 'button',
						'target' => '_blank'
					], __( 'Integrate this service!' ) );

					?>

					<?php echo html()->e( 'a', [
						'href'   => $extension->info->link,
						'target' => '_blank',
						'class'  => 'more-details',
					], __( 'More details' ) ); ?>
				</div>
			</div>

		<?php
		endforeach;

	}

	/**
	 * Listen for the que to redirect to Groundhogg's Oauth Method.
	 *
	 * @return bool
	 */
	public function save() {
		return true;
	}

}