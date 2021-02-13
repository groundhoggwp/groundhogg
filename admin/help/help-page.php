<?php

namespace Groundhogg\Admin\Help;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Plugin;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\get_store_products;
use function Groundhogg\html;
use function Groundhogg\is_pro_features_active;

class Help_Page extends Tabbed_Admin_Page {

	/**
	 * Add Ajax actions...
	 */
	protected function add_ajax_actions() {
		add_action( 'wp_ajax_groundhogg_get_docs', [ $this, 'get_docs_ajax' ] );
	}

	/**
	 * Adds additional actions.
	 *
	 * @return mixed
	 */
	protected function add_additional_actions() {
		if ( ! is_pro_features_active() ) {
			add_action( 'admin_print_styles', function () {
				?>
                <style>
                    .nav-tab-wrapper a[href="?page=gh_help&tab=support"] {
                        color: #DB741A;
                    }

                    .nav-tab-wrapper a[href="?page=gh_help&tab=support"] .dashicons {
                        margin-right: 4px;
                    }
                </style>
				<?php
			} );
		}
	}

	/**
	 * Get the page slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_help';
	}

	/**
	 * Get the menu name
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Help' );
	}

	/**
	 * The required minimum capability required to load the page
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'edit_contacts';
	}

	public function get_priority() {
		return 2;
	}

	/**
	 * Get the item type for this page
	 *
	 * @return mixed
	 */
	public function get_item_type() {
		// TODO: Implement get_item_type() method.
	}

	/**
	 * Enqueue any scripts
	 */
	public function scripts() {
		wp_enqueue_style( 'groundhogg-admin' );
		wp_enqueue_style( 'groundhogg-admin-help' );
	}

	/**
	 * Add any help items
	 *
	 * @return mixed
	 */
	public function help() {
		// TODO: Implement help() method.
	}

	/**
	 * array of [ 'name', 'slug' ]
	 *
	 * @return array[]
	 */
	protected function get_tabs() {
		$tabs = [
			[
				'name' => __( 'Basic Help', 'groundhogg' ),
				'slug' => 'docs'
			],
			[
				'name' => __( 'Support Ticket', 'groundhogg' ),
				'slug' => 'support'
			],
//            [
//                'name' => __('Support Group', 'groundhogg'),
//                'slug' => 'fb'
//            ]
		];

		if ( ! is_pro_features_active() ) {
			$tabs[1]['name'] = dashicon( 'star-filled' ) . $tabs[1]['name'];
		}

		return $tabs;
	}

	public function docs_view() {
		?>
        <div id="poststuff">
            <div id="docs" class="post-box-grid">

                <!-- getting Started -->
                <div class="postbox">
                    <h2><?php _e( 'New to Groundhogg?', 'groundhogg' ); ?></h2>
                    <p class="inner"><?php _e( 'If you are new to Groundhogg, try browsing our getting started articles to learn what you need to know!', 'groundhogg' ); ?></p>
					<?php

					echo html()->e( 'a', [
						'class'  => 'button big-button',
						'target' => '_blank',
						'href'   => 'https://help.groundhogg.io/collection/1-getting-started'
					], __( 'I need help getting started!', 'groundhogg' ) ); ?>


                </div>

                <!-- Building something -->
                <div class="postbox">
                    <h2><?php _e( 'Building something?', 'groundhogg' ); ?></h2>
                    <p class="inner"><?php _e( 'Are you building something custom with Groundhogg? Take a look at our developer oriented articles.', 'groundhogg' ); ?></p>
					<?php

					echo html()->e( 'a', [
						'class'  => 'button big-button',
						'target' => '_blank',
						'href'   => 'https://help.groundhogg.io/collection/141-developers',
					], __( 'I need help with development!', 'groundhogg' ) ); ?>

                </div>

                <!-- FAQ -->
                <div class="postbox">
                    <h2><?php _e( 'Have a question?', 'groundhogg' ); ?></h2>
                    <p class="inner"><?php _e( 'Someone else may have already asked your question. Check out our FAQs to see if there is an answer for you.', 'groundhogg' ); ?></p>
					<?php

					echo html()->e( 'a', [
						'class'  => 'button big-button',
						'target' => '_blank',
						'href'   => 'https://help.groundhogg.io/collection/6-faqs'
					], __( 'I have a question!', 'groundhogg' ) ); ?>
                </div>

                <!-- Extension -->
                <div class="postbox">
                    <h2><?php _e( 'Installing an extension?', 'groundhogg' ); ?></h2>
                    <p class="inner"><?php _e( 'We have detailed setup guides for all of our premium extensions. Find the one you need!', 'groundhogg' ); ?></p>
					<?php

					echo html()->e( 'a', [
						'class'  => 'button big-button',
						'target' => '_blank',
						'href'   => 'https://help.groundhogg.io/collection/24-extensions'
					], __( 'I need help with an extension!', 'groundhogg' ) ); ?>
                </div>

                <!-- Extension -->
                <div class="postbox">
                    <h2><?php _e( "Didn't find what you need?", 'groundhogg' ); ?></h2>
                    <p class="inner"><?php _e( "If you didn't find what you were looking for then you can join our support group and ask the community!", 'groundhogg' ); ?></p>
					<?php

					echo html()->e( 'a', [
						'class'  => 'button big-button',
						'target' => '_blank',
						'href'   => 'https://www.groundhogg.io/fb/'
					], __( 'Join the community!', 'groundhogg' ) ); ?>
                </div>

                <!-- Support ticket -->
                <div class="postbox">
                    <h2><?php _e( "Need technical help?", 'groundhogg' ); ?></h2>
                    <p class="inner"><?php _e( "If you require technical assistance then the best option is to open a support ticket with our advanced support team.", 'groundhogg' ); ?></p>
					<?php

					echo html()->e( 'a', [
						'class' => 'button big-button',
						'href'  => admin_page_url( 'gh_help', [ 'tab' => 'support' ] )
					], __( 'Open a ticket!', 'groundhogg' ) ); ?>
                </div>
            </div>
        </div>
		<?php
	}


	public function support_view() {

		if ( ! is_pro_features_active() ):

			$pricing_url = add_query_arg( [
				'utm_source'   => 'wp-dash',
				'utm_medium'   => 'support',
				'utm_campaign' => 'go-pro',
				'utm_content'  => 'button',
			], 'https://www.groundhogg.io/pricing/' );

			$discount = get_user_meta( wp_get_current_user()->ID, 'gh_free_extension_discount', true );

			if ( $discount ) {
				$pricing_url = add_query_arg( [ 'discount' => $discount ], $pricing_url );
			}

			?>
            <style>
                .support-ad {
                    display: block;
                    max-width: 500px;
                    margin: 60px auto;
                    background: #FFF;
                    padding: 30px;
                    box-sizing: border-box;
                    border: 1px solid #e5e5e5;
                }

                .support-ad h1 {
                    text-align: center;
                    font-size: 32px;
                }

                .support-ad p {
                    font-size: 16px;
                }

            </style>
            <div class="support-ad">

                <h1><b>Need Support?</b></h1>
                <p>Unlock <b>premium technical support</b> when you upgrade to any premium plan.</p>
                <p>There are many benefits to upgrading, like advanced integrations with your favorite tools, more
                    features, and of course premium support.</p>
                <p style="text-align: center">
                    <a id="pricing-button" class="button-primary big-button"
                       href="<?php echo esc_url( $pricing_url ); ?>"
                       target="_blank"><?php dashicon_e( 'star-filled' );
						_e( 'Yes, I Want To Upgrade!' ); ?></a>
                </p>
                <p style="text-align: center">
                    <a href="https://www.groundhogg.io/fb/"
                       target="_blank"><?php _e( 'Ask my question on Facebook.', 'groundhogg' ); ?></a>
                </p>
            </div>
		<?php

		endif;

		do_action( 'groundhogg/support_ticket_form' );
	}

	/**
	 * @var int
	 */
	protected $ticket_id = 0;

	/**
	 * Create a support ticket
	 */
	public function process_support_submit_ticket() {
		add_action( 'groundhogg/create_support_ticket/failed', [ $this, 'listen_for_support_error' ] );

		do_action( 'groundhogg/create_support_ticket' );

		if ( $this->has_errors() ) {
			return $this->get_last_error();
		}

		return false;
	}

	/**
	 * @param $error \WP_Error
	 */
	public function listen_for_support_error( $error ) {
		$this->add_error( $error );
	}

	/**
	 * Output the basic view.
	 *
	 * @return mixed
	 */
	public function view() {
		// TODO: Implement view() method.
	}
}
