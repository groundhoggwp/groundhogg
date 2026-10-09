<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


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
            <p class="archive-count"><?php
				printf(
				    /* translators: 1: number of campaigns found, 2: search term */
                    esc_html( _n( 'We found %1$s campaign matching %2$s.', 'We found %1$s campaigns matching %2$s.', $total_items, 'groundhogg' ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( number_format_i18n( $total_items ) ), bold_it( esc_html( $search ) )
                ); ?></p>
		    <?php
		else:
			?>
            <p class="archive-count"><?php
				printf(
				    /* translators: 1: number of campaign archives available */
                    esc_html( _n( 'There is %s campaign archive available.', 'There are %s campaign archives available.', $total_items, 'groundhogg' ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( number_format_i18n( $total_items ) ) );
                ?></p>
		    <?php
		endif;

		?>
        <div class="archive-cards">
			<?php foreach ( $items as $campaign ): ?>
                <a class="archive-card" href="<?php echo esc_url( managed_page_url( sprintf( '/campaigns/%s/', $campaign->get_slug() ) ) ); ?>">
                    <span class="archive-card-title">
                        <span><?php echo esc_html( $campaign->get_name() ); ?></span>
                        <span class="archive-card-count"><?php
	                        /* translators: %s: the number of emails in a campaign archive */
	                        echo esc_html( sprintf( _n( '%s email', '%s emails', (int) $campaign->total, 'groundhogg' ), number_format_i18n( $campaign->total ) ) );
	                        ?></span>
                    </span>
					<?php if ( $campaign->get_description() ): ?>
                        <span class="archive-card-description"><?php echo esc_html( $campaign->get_description() ); ?></span>
					<?php endif; ?>
                </a>
			<?php endforeach; ?>
        </div>
		<?php

		include __DIR__ . '/pagination.php';
		?>
    </div>
	<?php

managed_page_footer();
