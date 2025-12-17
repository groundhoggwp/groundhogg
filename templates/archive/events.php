<?php

namespace Groundhogg;

use Groundhogg\Cli\Table;
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

include_once __DIR__ . '/../managed-page.php';

$contact = get_contactdata();

if ( ! $contact ) {
	wp_die( 'Archive is currently unavailable.' );
}

$permissions_key = get_permissions_key( 'view_archive', true );

if ( ! ( current_user_can( 'view_emails' ) || current_contact_and_logged_in_user_match() || check_permissions_key( $permissions_key, $contact, 'view_archive' ) ) ) {
	include __DIR__ . '/../preferences.php';

	return;
}

managed_page_head( __( 'Email Archive', 'groundhogg' ), 'archive' );

?>
    <div class="box">
        <h1 class="no-margin-top"><?php esc_html_e( 'Email Archive', 'groundhogg' );; ?></h1>
        <p><?php esc_html_e( 'Below is a list of all the emails you\'ve received in the past. Use the pagination to browse. Emails are ordered by date starting with the most recently received.', 'groundhogg' );; ?></p>

		<?php

		$per_page     = 10;
		$current_page = absint( get_url_var( '_page', 1 ) );

		$eventsQuery = new Table_Query( 'events' );
		$eventsQuery
			->setLimit( $per_page )
			->setOffset( ( $current_page - 1 ) * $per_page )
			->setFoundRows( true )
			->setOrderby( [ 'time', 'DESC' ], [ 'micro_time', 'DESC' ] )
			->where()
			->equals( 'status', Event::COMPLETE )
			->greaterThan( 'email_id', 0 )
			->equals( 'contact_id', $contact->get_id() );

		$search = sanitize_text_field( get_url_var( 'filter' ) );

		if ( $search ) {
			$join = $eventsQuery->addJoin( 'LEFT', 'emails' );
			$join->onColumn( 'ID', 'email_id' );
			$eventsQuery->where()->subWhere()
			            ->contains( "$join->alias.subject", $search )
			            ->contains( "$join->alias.plain_text", $search );
		}

		/**
         * Allow modifying the events query, perhaps to restrict what can appear in the archive.
         *
		 * @param $query Table_Query
		 */
        do_action_ref_array( 'groundhogg/archive/events_query', [ &$eventsQuery ] );

		$events = $eventsQuery->get_results();

		$total_events = $eventsQuery->get_found_rows();
		$total_pages  = ceil( $total_events / $per_page );

		$rows = array_map( function ( $e ) use ( $contact ) {

			$date  = new DateTimeHelper( absint( $e->time ) );
			$email = new Email( $e->email_id );

			if ( ! $email->exists() ) {
				return [
					$date->format( get_option( 'date_format' ) ),
					html()->e( 'i', [], __( 'Content unavailable.', 'groundhogg' ) )
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
						'href'  => managed_page_url( 'archive/p/' . dechex( $e->ID ) ),
					], $email->get_merged_subject_line() ),
					html()->e( 'span', [ 'class' => 'preview' ], $email->get_merged_pre_header() )
				] ),

			];

		}, $events );

		include __DIR__ . '/search.php';

		if ( $search ):

			?>
            <p><?php
				printf(
				    /* translators: 1: number of emails received, 2: search term */
                    esc_html__( 'You\'ve received %1$s emails matching %2$s.', 'groundhogg' ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( esc_html( number_format_i18n( $total_events ) ) ),
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( esc_html( $search ) )
                ); ?></p>
		<?php

		else:

			?>
            <p><?php
				printf(
				    /* translators: 1: number of emails received */
                    esc_html__( 'You\'ve received %s emails from us.', 'groundhogg' ),
                    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML
                    bold_it( esc_html( number_format_i18n( $total_events ) ) )
                );
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
