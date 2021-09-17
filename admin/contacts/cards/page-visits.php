<?php

/**
 * Show the page visits history
 *
 * - ID with link to edit
 * - Date of last login
 *
 * @var $contact \Groundhogg\Contact
 */

use Groundhogg\Classes\Page_Visit;

$page_visits = \Groundhogg\get_db( 'page_visits' )->query( [
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
			<?php echo $cur_date->format( get_option( 'date_format' ) ); ?>
		</div>
	</div>
	<div class="ic-section-content">
		<ul class="info-list"><?php

			}

			?>
			<li><?php echo \Groundhogg\html()->e( 'a', [
						'href'   => $page_visit->get_url(),
						'target' => '_blank'
					], $page_visit->get_path(), false ) . ' (' . $page_visit->get_date()->format( get_option( 'time_format' ) ) . ')' ?></li>
			<?php

			}

			?></ul>
	</div>
</div><?php

}