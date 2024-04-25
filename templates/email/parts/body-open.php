<?php

use function Groundhogg\is_browser_view;
use function Groundhogg\managed_page_url;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $campaign;
global $broadcast;
global $event;
global $email;

// Archive navigation
if ( is_browser_view() && isset( $campaign ) && isset( $broadcast ) ) {
	?>
    <div class="archive-header">
        <a target="_self" href="<?php echo esc_url( managed_page_url( sprintf( 'campaigns/%s/', $campaign->get_slug() ) ) ) ?>"><?php _e( '&larr; Back to archive', 'groundhogg' ) ?></a>
	    <div class="subject-and-preview">
		    <h1><?php esc_html_e( $broadcast->get_object()->get_merged_subject_line() ); ?></h1>
		    <p><?php esc_html_e( $broadcast->get_object()->get_merged_pre_header() ); ?></p>
	    </div>
	    <div></div>
    </div>
	<?php
}

if ( is_browser_view() && isset( $event ) && isset( $email ) ) {

    // Exclude the archive link from being tracked
    add_filter( 'groundhogg/is_url_excluded_from_tracking', function ( bool $matched, string $url ){
        if ( $url === managed_page_url( 'archive' ) ){
            return true;
        }

        return $matched;
    }, 10, 2 );

	?>
    <div class="archive-header">
        <a target="_self" href="<?php echo esc_url( managed_page_url( 'archive' ) ); ?>"><?php _e( '&larr; Back to archive', 'groundhogg' ) ?></a>
        <div class="subject-and-preview">
            <h1><?php esc_html_e( $email->get_merged_subject_line() ); ?></h1>
            <p><?php esc_html_e( $email->get_merged_pre_header() ); ?></p>
        </div>
        <div></div>
    </div>
	<?php
}
