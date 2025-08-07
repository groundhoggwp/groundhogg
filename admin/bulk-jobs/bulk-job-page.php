<?php

namespace Groundhogg\Admin\Bulk_Jobs;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;
use function Groundhogg\get_post_var;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\use_experimental_features;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Bulk_Job_Page extends Admin_Page {

	/* Unused Functions */
	public function get_priority() {
		return 99;
	}

	public function scripts() {
	}

	protected function add_additional_actions() {
	}

	public function help() {
	}

	/**
	 * Listen for the bulk actions..
	 *
	 * @return void
	 */
	public function add_ajax_actions() {
		add_action( 'wp_ajax_bulk_action_listener', [ $this, 'ajax_listener' ] );
	}

	/**
	 * Listen for the bulk action and then perform it.
	 */
	public function ajax_listener() {
		if ( ! current_user_can( 'perform_bulk_actions' ) ) {
			$this->wp_die_no_access();
		}

		// Sanitize the bulk action
		// Permitted Characters 0-9, A-z, _, -, / to keep inline with the Groundhogg Action Structure. No spaces.
		$bulk_action = preg_replace( '/[^0-9A-z_\-\/]/', '', get_request_var( 'bulk_action' ) );

		$nonce = get_post_var( 'bulk_action_nonce' );

		if ( ! wp_verify_nonce( $nonce, $bulk_action ) ) {
			wp_send_json_error( [ 'Invalid nonce.', $nonce, $bulk_action, $_POST ] );
		}

		//Double check and that everything is okay.
		$action = sanitize_text_field( "groundhogg/bulk_job/{$bulk_action}/ajax" );

		do_action( $action );
	}

	protected function get_parent_slug() {
		return 'gh_tools';
	}

	/**
	 * Get the slug
	 *
	 * @return string
	 */
	public function get_slug() {
		return 'gh_bulk_jobs';
	}

	/**
	 * default screen title
	 *
	 * @return string
	 */
	public function get_name() {
		return __( 'Processing...', 'groundhogg' );
	}

	public function admin_title( $admin_title, $title ) {
		return sprintf( "%s %s", $this->get_title(), $admin_title );
	}

	/**
	 * Minimum access cap
	 *
	 * @return string
	 */
	public function get_cap() {
		return 'perform_bulk_actions';
	}

	/**
	 * @return mixed|string
	 */
	public function get_item_type() {
		return 'bulk_job';
	}

	protected function get_title_actions() {
		return [];
	}

	/**
	 * Display the title and dependent action include the appropriate page content
	 */
	public function page() {

		$this->add_notice( 'do_not_leave', __( 'Do not leave the page till the process is complete!', 'groundhogg' ), 'warning' );

		?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
			<?php $this->do_title_actions(); ?>
            <div id="notices">
				<?php Plugin::instance()->notices->notices(); ?>
            </div>
            <hr class="wp-header-end">
			<?php

			$this->view();

			?>
        </div>
		<?php
	}

	public function process_action() {
		return;
	}

	public function view() {

		if ( ! $this->verify_action() || ! current_user_can( 'perform_bulk_actions' ) ) {
			$this->wp_die_no_access();
		}

		$items     = apply_filters( "groundhogg/bulk_job/{$this->get_current_action()}/query", [] );
		$max_items = apply_filters( "groundhogg/bulk_job/{$this->get_current_action()}/max_items", 25, $items );

		echo Plugin::$instance->utils->html->progress_bar( [ 'id' => 'bulk-job', 'hidden' => false ] );

		$bp_args = [
			'num_retries'   => 3,
			'error_message' => sprintf( __( 'Something went wrong. Please contact %s support.', 'groundhogg' ), white_labeled_name() ),
		];

		if ( use_experimental_features() ) {
			$bp_args['experimental_features'] = true;
		}

		$bp_args = apply_filters( 'groundhogg/admin/bulk_processor_args', $bp_args );

		?>
        <p>
			<?php _e( 'Total Complete: ' ); ?><b><span id="total-complete">0</span></b>
        </p>
        <p>
			<?php _e( 'Total Remaining: ' ); ?><b><span id="total-remaining">0</span></b>
        </p>
        <p>
			<?php echo html()->textarea( [
				'name'        => '',
				'id'          => 'bulk-log',
				'class'       => '',
				'value'       => __( '### LOG ###', 'groundhogg' ),
				'cols'        => '',
				'rows'        => '10',
				'readonly'    => true,
				'style'       => [ 'width' => '100%' ],
				'placeholder' => 'Log...',
			] ); ?>
        </p>
        <div id="job-complete" class="hidden">
            <p><?php _e( "The process is now complete.", 'groundhogg' ); ?></p>
            <p class="submit">
                <a class="button button-primary"
                   href="<?php echo admin_url( 'index.php' ); ?>">&larr;&nbsp;<?php _e( 'Return to dashboard.', 'groundhogg' ) ?></a>
            </p>
        </div>

        <script>
          var BulkProcessor = <?php echo wp_json_encode( $bp_args ); ?>;

          ( function ($, bp, items) {

            Object.assign(bp, {

              items            : 0,
              complete         : 0,
              all              : 0,
              size             : <?php echo $max_items; ?>,
              bulk_action_nonce: '<?php echo wp_create_nonce( $this->get_current_action() ); ?>',
              bulk_action      : '<?php echo $this->get_current_action(); ?>',
              bar              : null,
              total            : null,
              title            : '',
              log              : null,
              current_request  : {},
              retries          : 0,

              init: function () {

                this.items = items
                this.all = items.length
                this.bar = $('#bulk-job')
                this.log = $('#bulk-log')
                this.progress = $('#bulk-job-percentage')
                this.total = $('#total-complete')
                this.remaining = $('#total-remaining')
                this.title = document.title

                if (typeof this.experimental_features != 'undefined') {
                  this.experimental()
                }
                else {
                  this.send()
                }

              },

              experimental: function () {
                var threshold = 10000
                var processes = Math.ceil(items.length / threshold)
                // console.log( processes );

                for (var i = 0; i < processes; i++) {
                  this.send()
                }
              },

              getItems: function () {
                var end = this.size

                if (this.items.length < this.size) {
                  end = this.items.length
                }

                return this.items.splice(0, end)
              },

              isLastOfThem: function () {
                return this.items.length === 0
              },

              updateProgress: function () {

                var p = ( this.complete / this.all ) * 100

                p = p.toFixed(2)

                // this.bar.animate( { 'width': p + '%' } );
                this.bar.css('width', p + '%')
                this.progress.text(p + '%')
                document.title = '(' + p + '%) ' + this.title
                this.total.text(this.complete)
                this.remaining.text(this.all - this.complete)

                if (this.complete === this.all) {
                  $('#job-complete').removeClass('hidden')
                  this.progress.removeClass('spinner')
                }
              },

              error: function (response) {

                console.log(response)

                // retry
                if (this.retries > 0) {

                  console.log('Job failed. Retrying...')

                  this.retries -= 1
                  this.send_ajax()
                  return
                }

                var message = this.error_message

                if (typeof response.data != 'undefined') {
                  message = response.data[0].message
                }

                bp.bar.css('background-color', '#f70000')

                this.progress.removeClass('spinner')

                alert(message)
              },

              clean: function (obj) {

                if (typeof obj !== 'object' || obj === null) {
                  return
                }

                var propNames = Object.getOwnPropertyNames(obj)
                for (var i = 0; i < propNames.length; i++) {
                  var propName = propNames[i]
                  if (obj[propName] === null || obj[propName] === undefined || obj[propName] === '') {
                    delete obj[propName]
                  }
                }
              },

              send_ajax: function () {

                var self = this

                $.ajax({
                  type    : 'post',
                  url     : ajaxurl,
                  dataType: 'json',
                  data    : self.current_request,
                  success : function (response) {

                    console.log(response)

                    if (typeof response.complete !== 'undefined') {
                      self.complete += response.complete
                      self.updateProgress()

                      self.log.val(response.message + '\n' + self.log.val())

                      if (self.items.length > 0) {
                        self.send()
                      }

                      if (response.return_url !== undefined) {

                        setTimeout(function () {
                          window.location.replace(response.return_url)
                        }, 1000)
                      }

                    }
                    else {
                      self.error(response)
                    }

                  },
                  error   : function (response) {
                    self.error(response)
                  },
                })
              },

              send: function () {

                var self = this

                self.current_request = {
                  action           : 'bulk_action_listener',
                  bulk_action      : self.bulk_action,
                  bulk_action_nonce: self.bulk_action_nonce,
                  items            : this.getItems(),
                  the_end          : this.isLastOfThem(),
                }

                // Reset the number ofn allow retires
                this.retries = this.num_retries

                this.send_ajax()

              },

            })

            $(function () {
              bp.init()
            })

          } )(jQuery, BulkProcessor, <?php echo wp_json_encode( $items ); ?> )
        </script>
		<?php
	}
}
