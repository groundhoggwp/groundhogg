<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use Groundhogg\Steps\Funnel_Step;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\groundhogg_icon;
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
function render_draggable_step_grid( $steps ) {

	$sub_groups = Plugin::instance()->step_manager->sub_groups;

	foreach ( $sub_groups as $sub_group_id => $name ) {

		$_steps = array_filter( $steps, function ( $step ) use ( $sub_group_id ) {
			return $step->get_sub_group() === $sub_group_id;
		} );

		if ( empty( $_steps ) ) {
			continue;
		}

		?>
        <div class="sub-group">
        <span class="sub-group-label">
		<?php _e( $name ) ?>
        </span><?php

		foreach ( $_steps as $step ):

			if ( $step->is_legacy() && ! is_option_enabled( 'gh_show_legacy_steps' ) ) {
				continue;
			}

			?>
            <div class="select-step">
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

		?></div><?php

	}

}

?>
<form method="post" id="funnel-form" class="gh-fixed-ui">
	<?php wp_nonce_field(); ?>
	<?php $args = array(
		'type'  => 'hidden',
		'name'  => 'funnel',
		'id'    => 'funnel',
		'value' => $funnel_id
	);
	echo Plugin::$instance->utils->html->input( $args ); ?>
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
			], dashicon( 'admin-settings' ) );

			echo html()->bigToggle( [
				'name'    => 'funnel_status',
				'id'      => 'status-toggle',
//					'class'   => 'big-toggle',
				'value'   => 'active',
				'checked' => $funnel->is_active(),
				'on'      => 'Active',
				'off'     => 'Inactive',
			] );
			echo html()->button( [
				'type'  => 'submit',
				'text'  => html()->wrap( __( 'Update' ), 'span', [ 'class' => 'save-text' ] ),
				'name'  => 'update',
				'id'    => 'update',
				'class' => 'gh-button primary save-button',
				'value' => 'save',
			] );
			?>
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
            <div id="step-settings-inner">
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
							render_draggable_step_grid( Plugin::instance()->step_manager->get_benchmarks() );
							?>
                        </div>
                        <div id='actions' class="steps-grid">
							<?php
							render_draggable_step_grid( Plugin::instance()->step_manager->get_actions() );
							?>
                        </div>
                    </div>
                </div>
                <div class="step-settings hidden">
					<?php foreach ( $funnel->get_steps() as $step ):
						$step->html_v2();
					endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</form>
