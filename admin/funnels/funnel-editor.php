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
    <p>This funnel does not exist. It may have been deleted.</p>
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
			<?php _e( $name ) ?>
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
        <div class="select-step visible" data-id="<?php esc_attr_e( $step->get_type() ); ?>" data-keywords="<?php esc_attr_e( implode( ',', $keywords ) ); ?>">
            <div class="gh-tooltip top"><?php echo $step->get_description(); ?></div>
            <div id='<?php echo $step->get_type(); ?>'
                 data-type="<?php esc_attr_e( $step->get_type() ); ?>"
                 data-name="<?php esc_attr_e( $step->get_name() ); ?>"
                 data-group="<?php esc_attr_e( $step->get_group() ); ?>"
                 class="<?php echo implode( ' ', $classes ) ?>">
                <div class="step-icon">
					<?php if ( $step->icon_is_svg() ): ?>
						<?php echo $step->get_icon_svg(); ?>
					<?php else: ?>
                        <img src="<?php echo esc_url( $step->get_icon() ); ?>">
					<?php endif; ?>
                </div>
                <p><?php echo $step->get_name() ?></p></div>
            </div><?php
		endforeach;

		if ( $groups ):
			?></div><?php
		endif;
	}
}

?>
<form method="post" id="funnel-form" class="gh-fixed-ui" data-status="<?php _e( $funnel->get_status() ) ?>">
	<?php wp_nonce_field(); ?>
	<?php $args = array(
		'type'  => 'hidden',
		'name'  => 'funnel',
		'id'    => 'funnel',
		'value' => $funnel_id
	);
	echo html()->input( $args ); ?>
    <div class="gh-header funnel-editor-header">

		<?php header_icon(); ?>

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
        <div class="last-saved">
            <span class="is-saving loading-dots">Saving</span>
            <span id="last-saved-text"></span>
        </div>
        <div class="actions">
            <div id="undo-and-redo"></div>
			<?php

			echo html()->e( 'button', [
				'class' => 'gh-button secondary text icon',
				'id'    => 'full-screen',
				'type'  => 'button',
			], dashicon( 'fullscreen-alt' ) );

			echo html()->modal_link( array(
				'title'              => __( 'Replacements', 'groundhogg' ),
				'text'               => dashicon( 'admin-users' ),
				'footer_button_text' => __( 'Insert' ),
				'id'                 => 'replacements',
				'class'              => 'no-padding replacements replacements-button gh-button secondary text icon',
				'source'             => 'footer-replacement-codes',
				'height'             => 900,
				'width'              => 700,
			) );

			echo html()->e( 'button', [
				'class' => 'gh-button secondary text icon',
				'id'    => 'funnel-settings',
				'type'  => 'button',
			], dashicon( 'admin-settings' ) ); ?>
            <button type="button" id="funnel-deactivate" class="gh-button danger text">Deactivate</button>
            <button type="button" id="funnel-update" class="gh-button primary">
                <span class="button-text">Publish Changes</span>
                <span class="gh-spinner"></span>
            </button>
            <button type="button" id="funnel-activate" class="gh-button action">
                <span class="button-text">Activate</span>
                <span class="gh-spinner"></span>
            </button>
        </div>
        <div id="close">
			<?php

			echo html()->e( 'a', [
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
            <div id="step-settings-inner">
                <div id="add-steps">
                    <div class="steps-select">
                        <div class="display-flex gap-10 stretch space-below-10">
                            <div class="gh-input-group full-width" style="background-color: #fff;">
                                <button class="gh-button step-filter full-width" data-group="benchmark">Benchmarks</button>
                                <button class="gh-button step-filter full-width" data-group="action">Actions</button>
                                <button class="gh-button step-filter full-width" data-group="logic">Logic</button>
                                <button class="gh-button step-filter full-width current" data-group="all">All</button>
                            </div>
                            <div class="step-search-wrap">
                                <input id="step-search" name="step-search" type="search" placeholder="Search for a step..."/>
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
            </div>
        </div>
    </div>
</form>
