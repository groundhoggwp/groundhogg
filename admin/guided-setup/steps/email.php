<?php

namespace Groundhogg\Admin\Guided_Setup\Steps;

use Groundhogg\Mailhawk;
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
	    
	    ?>
		<style type="text/css">
			#groundhogg-mailhawk-connect {
				display: block;
				margin: 20px auto 20px auto;
				padding: 8px 14px;
			}
			#connect-mailhawk h3{
				text-align: center;
			}
			#connect-mailhawk p{
				font-size: 14px;
			}
			#connect-mailhawk{
				margin: 60px auto;
			}
		</style>

		<div id="connect-mailhawk">
			<h3 id="connect-mailhawk-h3"><?php _e( 'Never worry about email deliverability again!' ); ?></h3>
			<?php

			Mailhawk::instance()->output_connect_button();
			Mailhawk::instance()->output_js();
			?>
			<p id="connect-mailhawk"><?php _e( '<a href="https://mailhawk.com/" target="_blank">MailHawl</a> makes WordPress email delivery as simple as a few clicks, starting at <b>$14.97/month</b>.', 'groundhogg' ); ?></p>
		</div>
		<?php

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