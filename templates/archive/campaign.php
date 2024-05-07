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

managed_page_head( sprintf( __( '%s Archive', 'groundhogg' ), $campaign->get_name() ), 'archive' );

?>
    <div class="box">
        <p>
            <a href="<?php echo esc_url( managed_page_url( 'campaigns' ) ); ?>">&larr; <?php _e( 'All campaign archives', 'groundhogg' ) ?></a>
        </p>
        <h1 class="no-margin-top"><?php printf( __( '%s Archive', 'groundhogg' ), $campaign->get_name() ); ?></h1>
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
					], $email->get_merged_subject_line() ),
					html()->e( 'span', [ 'class' => 'preview' ], $email->get_merged_pre_header() )
				] ),

			];

		}, $items );

        echo html()->e('p', [], $campaign->get_description() );

		include __DIR__ . '/search.php';

		if ( $search ):
			?>
            <p><?php printf( _n(  'We found %s email in this archive matching %s.', 'We found %s emails in this archive matching %s.', $total_items, 'groundhogg' ), bold_it( number_format_i18n( $total_items ) ), bold_it( esc_html( $search ) ) ); ?></p>
		    <?php
		else:
			?>
            <p><?php printf( _n( 'There is %s email in this archive.', 'There are %s emails in this archive.', $total_items, 'groundhogg' ), bold_it( number_format_i18n( $total_items ) ) ); ?></p>
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
