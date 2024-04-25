<?php

namespace Groundhogg;

use Groundhogg\DB\Query\Table_Query;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_head', function () {

	?>
    <style>
        a.campaign-title {
            font-size: 16px;
            font-weight: bold;
        }

        a + p {
            margin-top: 0;
        }
    </style>
	<?php

} );

include_once __DIR__ . '/../managed-page.php';

managed_page_head( __( 'Campaigns Archive', 'groundhogg' ), 'archive' );

?>
    <div class="box">
        <h1 class="no-margin-top"><?php _e( 'Campaigns Archive', 'groundhogg' ); ?></h1>
		<?php

		$per_page     = 10;
		$current_page = absint( get_url_var( '_page', 1 ) );

		$search = sanitize_text_field( get_url_var( 'filter' ) );

		$assetsQuery = new Table_Query( 'object_relationships' );
		$assetsQuery->setSelect( [ 'COUNT(primary_object_id)', 'total' ], [ 'secondary_object_id', 'campaign_id' ] )->setGroupby( 'secondary_object_id' )
		            ->where()
		            ->equals( 'primary_object_type', 'broadcast' )
		            ->equals( 'secondary_object_type', 'campaign' );

		$campaignsQuery = new Table_Query( 'campaigns' );

		$join = $campaignsQuery->addJoin( 'LEFT', [ $assetsQuery, 'assets' ] );
		$join->onColumn( 'campaign_id' );

		$campaignsQuery->add_safe_column( "$campaignsQuery->alias.*" );

		$campaignsQuery
			->setSelect( "$campaignsQuery->alias.*", "$join->alias.total" )
			->setLimit( $per_page )
			->setOffset( ( $current_page - 1 ) * $per_page )
			->setOrderby( $join->alias . '.total' )
			->setOrder( 'DESC' )
			->setFoundRows( true )
			->where()
            ->greaterThan( $join->alias . '.total', 0 )
			->equals( 'visibility', 'public' );

		if ( $search ) {
			$campaignsQuery->where()->subWhere()
			               ->contains( 'name', $search )
			               ->contains( 'description', $search );
		}

		$items       = $campaignsQuery->get_objects();
		$total_items = $campaignsQuery->get_found_rows();

		$total_pages = ceil( $total_items / $per_page );

		$contact = get_contactdata();

		$rows = array_map( function ( Campaign $campaign ) {

			return [
				html()->e( 'a', [
					'href' => managed_page_url( sprintf( '/campaigns/%s/', $campaign->get_slug() ) )
				], $campaign->get_name() ),
				$campaign->get_description()
			];

		}, $items );

		?>
        <p><?php _e( 'Missed an email from us? Browse the campaign archives!', 'groundhogg' ) ?></p>
		<?php

		include __DIR__ . '/search.php';

		if ( $search ):
			?>
            <p><?php printf( _n( 'We found %s campaign matching %s.', 'We found %s campaigns matching %s.', $total_items, 'groundhogg' ), bold_it( number_format_i18n( $total_items ) ), bold_it( esc_html( $search ) ) ); ?></p>
		    <?php
		else:
			?>
            <p><?php printf( _n( 'There is %s campaign archive available.', 'There are %s campaign archives available.', $total_items, 'groundhogg' ), bold_it( number_format_i18n( $total_items ) ) ); ?></p>
		    <?php
		endif;

		foreach ( $items as $campaign ):

			echo html()->e( 'a', [
				'href'  => managed_page_url( sprintf( '/campaigns/%s/', $campaign->get_slug() ) ),
				'class' => 'campaign-title'
			], $campaign->get_name() );

			printf( ' (%s)', number_format_i18n( $campaign->total ) );

			echo html()->e( 'p', [
				'class' => 'campaign-description'
			], $campaign->get_description() );


		endforeach;

		include __DIR__ . '/pagination.php';
		?>
    </div>
	<?php

managed_page_footer();
