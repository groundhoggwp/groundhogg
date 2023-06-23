<?php

namespace Groundhogg;

use Groundhogg\Utils\DateTimeHelper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

include_once __DIR__ . '/../managed-page.php';

$contact = get_contactdata();

if ( ! $contact ) {
	wp_die( 'Archive is currently unavailable.' );
}

$permissions_key = get_permissions_key( 'view_archive', true );

if ( current_user_can( 'view_emails' ) || current_contact_and_logged_in_user_match() || check_permissions_key( $permissions_key, $contact, 'view_archive' ) ):

	managed_page_head( __( 'Email Archive', 'groundhogg' ), 'archive' );

	?>
	<div class="box">
		<p><b><?php _e( 'Email Archive', 'groundhogg' ); ?></b></p>
		<p><?php _e( 'Below is a list of all the emails you\'ve received in the past. Use the pagination to browse. Emails are ordered by date starting with the most recently received.', 'groundhogg' ); ?></p>

		<?php

		$per_page     = 10;
		$current_page = absint( get_url_var( '_page', 1 ) );

		$events = get_db( 'events' )->query( [
			'status'     => Event::COMPLETE,
			// Force email only
			'email_id'   => [ '>', 0 ],
			'contact_id' => $contact->get_id(),
			'orderby'    => 'time',
			'limit'      => $per_page,
			'offset'     => ( $current_page - 1 ) * $per_page,
			'found_rows' => true,
		] );

		$total_events = get_db( 'events' )->found_rows();
		$total_pages  = ceil( $total_events / $per_page );

		$rows = array_map( function ( $e ) use ( $contact ) {

			$date  = new DateTimeHelper( absint( $e->time ) );
			$email = new Email( $e->email_id );

			if ( ! $email->exists() ) {
				return [
					$date->format( get_option( 'date_format' ) ),
					'<i>Content deleted.</i>'
				];
			}

			$email->set_contact( $contact );

			return [
				$date->format( get_option( 'date_format' ) ),
				html()->e( 'a', [
					'href' => managed_page_url( 'archive/p/' . dechex( $e->ID ) ),
				], $email->get_merged_subject_line() )
			];

		}, $events );

		?>
		<p><?php printf( __( 'You\'ve received %s emails from us.', 'groundhogg' ), bold_it( number_format_i18n( $total_events ) ) ); ?></p><?php

		?>
		<div class="list-wrap"><?php

			html()->list_table( [
				'class' => 'archive-list'
			], [
				__( 'Date' ),
				__( 'Subject Line' )
			], $rows, false );

			?></div><?php

		if ( $total_pages > 1 ):
			?>

			<!-- Generate pagination buttons -->
			<div class="pagination">
				<!-- Previous button -->
				<?php if ( $current_page > 1 ) { ?>
					<a href="?_page=<?php echo( $current_page - 1 ); ?>">&laquo; <?php _e( 'Previous' ) ?></a>
				<?php } ?>

				<!-- Page numbers -->
				<?php
				// Determine the starting and ending page numbers
				$start_page = max( 1, $current_page - 4 );
				$end_page   = min( $total_pages, $start_page + 9 );

				// Adjust the starting page number if nearing the end
				if ( $end_page - $start_page < 9 ) {
					$start_page = max( 1, $end_page - 9 );
				}

				// Display page numbers with ellipses
				if ( $start_page > 1 ) {
					echo '<a href="?_page=1">1</a>';
					if ( $start_page > 2 ) {
						echo '<span>...</span>';
					}
				}

				for ( $i = $start_page; $i <= $end_page; $i ++ ) {
					echo '<a href="?_page=' . $i . '" ' . ( $current_page == $i ? 'class="active"' : '' ) . '>' . $i . '</a>';
				}

				if ( $end_page < $total_pages ) {
					if ( $end_page < $total_pages - 1 ) {
						echo '<span>...</span>';
					}
					echo '<a href="?_page=' . $total_pages . '">' . $total_pages . '</a>';
				}
				?>

				<!-- Next button -->
				<?php if ( $current_page < $total_pages ) { ?>
					<a href="?_page=<?php echo( $current_page + 1 ); ?>"><?php _e( 'Next' ) ?> &raquo;</a>
				<?php } ?>
			</div>
		<?php


		endif;

		?>
	</div>
	<?php

	managed_page_footer();

else:
	include __DIR__ . '/../preferences.php';

endif;