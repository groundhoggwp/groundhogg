<?php

namespace Groundhogg\Admin\Reports\Views;
use Groundhogg\Broadcast;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\get_url_var;


$broadcasts = get_db( 'broadcasts' );
$broadcasts = $broadcasts->query( [ 'status' => 'sent' ] );

$options = [];

foreach ( $broadcasts as $broadcast ) {
	$broadcast                       = new Broadcast( absint( $broadcast->ID ) );
	$options[ $broadcast->get_id() ] = $broadcast->get_title();
}
?>

<div class="groundhogg-chart-wrapper">
    <div class="groundhogg-chart-no-padding full-width">
        <h2 class="title"><?php _e( 'Forms', 'groundhogg' ); ?></h2>
        <div id="table_form_activity"></div>
    </div>
</div
