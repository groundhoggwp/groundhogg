<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\header_icon;
use function Groundhogg\html;
use function Groundhogg\is_option_enabled;

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

			?>
        <div class="select-step visible" data-id="<?php esc_attr_e( $step->get_type() ); ?>" data-name="<?php esc_attr_e( $step->get_name() ); ?>">
            <div id='<?php echo $step->get_type(); ?>'
                 data-group="<?php echo $step->get_group(); ?>"
                 title="<?php esc_attr_e( $step->get_description() ); ?>"
                 class="wpgh-element ui-draggable">
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

// get all steps an initialize the merged changes
$funnel_editor_steps = $funnel->get_steps_for_editor();

// validate the settings so errors appear
foreach ( $funnel_editor_steps as $step ) {
	$step->get_step_element()->validate_settings( $step );
}

// filter by the main branch
$main_branch_steps = array_filter( $funnel_editor_steps, function ( $step ) {
    return $step->is_main_branch();
} );

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

        <div class="actions">
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
                <span class="button-text">Update</span>
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
                ><?php foreach ( $main_branch_steps as $step ):$step->sortable_item();endforeach; ?></div>
            </div>
            <button class="add-step-button-flow" type="button" id="add-new-step">
				<?php dashicon_e( 'plus-alt2' ); ?>
                <div class="gh-tooltip left">Add a step</div>
            </button>
        </div>
        <div id="step-settings-container" class="slide-out">
            <button id="collapse-settings"><?php dashicon_e( 'arrow-right-alt2' ); ?></button>
            <div id="step-settings-inner">
                <div id="add-steps">
                    <div class="steps-select">
                        <input id="step-search" name="step-search" type="search" placeholder="Search for a step..."/>
                        <div class="steps-grid">
	                        <?php
	                        render_draggable_step_grid( Plugin::instance()->step_manager->get_logic() );

	                        render_draggable_step_grid( Plugin::instance()->step_manager->get_benchmarks() );

	                        render_draggable_step_grid( Plugin::instance()->step_manager->get_actions() );
	                        ?>
                        </div>
                    </div>
                </div>
                <div class="step-settings">
				    <?php foreach ( $main_branch_steps as $step ):
					    $step->html_v2();
				    endforeach; ?>
                </div>
                <div id="funnel-health-check">

                </div>
            </div>
        </div>
    </div>
</form>
