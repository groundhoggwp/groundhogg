<?php

use Groundhogg\Extension;
use Groundhogg\License_Manager;
use function Groundhogg\action_input;
use function Groundhogg\html;

// Only show extensions we have access too.
$access = get_option( 'gh_master_license_access', [] );

$response = License_Manager::get_store_products( [
	'category' => [
		159,
		158,
		157,
	]
] );

if ( is_wp_error( $response ) ) {
	wp_die( $response );
}

$downloads = (array) $response->products;

usort( $downloads, function ( $a, $b ) {
	return strcmp( strtolower( $a->info->title ), strtolower( $b->info->title ) );
} );

?>
<div class="post-box-grid">
	<?php

	foreach ( $downloads as $download ):

		$extension = (object) $download;

		// Ignore these downloads as they're non downloadable
		if ( in_array( $download->info->id, [ 12344, 48143 ] ) ) {
			continue;
		}

		?>
        <div class="postbox">
            <div class="card-top">
                <h3 class="extension-title">
					<?php esc_html_e( $download->info->title ); ?>
                    <img class="thumbnail" src="<?php echo esc_url( $extension->info->thumbnail ); ?>"
                         alt="<?php esc_attr_e( $extension->info->title ); ?>">
                </h3>
                <p class="extension-description">
					<?php esc_html_e( $extension->info->excerpt ); ?>
                </p>
            </div>
            <div class="install-actions">

				<?php if ( $extension->info->link ): ?>
					<?php
					$pricing = (array) $extension->pricing;

					if ( count( $pricing ) > 1 ) {

						$price1 = min( $pricing );
						$price2 = max( $pricing );

						?>
                        <a class="button-secondary" target="_blank"
                           href="<?php echo $extension->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s - $%s)', 'action', 'groundhogg' ), $price1, $price2 ); ?></a>
						<?php
					} else {

						$price = array_pop( $pricing );

						if ( $price > 0.00 ) {
							?>
                            <a class="button-secondary" target="_blank"
                               href="<?php echo $extension->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s)', 'action', 'groundhogg' ), $price ); ?></a>
							<?php
						} else {
							?>
                            <a class="button-secondary" target="_blank"
                               href="<?php echo $extension->info->link; ?>"> <?php _ex( 'Download', 'action', 'groundhogg' ); ?></a>
							<?php
						}
					}
				endif; ?>

				<?php echo html()->e( 'a', [
					'href'   => $extension->info->link,
					'target' => '_blank',
					'class'  => 'more-details',
				], __( 'More details' ) ); ?>
            </div>
        </div>

	<?php
	endforeach;

	?></div>