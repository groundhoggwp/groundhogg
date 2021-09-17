<?php

namespace Groundhogg;


class Reviews {

	public function __construct() {

		add_action( 'admin_notices', [ $this, 'show_review_request' ] );
		add_action( 'wp_ajax_groundhogg_dismiss_review', [ $this, 'dismiss_review' ] );

	}

	public function show_review_request() {
		if ( ! current_user_can( 'administrator' ) || is_white_labeled() || get_transient( 'groundhogg_review_request_dismissed' ) || notices()->is_dismissed( 'gh_review_request' ) ) {
			return;
		}

		$message = sprintf(
			esc_html__( 'Is Groundhogg working for you? Show your appreciation by leaving a %1$s review! %2$s | %3$s | %4$s', 'groundhogg' ),
			'&#x2B50;&#x2B50;&#x2B50;&#x2B50;&#x2B50;',
			html()->e( 'a', [
				'class' => '',
				'style' => [ 'color' => 'green' ],
				'href' => 'https://wordpress.org/support/plugin/groundhogg/reviews/#new-post',
				'target' => '_blank'
			], __( "I'll leave a review!", 'groundhogg' ) ),
			html()->e( 'a', [
				'class' => 'notice-dismiss-link',
				'style' => [ 'color' => '#a00' ],
				'href'  => '#'
			], __( "I don't want to", 'groundhogg' ) ),
			html()->e( 'a', [
				'class' => 'notice-dismiss-link permanent',
				'style' => [ 'color' => '' ],
				'href'  => '#'
			], __( "I already did", 'groundhogg' ) )
		);

		$html_message = sprintf( '<div class="review-request notice notice-info is-dismissible">%s</div>', wpautop( $message ) );

		echo wp_kses_post( $html_message );

		?>
        <script>
          ( function ($) {

            function dismiss_review (permanently) {

              var args = { action: 'groundhogg_dismiss_review' }

              if (permanently) {
                args.permanent = true
              }

              adminAjaxRequest(args, function (response) {
                console.log(response)
              })
            }

            function init () {

              $(document).on('click', '.review-request .notice-dismiss', function (e) {
                dismiss_review(false)
              })

              $('.review-request .notice-dismiss-link').click(function (e) {
                e.preventDefault()

                var $this = $(this)

                if ($this.hasClass('permanent')) {
                  dismiss_review(true)
                }
                else {
                  dismiss_review(false)
                }

                $this.closest('.notice').fadeOut(100, function () {
                  $(this).remove()
                })
              })
            }

            $(function () {
              init()
            })
          } )(jQuery)
        </script>
		<?php

	}

	public function dismiss_review() {

		if ( ! current_user_can( 'administrator' ) ) {
			return;
		}

		if ( get_request_var( 'permanent' ) ) {
			notices()->dismiss_notice( 'gh_review_request' );
		} else {
			set_transient( 'groundhogg_review_request_dismissed', WEEK_IN_SECONDS );
		}

		wp_send_json_success();
	}

}