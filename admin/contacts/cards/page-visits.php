<?php

/**
 * Show the page visits history
 *
 * - ID with link to edit
 * - Date of last login
 *
 * @var $contact Contact
 */

use Groundhogg\Classes\Page_Visit;
use Groundhogg\Contact;
use function Groundhogg\collapse_string;
use function Groundhogg\get_db;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$page_visits = get_db( 'page_visits' )->query( [
	'contact_id' => $contact->get_id(),
	'orderby'    => 'timestamp',
	'order'      => 'DESC'
] );

if ( ! empty( $page_visits ) ) {

$cur_date = false;

while ( ! empty( $page_visits ) ) {

$page_visit = array_shift( $page_visits );

$page_visit = new Page_Visit( $page_visit );

$is_first = true;

// Set the current date
if ( ! $cur_date || $cur_date->format( 'Y-m-d' ) !== $page_visit->get_date()->format( 'Y-m-d' ) ) {

// will execute everytime except the first time
if ( $cur_date ){
$is_first = false;
?></ul></div></div><?php
}

$cur_date = $page_visit->get_date();
?>
<div class="ic-section <?php echo $is_first ? 'open' : '' ?> ">
    <div class="ic-section-header">
        <div class="ic-section-header-content">
	        <?php echo esc_html( $cur_date->format( get_option( 'date_format' ) ) ); ?>
        </div>
    </div>
    <div class="ic-section-content">
        <ul class="info-list"><?php

			}

			?>
            <li><?php

                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                echo html()->e( 'a', [
		                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'href'   => $page_visit->get_url(),
						'target' => '_blank',
		                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						'title'  => $page_visit->get_path(),
					], esc_html( collapse_string( $page_visit->get_path() ) ), false ) . ' (' . esc_html( $page_visit->get_date()->format( get_option( 'time_format' ) ) ) . ')' ?></li>
			<?php

			}

			?></ul>
    </div>
</div><?php

} else {
	?>
    <p><?php esc_html_e( 'No tracked site activity yet.', 'groundhogg' ); ?></p>
	<?php
}
