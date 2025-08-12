<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( $total_pages > 1 ):
	?>

    <!-- Generate pagination buttons -->
    <div class="pagination">
        <!-- Previous button -->
		<?php if ( $current_page > 1 ) { ?>
            <a href="?_page=<?php echo absint( $current_page - 1 ); ?>">&laquo; <?php esc_html_e( 'Previous', 'groundhogg' ); ?></a>
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
			echo '<a href="?_page=' . absint( $i ) . '" ' . ( $current_page == $i ? 'class="active"' : '' ) . '>' . esc_html( $i ) . '</a>';
		}

		if ( $end_page < $total_pages ) {
			if ( $end_page < $total_pages - 1 ) {
				echo '<span>...</span>';
			}
			echo '<a href="?_page=' . absint( $total_pages ) . '">' . esc_html( $total_pages ) . '</a>';
		}
		?>

        <!-- Next button -->
		<?php if ( $current_page < $total_pages ) { ?>
            <a href="?_page=<?php echo absint( $current_page + 1 ); ?>"><?php esc_html_e( 'Next', 'groundhogg' ); ?> &raquo;</a>
		<?php } ?>
    </div>
<?php

endif;

?>
<p>
    <i><?php esc_html_e( 'Archived content may not reflect its original form as when initially sent. Not all emails are available in the archives. We do not guarantee the retention of any content for any amount of time.', 'groundhogg' ); ?></i>
</p>
