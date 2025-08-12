<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\force_custom_step_names;
use function Groundhogg\get_request_var;
use function Groundhogg\header_icon;
use function Groundhogg\html;
use function Groundhogg\is_option_enabled;
use function Groundhogg\is_pro_features_active;

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

if ( ! $funnel->exists() ) {

	?>
    <p>This flow does not exist. It may have been deleted.</p>
	<?php

	return;
}

/**
 * @param $steps Funnel_Step[]
 *
 * @return void
 */
function render_draggable_step_grid( $steps, $groups = true ) {

	$sub_groups = Plugin::instance()->step_manager->sub_groups;

	foreach ( $sub_groups as $sub_group_id => $name ) {

		$_steps = array_filter( $steps, function ( $step ) use ( $sub_group_id ) {
			return $step->get_sub_group() === $sub_group_id;
		} );

		if ( empty( $_steps ) ) {
			continue;
		}

		if ( $groups ):
			?>
            <div class="sub-group">
            <span class="sub-group-label">
			<?php echo esc_html( $name ); ?>
            </span><?php
		endif;

		foreach ( $_steps as $step ):

			if ( $step->is_legacy() && ! is_option_enabled( 'gh_show_legacy_steps' ) ) {
				continue;
			}

			$classes = [
				'step-element step-draggable'
			];

			if ( $step->is_premium() && ! is_pro_features_active() ) {
				$classes[] = 'premium';
			}

			$keywords = [
				$step->get_name(),
				$name,
			];

			?>
        <div class="select-step visible" data-id="<?php echo esc_attr( $step->get_type() ); ?>" data-keywords="<?php echo esc_attr( implode( ',', $keywords ) ); ?>">
            <div class="gh-tooltip top"><?php echo esc_html( $step->get_description() ); ?></div>
            <div id='<?php echo esc_attr( $step->get_type() ); ?>'
                 data-type="<?php echo esc_attr( $step->get_type() ); ?>"
                 data-name="<?php echo esc_attr( $step->get_name() ); ?>"
                 data-group="<?php echo esc_attr( $step->get_group() ); ?>"
                 class="<?php echo esc_attr( implode( ' ', $classes ) ) ?>">
                <div class="step-icon">
	                <?php if ( $step->icon_is_svg() ):
		                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- this is an SVG
		                echo $step->get_icon_svg();
	                else: ?>
                        <img src="<?php echo esc_url( $step->get_icon() ); ?>" alt="<?php echo esc_attr( $step->get_name() ); ?>">
					<?php endif; ?>
                </div>
                <p><?php echo esc_html( $step->get_name() ) ?></p></div>
            </div><?php
		endforeach;

		if ( $groups ):
			?></div><?php
		endif;
	}
}

?>
<form method="post" id="funnel-form" class="gh-fixed-ui" data-status="<?php echo esc_attr( $funnel->get_status() ) ?>">
	<?php wp_nonce_field();
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo html()->input( [ 'type' => 'hidden', 'name' => 'funnel', 'id' => 'funnel', 'value' => $funnel_id ] ); ?>
    <div class="gh-header funnel-editor-header">
		<?php header_icon(); ?>
        <div class="title-section">
            <div class="title-view"><?php
	            /* translators: %s: the funnel title */
	            esc_html_e( 'Now editing', 'groundhogg' );
                ?>&nbsp;<span class="title"><?php echo esc_html( $funnel->get_title() ) ?></span></div>
            <div class="title-edit hidden">
                <input class="title" placeholder="<?php esc_attr_e( 'Enter Funnel Name Here', 'groundhogg' ); ?>"
                       type="text"
                       name="funnel_title" size="30" value="<?php echo esc_attr( $funnel->get_title() ); ?>" id="title"
                       spellcheck="true" autocomplete="off">
            </div>
        </div>
        <div class="last-saved">
            <span class="is-saving loading-dots"><?php esc_html_e( 'Saving', 'groundhogg' ); ?></span>
            <span id="last-saved-text"></span>
        </div>
        <div class="actions">
            <div id="undo-and-redo"></div>
			<?php

			html( 'button', [
				'class' => 'gh-button secondary text icon',
				'id'    => 'funnel-simulate',
				'type'  => 'button',
			], [
				dashicon( 'controls-play' ),
				html()->e( 'div', [
					'class' => 'gh-tooltip bottom'
				], esc_html__( 'Simulate', 'groundhogg' ) )
			] );

			html( 'button', [
				'class' => 'gh-button secondary text icon',
				'id'    => 'funnel-settings',
				'type'  => 'button',
			], [
				dashicon( 'admin-generic' ),
				html()->e( 'div', [
					'class' => 'gh-tooltip bottom'
				], esc_html__( 'Settings', 'groundhogg' ) )
			] );

            html( 'button', [
	            'type'  => 'button',
	            'class' => 'gh-button danger text',
	            'id'    => 'funnel-deactivate',
            ], esc_html__( 'Deactivate', 'groundhogg' ) );

			html( 'button', [
				'type'     => 'button',
				'class'    => 'gh-button primary',
				'disabled' => ! $funnel->has_changes(),
				'id'       => 'funnel-update',
			], [
				html()->e( 'span', [ 'class' => 'button-text' ], esc_html__( 'Publish Changes', 'groundhogg' ) ),
				html()->e( 'span', [ 'class' => 'gh-spinner' ] )
            ] );

			html( 'button', [
				'type'  => 'button',
				'class' => 'gh-button action',
				'id'    => 'funnel-activate',
			], [
				html()->e( 'span', [ 'class' => 'button-text' ], 'Activate' ),
				html()->e( 'span', [ 'class' => 'gh-spinner' ] )
			] );

			?>
        </div>
        <div id="close">
			<?php

			html( 'a', [
				'href'  => admin_page_url( 'gh_funnels' ),
				'id'    => 'close-button',
				'class' => 'gh-button secondary icon text medium'
			], dashicon( 'no-alt' ) );

			?>
        </div>
    </div>
    <div id="funnel-builder">
        <div id="step-flow">
            <div class="fixed-inside">
                <div id="step-sortable"
                     class="step-branch"
                     data-branch="main"
                ><?php $funnel->step_flow(); ?></div>
            </div>
        </div>
        <div id="step-settings-container" class="slide-out">
            <button id="collapse-settings">
				<?php dashicon_e( 'arrow-right-alt2' ); ?>
				<?php dashicon_e( 'arrow-left-alt2' ); ?>
            </button>
            <div id="step-settings-inner" data-view="settings">
                <div id="add-steps">
                    <div class="steps-select">
                        <div class="display-flex gap-10 stretch space-below-10">
                            <div class="gh-input-group full-width" style="background-color: #fff;">
                                <button class="gh-button step-filter full-width" data-group="benchmark"><?php esc_html_e( 'Triggers', 'groundhogg' ); ?></button>
                                <button class="gh-button step-filter full-width" data-group="action"><?php esc_html_e( 'Actions', 'groundhogg' ); ?></button>
                                <button class="gh-button step-filter full-width" data-group="logic"><?php esc_html_e( 'Logic', 'groundhogg' ); ?></button>
                                <button class="gh-button step-filter full-width current" data-group="all"><?php esc_html_e( 'All', 'groundhogg' ); ?></button>
                            </div>
                            <div class="step-search-wrap">
                                <input id="step-search" name="step-search" type="search" placeholder="<?php esc_attr_e( 'Search for a step...', 'groundhogg' ); ?>"/>
                            </div>
                        </div>
                        <div class="steps-grid">
							<?php

							render_draggable_step_grid( Plugin::instance()->step_manager->get_benchmarks() );

							render_draggable_step_grid( Plugin::instance()->step_manager->get_actions() );

							render_draggable_step_grid( Plugin::instance()->step_manager->get_logic() );
							?>
                        </div>
                    </div>
                </div>
                <div class="step-settings <?php echo force_custom_step_names() ? 'custom-step-names' : 'generated-step-names' ?>">
					<?php $funnel->step_settings() ?>
                </div>
                <div id="flow-simulator">
                    Simulator here!
                </div>
                <div id="flow-settings">
                    Settings here!
                </div>
            </div>
        </div>
    </div>
</form>
