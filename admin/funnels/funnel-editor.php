<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Step;
use function Groundhogg\Admin\Reports\Views\get_funnel_id;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\is_option_enabled;
use function Groundhogg\is_white_labeled;
use function Groundhogg\key_to_words;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

/**
 * Edit Funnel
 *
 * This page allows one to edit the funnels they have installed.
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$funnel_id = absint( get_request_var( 'funnel' ) );

$funnel = new Funnel( $funnel_id );

?>
<form method="post" id="funnel-form">
	<?php wp_nonce_field(); ?>
	<?php $args = array(
		'type'  => 'hidden',
		'name'  => 'funnel',
		'id'    => 'funnel',
		'value' => $funnel_id
	);
	echo Plugin::$instance->utils->html->input( $args ); ?>
    <div class="funnel-editor-header">

		<?php if ( ! is_white_labeled() ): ?>
            <svg viewBox="38.053 7.279 310.877 351.102" xmlns="http://www.w3.org/2000/svg">
                <g>
                    <path d="M 348.93 258.42 L 348.93 107.24 C 348.927 98.919 344.486 91.231 337.28 87.07 L 206.35 11.47 C 199.144 7.31 190.266 7.31 183.06 11.47 L 52.13 87.08 C 44.926 91.242 40.489 98.93 40.49 107.25 L 40.49 258.43 C 40.491 266.749 44.927 274.437 52.13 278.6 L 183.06 354.2 C 190.268 358.364 199.152 358.364 206.36 354.2 L 337.28 278.6 C 344.486 274.439 348.927 266.751 348.93 258.43 L 348.93 258.42"
                          fill="#ff7b01"/>
                    <path d="M 88.07 257.31 C 87.492 255.966 86.753 254.696 85.87 253.53 C 85.87 253.53 75.79 259.83 66.65 246.29 C 57.52 232.74 60.35 188.96 60.35 188.96 C 60.35 188.96 42.72 188.33 38.62 171.01 C 34.52 153.69 53.74 137.94 60.98 134.16 C 62.88 118.41 65.71 111.17 65.71 111.17 C 65.71 111.17 45.87 101.72 50.28 73.06 C 54.68 44.4 86.81 29.28 108.54 45.66 C 154.84 11.64 214.37 9.44 257.52 35.58 C 271.38 26.13 290.9 23.93 307.28 42.2 C 323.66 60.46 312.32 83.45 305.71 89.12 C 311.06 97 314.21 107.39 314.21 107.39 C 314.21 107.39 332.47 116.84 337.83 131.96 C 343.18 147.08 327.75 155.26 327.75 155.26 C 327.75 155.26 337.02 180.22 335.94 201.56 C 334.68 226.45 325.54 235.26 317.67 234.32 C 314.71 244.389 309.668 253.724 302.87 261.72 C 309.867 269.053 316.315 280.873 320.091 288.526 L 206.36 354.2 C 199.152 358.364 190.268 358.364 183.06 354.2 L 57.495 281.698 C 65.004 274.649 79.541 261.535 88.07 257.31 Z"
                          fill="#fff"/>
                    <path d="M 114.83 259.62 C 106.396 249.472 99.012 238.496 92.79 226.86 C 92.79 226.86 78.39 242.27 77.09 216.3 C 75.79 190.33 76.41 174.58 76.41 174.58 C 76.41 174.58 49.96 173.95 53.74 163.24 C 57.51 152.54 78.3 137.42 78.3 137.42 C 78.3 137.42 79.56 114.11 85.23 101.52 C 69.48 95.22 63.81 85.14 70.74 68.76 C 77.67 52.38 99.09 48.6 109.17 63.09 C 155.78 25.92 225.7 26.55 259.72 55.53 C 272.32 39.78 288.06 37.89 298.77 53.63 C 309.48 69.39 288.07 85.13 288.07 85.13 L 300.67 113.48 C 300.67 113.48 321.83 126.2 322.27 135.9 C 322.71 145.6 311.37 141.2 311.37 141.2 C 311.37 141.2 323.97 179.62 322.71 200.4 C 321.45 221.2 309.48 213.63 309.48 213.63 C 304.309 230.841 295.5 246.74 283.65 260.25 C 295.286 275.752 301.79 288.694 305.262 297.089 L 206.36 354.2 C 199.152 358.364 190.269 358.364 183.06 354.2 L 70.841 289.404 C 84.305 277.838 99.062 267.842 114.83 259.62 Z M 220.19 58.11 C 201.083 47.078 177.2 60.868 177.2 82.93 C 177.2 93.169 182.663 102.631 191.53 107.75 C 210.637 118.782 234.52 104.992 234.52 82.93 C 234.52 72.691 229.057 63.229 220.19 58.11 Z M 270.205 143.687 C 255.272 134.642 236.147 145.155 235.78 162.61 C 235.603 171.052 240.13 178.894 247.53 182.961 C 262.83 191.372 281.497 180.065 281.13 162.61 C 280.967 154.848 276.845 147.709 270.205 143.687 Z M 165.451 152.781 C 150.809 143.909 132.053 154.213 131.69 171.33 C 131.514 179.612 135.956 187.305 143.216 191.293 C 158.221 199.537 176.523 188.447 176.16 171.33 C 175.999 163.722 171.959 156.725 165.451 152.781 Z M 201.77 248.91 L 228.85 245.13 L 230.74 225.6 L 214.36 219.93 L 196.73 230.01 L 201.77 248.91 Z"
                          fill="#cfa756"/>
                    <path d="M 206.8 128.81 C 135.84 135.52 95.1 149.38 57.3 169.12 C 48.9 166.18 64.44 148.54 78.3 137.41 C 78.14 125.096 80.498 112.88 85.23 101.51 C 85.23 101.51 95.94 101.93 105.18 98.15 C 108.54 86.81 118.2 77.15 118.2 77.15 C 116.262 72.366 113.722 67.85 110.64 63.71 C 123.65 45.24 174.47 26.76 226.54 38.94 C 278.61 51.12 299.19 114.1 299.19 114.1 C 299.19 114.1 323.55 125.03 322.29 139.3 C 304.65 130.9 255.42 124.2 206.8 128.8 L 206.8 128.81 Z M 220.19 58.11 C 201.083 47.079 177.2 60.868 177.2 82.93 C 177.2 93.17 182.662 102.631 191.53 107.751 C 210.636 118.782 234.52 104.993 234.52 82.93 C 234.52 72.691 229.057 63.23 220.19 58.11 Z"
                          fill="#ff7b01"/>
                    <path d="M 265.93 176.4 C 256.207 176.393 250.138 165.864 255.005 157.448 C 259.149 150.281 268.951 148.972 274.83 154.8 C 274.55 154.147 274.23 153.513 273.87 152.9 C 266.83 140.71 249.25 140.71 242.22 152.9 C 235.18 165.08 243.98 180.3 258.04 180.3 C 262.54 180.307 266.882 178.647 270.23 175.64 C 268.852 176.144 267.397 176.401 265.93 176.4 Z M 179.34 172.26 C 179.34 176.88 178.13 181.42 175.81 185.43 C 174.745 187.283 173.463 189.002 171.99 190.55 C 161.95 201.72 150.55 201.68 151.4 201.16 C 152.24 200.64 154.18 199.6 156.36 198.38 C 145.826 199.77 135.496 194.653 130.22 185.43 C 120.09 167.88 132.75 145.93 153.02 145.93 C 167.56 145.93 179.346 157.72 179.34 172.26 Z M 169.68 162.64 C 162.28 149.81 143.76 149.81 136.35 162.64 C 128.95 175.47 138.2 191.51 153.02 191.51 C 158.58 191.51 163.6 189.14 167.12 185.36 C 157.869 190.043 147.017 182.956 147.586 172.602 C 148.156 162.249 159.72 156.394 168.402 162.065 C 168.994 162.452 169.555 162.885 170.08 163.36 L 169.68 162.64 Z M 262.86 189.12 C 258.1 189.66 247.57 186.73 242.15 181.5 C 239.802 179.565 237.823 177.22 236.31 174.58 C 226.65 157.85 238.72 136.93 258.04 136.93 C 277.362 136.928 289.441 157.843 279.781 174.578 C 279.781 174.578 279.78 174.579 279.78 174.58 C 275.375 182.309 267.176 187.095 258.28 187.13 C 261.1 188.21 264.46 188.93 262.86 189.12 Z M 107.78 86.48 C 109.552 83.571 111.719 80.922 114.22 78.61 C 112.64 74.87 107.15 64.09 95.42 60.61 C 81.13 56.36 69.94 76.25 76.31 88.21 C 81.69 98.33 97.28 96.59 103.5 95.57 C 104.558 92.377 105.995 89.322 107.78 86.47 L 107.78 86.48 Z M 126.5 72.77 C 127.85 73.54 119.9 77.71 112.99 88.8 C 108.905 95.066 105.597 101.805 103.14 108.87 C 102.66 110.03 101.69 107.85 101.91 103.67 C 98.55 104.993 95.056 105.948 91.49 106.52 C 89.107 113.495 87.294 120.652 86.07 127.92 C 87.315 127.228 88.565 126.545 89.82 125.87 C 129.34 104.72 157.02 97.97 175.36 95.04 C 166.72 74.14 181.98 50.18 205.53 50.18 C 228.121 50.181 243.845 72.627 236.13 93.86 C 257.59 96.28 276.8 101.22 291.13 106.38 C 286.61 95.31 275.04 72.48 251.42 55.59 C 218.21 31.84 157.78 38.02 127.86 58.1 C 124.296 60.485 120.904 63.118 117.71 65.98 C 119.067 68.538 120.176 71.22 121.02 73.99 C 123.82 72.69 125.92 72.44 126.5 72.77 Z M 66.27 99.22 C 52.37 84.16 61.63 57.13 79.4 50.57 C 90.29 46.55 101.24 48.54 109.52 55.62 C 131.59 37.31 163.95 26.87 194.27 27.02 C 214.45 27.12 238.24 32.08 259.61 46.86 C 262.135 43.148 265.652 40.22 269.76 38.41 C 280.78 33.29 301.86 39.82 306.63 59.19 C 310.05 73.06 300.35 82.96 295.25 87.07 C 299.78 95.328 303.569 103.972 306.57 112.9 C 308.21 113.73 309.67 114.53 310.94 115.3 C 332.31 127.92 331.54 139.25 327.42 143.62 C 326.49 144.62 323.32 146.49 318.09 148.88 C 320.29 155.05 322.91 164.13 325.61 177.35 C 332.56 211.33 321.75 228.57 313 222.91 C 309.179 237.85 301.237 251.415 290.08 262.06 C 299.415 270.914 306.701 281.645 311.487 293.494 L 300.048 300.1 C 292.317 284.891 283.237 274.756 279.26 270.72 C 272.07 275.42 266.11 277.32 264.09 276.45 C 258.69 274.14 268.99 273.37 285.97 248.14 C 302.96 222.91 303.47 201.54 303.47 201.54 C 303.47 201.54 309.66 208.5 311.97 207.47 C 314.29 206.43 318.67 195.37 311.97 169.37 C 306.91 149.71 301.41 139.62 299.03 135.87 C 294.84 134.91 289.88 133.97 284.08 133.16 C 285.13 136 281.18 136.51 278.25 134.36 C 276.705 133.334 275.039 132.505 273.29 131.89 C 265.226 131.138 257.137 130.691 249.04 130.55 C 244.85 132.08 240.98 133.92 238.87 133.59 C 236.97 133.29 237.31 131.77 237.95 130.49 C 228.6 130.58 218.29 130.97 206.95 131.79 C 194.85 132.64 183.54 133.95 172.97 135.56 C 175.11 138.41 174.62 141.57 172.97 141.57 C 171.63 141.57 168.1 138.51 161.31 137.52 C 152.998 139.042 144.74 140.843 136.55 142.92 C 130.8 146.52 129.91 150.09 127.4 149.55 C 125.64 149.17 125.36 147.5 125.53 145.89 C 112.376 149.67 99.46 154.231 86.85 159.55 C 85.98 165.07 84.45 176.45 83.38 193.05 C 81.84 217.25 83.64 231.92 94.19 213.39 C 105.78 241.19 116.28 259.07 139.24 273.11 C 142.28 274.96 132.93 277.59 117.64 267.44 C 99.723 275.954 86.84 285.974 78.473 293.811 L 67.014 287.194 C 78.339 275.482 91.455 265.595 105.9 257.92 C 99.887 252.079 94.418 245.702 89.56 238.87 C 85.18 245.82 71.54 248.4 69.48 220.34 C 68.48 206.58 69.13 192.32 70.07 181.37 C 60.67 180.37 56.24 178.68 52.76 175.81 C 44.49 169.01 45.32 155.31 70.66 137.51 C 71.04 131.93 72.4 120.37 77.32 105.97 C 73.075 104.805 69.244 102.465 66.27 99.22 Z M 282.37 47.87 C 275.18 47.1 270.61 50.93 268.36 53.59 C 276.224 60.357 283.142 68.15 288.93 76.76 C 303.84 67.26 292.63 48.96 282.37 47.86 L 282.37 47.87 Z M 300.91 121.23 C 280.11 110.55 254.27 104.86 231.12 102.9 C 218.62 118.94 194.37 119.43 181.15 104.37 C 161.99 107.01 136.65 112.81 111.19 125.61 C 83.88 139.34 61.92 154.17 60.99 163.35 C 70.51 157.55 96.93 144.13 152.89 132.05 C 222.01 117.11 282.57 123.35 315.8 133.71 C 314.87 129.91 309.89 125.84 300.91 121.23 Z M 228.31 82.75 C 228.31 78.75 227.26 74.82 225.26 71.35 C 216.49 56.17 194.57 56.17 185.8 71.35 C 177.03 86.55 188 105.53 205.53 105.53 C 218.111 105.53 228.31 95.331 228.31 82.75 Z M 250.45 233.72 C 243.75 246.08 232.17 265.9 212.87 267.44 C 193.57 268.99 179.07 254.28 159.58 230.37 C 153.92 223.42 162.68 224.9 164.98 225.48 C 171.632 227.125 178.468 227.905 185.32 227.8 C 198.2 227.4 209.52 215.7 213.64 215.7 C 217.76 215.7 226.51 221.62 231.92 221.62 C 237.32 221.62 252.25 217.76 255.34 216.47 C 258.44 215.19 257.14 221.37 250.45 233.72 Z M 225.73 226.84 C 220.53 225.3 217.05 222.59 214.73 222.98 C 212.42 223.37 202.57 229.93 202.57 229.93 C 202.57 229.93 205.47 242.28 207.2 243.06 C 208.94 243.83 220.33 242.67 221.5 241.9 C 222.65 241.12 225.35 234.17 225.73 226.84 Z M 127.4 302.45 C 125.929 306.131 123.771 312.52 121.732 318.789 L 112.797 313.629 C 122.433 299.766 129.957 296.067 127.4 302.45 Z M 263.32 312.23 C 260.783 307.495 263.381 305.072 272.838 315.812 L 266.875 319.255 C 265.588 316.623 264.338 314.137 263.32 312.24 L 263.32 312.23 Z M 213.64 209.78 C 206.26 211.08 201.37 207.08 198.54 202.62 C 189.54 201.75 185.28 198.37 184.55 194.34 C 183.52 188.67 193.56 182.24 209.78 180.69 C 228.26 178.93 235.26 184.29 235.26 189.19 C 235.26 192.51 232.88 196.43 225.62 199.25 C 224.16 204.71 220.2 208.62 213.64 209.78 Z"/>
                    <path d="M 348.93 262.44 L 348.93 103.22 C 348.93 97.39 347.28 32.26 342.23 29.34 L 202.88 9.47 C 197.825 6.549 191.595 6.549 186.54 9.47 L 44.92 24.52 C 39.87 27.44 40.49 97.39 40.49 103.22 L 40.49 262.44 C 40.49 268.27 43.6 273.67 48.66 276.59 L 186.54 356.19 C 191.595 359.111 197.825 359.111 202.88 356.19 L 340.76 276.59 C 345.815 273.671 348.93 268.277 348.93 262.44 Z"
                          fill="none"/>
                </g>
            </svg>
		<?php endif; ?>

        <div class="title-section">
            <div class="title-view">
				<?php printf( __( 'Now editing %s', 'groundhogg' ), html()->e( 'span', [ 'class' => 'title' ], $funnel->get_title() ) ); ?>
            </div>
            <div class="title-edit hidden">
                <input class="title" placeholder="<?php echo __( 'Enter Funnel Name Here', 'groundhogg' ); ?>"
                       type="text"
                       name="funnel_title" size="30" value="<?php esc_attr_e( $funnel->get_title() ); ?>" id="title"
                       spellcheck="true" autocomplete="off">
            </div>
        </div>
        <div class="status-options">
            <div id="status">
				<?php echo Plugin::$instance->utils->html->toggle( [
					'name'    => 'funnel_status',
					'id'      => 'status-toggle',
					'class'   => 'big-toggle',
					'value'   => 'active',
					'checked' => $funnel->is_active(),
					'on'      => 'Active',
					'off'     => 'Inactive',
				] ); ?>
            </div>
            <div id="save">
				<?php
				echo html()->button( [
					'type'  => 'submit',
					'text'  => dashicon( 'yes' ) . html()->wrap( __( 'Save' ), 'span', [ 'class' => 'save-text' ] ),
					'name'  => 'update',
					'id'    => 'update',
					'class' => 'gh-button primary save-button medium icon',
					'value' => 'save',
				] );
				?>
            </div>
        </div>
        <div id="close">
			<?php

			echo html()->e( 'a', [
				'href'  => admin_page_url( 'gh_funnels' ),
				'id'    => 'close-button',
				'class' => 'gh-button secondary icon text medium'
			], dashicon( 'no' ) );

			?>
        </div>
    </div>
    <div id="funnel-builder">
        <div id="step-flow" class="sidebar">
            <div class="fixed-inside">
                <div id="step-sortable"
                     class="ui-sortable"><?php foreach ( $funnel->get_steps() as $step ): ?><?php $step->sortable_item(); ?><?php endforeach; ?></div>
                <div class="add-step-bottom-wrap">
                    <button class="gh-button secondary medium icon" type="button">
						<?php dashicon_e( 'plus-alt2' ); ?>
						<?php _e( 'Add Step' ) ?>
                    </button>
                </div>
            </div>
        </div>
        <div id="step-settings-container" class="postbox-container">
            <div class="sub-header">

                <div class="toolbar-buttons">

					<?php

					echo html()->e( 'button', [
						'class' => 'gh-button secondary text icon',
						'id'    => 'full-screen',
						'type'  => 'button',
					], dashicon( 'fullscreen-alt' ) . __( 'Full Screen', 'groundhogg' ) )

					?>

					<?php echo Plugin::$instance->utils->html->modal_link( array(
						'title'              => __( 'Settings', 'groundhogg' ),
						'text'               => dashicon( 'admin-generic' ) . __( 'Settings', 'groundhogg' ),
						'id'                 => 'settings',
						'class'              => 'no-padding settings settings-button gh-button secondary text icon',
						'source'             => admin_page_url( 'gh_funnels', [
							'action' => 'funnel_settings',
							'funnel' => $funnel_id
						] ),
						'height'             => 500,
						'width'              => 900,
						'footer_button_text' => __( 'Close' ),
					) ); ?>

					<?php echo Plugin::$instance->utils->html->modal_link( array(
						'title'              => __( 'Replacements', 'groundhogg' ),
						'text'               => dashicon( 'admin-users' ) . __( 'Replacements', 'groundhogg' ),
						'footer_button_text' => __( 'Insert' ),
						'id'                 => 'replacements',
						'class'              => 'no-padding replacements replacements-button gh-button secondary text icon',
						'source'             => 'footer-replacement-codes',
						'height'             => 900,
						'width'              => 700,
					) ); ?>
                    <a id="copy-share-link" href="#" class="gh-button secondary text icon"><span
                                title="<?php esc_attr_e( 'Copy share link', 'groundhogg' ) ?>"
                                class="dashicons dashicons-share"></span> <?php _e( 'Share', 'groundhogg' ); ?></a>
                    <input id="share-link" type="hidden" value="<?php echo esc_attr( $funnel->export_url() ); ?>">
                    <a href="<?php echo esc_url( $funnel->export_url() ); ?>"
                       class="gh-button secondary text icon"><span
                                title="<?php esc_attr_e( 'Export', 'groundhogg' ) ?>"
                                class="dashicons dashicons-download"></span> <?php _e( 'Export', 'groundhogg' ); ?></a>

                    <a target="_blank"
                       href="<?php echo admin_url( sprintf( 'admin.php?page=gh_reporting&tab=funnels&funnel=%s', $funnel_id ) ); ?>"
                       class="gh-button secondary text icon"><span
                                title="<?php esc_attr_e( 'Reports', 'groundhogg' ) ?>"
                                class="dashicons dashicons-chart-area"></span> <?php _e( 'Reports', 'groundhogg' ); ?>
                    </a>
                </div>
            </div>
            <div id="step-settings-inner">
				<?php Plugin::$instance->notices->print_notices(); ?>
                <div id="add-steps">
                    <div class="steps-select">
                        <div id="step-toggle" class="gh-button-group">
                            <button class="gh-button secondary change-step-type" type="button"
                                    data-group="benchmarks"><?php _e( 'Benchmarks' ) ?></button>
                            <button class="gh-button secondary change-step-type active" type="button"
                                    data-group="actions"><?php _e( 'Actions' ) ?></button>
                        </div>
                        <div id='benchmarks' class="hidden steps-grid">
							<?php
							$benchmarks = Plugin::$instance->step_manager->get_benchmarks();
							foreach ( $benchmarks as $benchmark ):

                                if ( $benchmark->is_legacy() && ! is_option_enabled( 'gh_show_legacy_steps' ) ){
                                    continue;
                                }

								?>
                                <div class="select-step">
                                <div id='<?php echo $benchmark->get_type(); ?>'
                                     title="<?php esc_attr_e( $benchmark->get_description() ); ?>"
                                     data-group="benchmark"
                                     class="wpgh-element ui-draggable">
                                    <div class="step-icon">
                                        <img src="<?php echo esc_url( $benchmark->get_icon() ); ?>">
                                    </div>
                                    <p><?php echo $benchmark->get_name() ?></p>
                                </div>
                                </div><?php

							endforeach;

							?>
                        </div>
                        <div id='actions' class="steps-grid">
							<?php
							$actions = Plugin::$instance->step_manager->get_actions();
							foreach ( $actions as $action ):

								if ( $action->is_legacy() && ! is_option_enabled( 'gh_show_legacy_steps' ) ){
									continue;
								}

								?>
                                <div class="select-step">
                                <div id='<?php echo $action->get_type(); ?>'
                                     data-group="action"
                                     title="<?php esc_attr_e( $action->get_description() ); ?>"
                                     class="wpgh-element ui-draggable">
                                    <div class="step-icon">
                                        <img src="<?php echo esc_url( $action->get_icon() ); ?>">
                                    </div>
                                    <p><?php echo $action->get_name() ?></p></div>
                                </div><?php
							endforeach;
							?>
                        </div>
                    </div>
                </div>
                <div class="step-settings hidden">
					<?php

					foreach ( $funnel->get_steps() as $step ):
						$step->html_v2();
					endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>
