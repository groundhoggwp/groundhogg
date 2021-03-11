<?php

global $wpdb;

use Groundhogg\Classes\Activity;
use Groundhogg\Contact;
use Groundhogg\Event;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;

/**
 * @var $contact Contact
 */

$events = get_db( 'events' )->query( [
	'contact_id' => $contact->get_id(),
	'orderby'    => 'time',
	'order'      => 'DESC',
	'limit'      => 10,
] );

$activity = get_db( 'activity' )->query( [
	'contact_id' => $contact->get_id(),
	'orderby'    => 'timestamp',
	'order'      => 'DESC',
	'limit'      => 10,
] );

$events = array_map( function ( $event ) {
	return new Event( $event->ID );
}, $events );

$activity = array_map( function ( $a ) {
	return new Activity( $a->ID );
}, $activity );

$merged_history = array_merge( $events, $activity );

usort( $merged_history, function ( $a, $b ) {
	return $a->get_time() - $b->get_time();
} );

/**
 * @var $merged_history Event[]|Activity[]
 */

?>
<ul class="info-list with-icon">
	<?php foreach ( $merged_history as $h ): ?>
		<li>
			<?php if ( is_a( $h, Event::class ) ): ?>
			<?php elseif ( is_a( $h, Activity::class ) ) : ?>
			<?php endif; ?>
		</li>
	<?php endforeach; ?>
</ul>


