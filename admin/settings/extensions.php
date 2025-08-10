<?php

use Groundhogg\License_Manager;
use function Groundhogg\html;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

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
    // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WP_Error in use
	wp_die( $response );
}

$downloads = (array) $response->products;

usort( $downloads, function ( $a, $b ) {
	return strcmp( strtolower( $a->info->title ), strtolower( $b->info->title ) );
} );

?>
<style>
    .gh-panel .display-flex p:first-child {
        margin-top: 0;
    }
</style>
<div class="post-box-grid">
	<?php

	foreach ( $downloads as $download ):

		$extension = (object) $download;

		// Ignore these downloads as they're non downloadable
		if ( in_array( $download->info->id, [ 12344, 48143 ] ) ) {
			continue;
		}

		?>
        <div class="gh-panel">
            <div class="gh-panel-header">
                <h2>
					<?php echo esc_html( $download->info->title ); ?>
                </h2>
            </div>
            <div class="inside">
                <div class="display-flex gap-20">
                    <div style="width: 100%">
                        <img width="100%" height="auto" style="border-radius: 5px" class="thumbnail" src="<?php echo esc_url( $extension->info->thumbnail ); ?>"
                             alt="<?php echo esc_attr( $extension->info->title ); ?>">
                    </div>
                    <div style="width: 100%">
						<?php echo wp_kses_post( wpautop( $extension->info->excerpt ) ); ?>
                    </div>
                </div>
                <p></p>
                <div class="display-flex space-between">
					<?php if ( $extension->info->link ): ?>
						<?php
						$pricing = (array) $extension->pricing;

						if ( count( $pricing ) > 1 ) {

							$price1 = min( $pricing );
							$price2 = max( $pricing );

							?>
                            <a class="gh-button secondary" target="_blank"
                               href="<?php echo esc_url( $extension->info->link ); ?>"> <?php
                                /* translators: 1: min price, 2: max price */
                                echo esc_html( sprintf( _x( 'Buy Now ($%1$s - $%2$s)', 'action', 'groundhogg' ), $price1, $price2 ) ); ?></a>
							<?php
						} else {

							$price = array_pop( $pricing );

							if ( $price > 0.00 ) {
								?>
                                <a class="gh-button secondary" target="_blank"
                                   href="<?php echo esc_url( $extension->info->link ); ?>"> <?php
                                   /* translators: %s: the price of the extension */
                                    echo esc_html( sprintf( _x( 'Buy Now ($%s)', 'action', 'groundhogg' ), $price ) ); ?></a>
								<?php
							} else {
								?>
                                <a class="gh-button secondary" target="_blank"
                                   href="<?php echo esc_url( $extension->info->link ); ?>"> <?php echo esc_html_x( 'Download', 'action', 'groundhogg' ); ?></a>
								<?php
							}
						}
					endif;

                    html('a', [
						'href'   => $extension->info->link,
						'target' => '_blank',
						'class'  => 'more-details',
					], esc_html__( 'More details' , 'groundhogg' ) ); ?>
                </div>
            </div>
        </div>

	<?php
	endforeach;

	?></div>
