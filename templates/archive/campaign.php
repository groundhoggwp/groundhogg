<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;
use Groundhogg\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {

	?>
    <style>

        table.wp-list-table {

            td:first-child {
                width: 170px;
                padding-left: 30px;
                vertical-align: middle;
            }
        }

        div.subject-and-preview {
            display: grid;
            gap: 0 5px;
            grid-template-columns: min-content max-content;
            grid-template-areas:
    "avatar subject"
    "avatar preview";

            img.avatar {
                grid-area: avatar;
                float: left;
                margin-right: 10px;
                height: 35px;
                width: 35px;
                border-radius: 50%;
            }

            a.subject {
                grid-area: subject;
                font-size: 15px;
                font-weight: 500;
            }

            span.preview {
                grid-area: preview;
            }
        }
    </style>
	<?php

} );

$campaign_slug = get_query_var( 'campaign' );

$campaign = new Campaign( $campaign_slug, 'slug' );

if ( ! $campaign->exists() ) {
	return;
}

include_once __DIR__ . '/../managed-page.php';

/* translators: 1: campaign name */
managed_page_head( sprintf( __( '%s Archive', 'groundhogg' ), $campaign->get_name() ), 'archive' );

?>
    <div class="box">
        <p>
            <a href="<?php echo esc_url( managed_page_url( 'campaigns' ) ); ?>">&larr; <?php esc_html_e( 'All campaign archives', 'groundhogg' ); ?></a>
        </p>
        <h1 class="no-margin-top"><?php
            /* translators: 1: campaign name */
		    echo esc_html( sprintf( __( '%s Archive', 'groundhogg' ), $campaign->get_name() ) );
            ?></h1>
		<?php

		$per_page     = 10;
		$current_page = absint( get_url_var( '_page', 1 ) );

		$search = sanitize_text_field( get_url_var( 'filter' ) );

		$broadcastQuery = new Table_Query( 'broadcasts' );
		$broadcastQuery
			->setLimit( $per_page )
			->setOffset( ( $current_page - 1 ) * $per_page )
			->setFoundRows( true )
			->setOrderby( [ 'send_time', 'DESC' ] )
			->where()
			->equals( 'object_type', 'email' )
			->equals( 'status', 'sent' );

		$broadcastQuery->parseFilters( [
			[
				[
					'type'      => 'campaigns',
					'campaigns' => [ $campaign->get_id() ]
				]
			]
		] );

		if ( $search ) {
			$join = $broadcastQuery->addJoin( 'LEFT', 'emails' );
			$join->onColumn( 'ID', 'object_id' );
			$broadcastQuery->where()->subWhere()
			               ->contains( "$join->alias.subject", $search )
			               ->contains( "$join->alias.plain_text", $search );
		}

		$items       = $broadcastQuery->get_objects();
		$total_items = $broadcastQuery->get_found_rows();

		$total_pages = ceil( $total_items / $per_page );

		$contact = get_contactdata();

		$rows = array_map( function ( Broadcast $broadcast ) use ( $contact, $campaign ) {

			$date  = new DateTimeHelper( $broadcast->get_send_time() );
			$email = $broadcast->get_object();

			if ( ! $email->exists() ) {
				return [
					$date->format( get_option( 'date_format' ) ),
					'<i>Content deleted.</i>'
				];
			}

			$email->set_contact( $contact );

			return [
				$date->wpDateFormat(),
				html()->e( 'div', [ 'class' => 'subject-and-preview' ], [
					html()->e( 'img', [
						'src'   => get_avatar_url( $email->get_from_email() ),
						'class' => 'avatar'
					] ),
					html()->e( 'a', [
						'class' => 'subject',
						'href'  => managed_page_url( sprintf( 'campaigns/%s/b/%d/', $campaign->get_slug(), $broadcast->get_id() ) ),
					], esc_html( $email->get_merged_subject_line() ) ),
					html()->e( 'span', [ 'class' => 'preview' ], esc_html( $email->get_merged_pre_header() ) )
				] ),

			];

		}, $items );

        html( 'p', [], esc_html( $campaign->get_description() ) );

		include __DIR__ . '/search.php';

		if ( $search ):
			?>
            <p><?php
				printf(
				    /* translators: 1: number of emails found, 2: search term */
                    esc_html( _n( 'We found %1$s email in this archive matching %2$s.', 'We found %1$s emails in this archive matching %2$s.', $total_items, 'groundhogg' ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( number_format_i18n( $total_items ) ), bold_it( esc_html( $search ) ) );
                ?></p>
		    <?php
		else:
			?>
            <p><?php
				printf(
				        /* translators: 1: number of emails in the archive */
                        esc_html( _n( 'There is %s email in this archive.', 'There are %s emails in this archive.', $total_items, 'groundhogg' ) ),
                        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                        bold_it( number_format_i18n( $total_items ) ) );
                ?></p>
		    <?php
		endif;

		?>
        <div class="list-wrap"><?php

			html()->list_table( [
				'class' => 'archive-list'
			], [], $rows, false );

			?></div><?php

		include __DIR__ . '/pagination.php';
		?>
    </div>
	<?php

managed_page_footer();
