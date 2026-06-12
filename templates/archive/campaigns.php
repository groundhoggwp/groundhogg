<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {

	?>
    <style>
        a.campaign-title {
            font-size: 16px;
            font-weight: bold;
        }

        a + p {
            margin-top: 0;
        }
    </style>
	<?php

} );

include_once __DIR__ . '/../managed-page.php';

managed_page_head( __( 'Campaigns Archive', 'groundhogg' ), 'archive' );

?>
    <div class="box">
        <h1 class="no-margin-top"><?php esc_html_e( 'Campaigns Archive', 'groundhogg' );; ?></h1>
		<?php

		$per_page     = 10;
		$current_page = absint( get_url_var( '_page', 1 ) );
        $search       = sanitize_text_field( get_url_var( 'filter' ) );

        $list = list_campaigns_archive( [
            'search' => $search,
            'page' => $current_page,
            'per_page' => 10,
        ] );

		$items       = $list['items'];
		$total_items = $list['total_items'];
		$total_pages = $list['total_pages'];

		$contact = get_contactdata();

		$rows = array_map( function ( Campaign $campaign ) {

			return [
				html()->e( 'a', [
					'href' => managed_page_url( sprintf( '/campaigns/%s/', $campaign->get_slug() ) )
				], $campaign->get_name() ),
				$campaign->get_description()
			];

		}, $items );

		?>
        <p><?php esc_html_e( 'Missed an email from us? Browse the campaign archives!', 'groundhogg' ); ?></p>
		<?php

		include __DIR__ . '/search.php';

		if ( $search ):
			?>
            <p><?php
				printf(
				    /* translators: 1: number of campaigns found, 2: search term */
                    esc_html( _n( 'We found %1$s campaign matching %2$s.', 'We found %1$s campaigns matching %2$s.', $total_items, 'groundhogg' ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( number_format_i18n( $total_items ) ), bold_it( esc_html( $search ) )
                ); ?></p>
		    <?php
		else:
			?>
            <p><?php
				printf(
				    /* translators: 1: number of campaign archives available */
                    esc_html( _n( 'There is %s campaign archive available.', 'There are %s campaign archives available.', $total_items, 'groundhogg' ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( number_format_i18n( $total_items ) ) );
                ?></p>
		    <?php
		endif;

		foreach ( $items as $campaign ):

			html( 'a', [
				'href'  => managed_page_url( sprintf( '/campaigns/%s/', $campaign->get_slug() ) ),
				'class' => 'campaign-title'
			], esc_html( $campaign->get_name() ) );

			printf( ' (%s)', esc_html( number_format_i18n( $campaign->total ) ) );

			html( 'p', [
				'class' => 'campaign-description'
			], esc_html( $campaign->get_description() ) );


		endforeach;

		include __DIR__ . '/pagination.php';
		?>
    </div>
	<?php

managed_page_footer();
