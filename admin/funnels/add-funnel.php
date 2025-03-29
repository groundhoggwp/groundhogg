<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\get_url_var;
use function Groundhogg\is_pro_features_active;
use function Groundhogg\is_white_labeled;

/**
 * Add Funnel
 *
 * Similar to the Email add page, this allows one to select a funnel from some pre-installed defaults.
 * Or upload their own funnel if they purchased one from us or another provider
 *
 * @since       File available since Release 0.1
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Admin
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

do_action( 'wpgh_before_new_funnel' );

if ( get_url_var( 'flush' ) ) {
	Plugin::$instance->library->flush();
}

?>
	<?php $active_tab = sanitize_key( get_request_var( 'tab', 'templates' ) ); ?>
    <h2 class="nav-tab-wrapper gh-nav">
        <a id="funnel-templates" href="?page=gh_funnels&action=add&tab=templates"
           class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Flow Templates', 'add_funnel_tab', 'groundhogg' ); ?></a>
        <a id="funnel-import" href="?page=gh_funnels&action=add&tab=import"
           class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Import Flow', 'add_funnel_tab', 'groundhogg' ); ?></a>
    </h2>
    <script>
      (() => {
        const pageHeader = document.getElementById( '<?php esc_attr_e( $this->get_slug() . '-header' ) ?>' )
        const navTabs = document.querySelector('h2.gh-nav')

        if (pageHeader) {
          pageHeader.insertAdjacentElement('afterend', navTabs )
        }
      })()
    </script>

	<?php if ( 'templates' === $active_tab ): ?>

	<?php

	$funnel_templates = Plugin::$instance->library->get_funnel_templates();

	if ( ! is_array( $funnel_templates ) ) {
		$funnel_templates = [];
	}

	$funnel_templates = array_map( function ( $template ) {
		return new Funnel( $template, true );
	}, $funnel_templates );

	// Filter out templates that have steps that are not registered on this site
	$funnel_templates = array_filter( $funnel_templates, function ( $funnel ) {

        $funnel->is_premium = false;

		foreach ( $funnel->steps as $step ) {
			$step = (object) $step;

            // don't show funnels with unregistered types
			if ( ! Plugin::instance()->step_manager->type_is_registered( $step->data->step_type ) ) {
				return false;
			}

			if ( Plugin::instance()->step_manager->get_element( $step->data->step_type )->is_premium() && ! is_pro_features_active() && ! is_white_labeled() ) {
				$funnel->is_premium = true;
			}
		}

		return true;
	} )

	?>

    <form method="post">
		<?php wp_nonce_field( 'add' ); ?>
        <div id="">
            <p></p>
            <div class="post-box-grid">
				<?php
				foreach ( $funnel_templates as $funnel ): ?>
                    <div class="gh-panel funnel-template <?php echo $funnel->is_premium ? 'premium' : '' ?> display-flex column">
                        <div class="gh-panel-header">
                            <h2><?php echo $funnel->get_title(); ?></h2>
                            <?php if ( $funnel->is_premium ): ?>
                            <div style="padding: 5px">
                                <span class="pill dark">PRO
                                    <div class="gh-tooltip top">This funnel contains paid features.</div>
                                </span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="inside display-flex column align-top" style="flex-grow: 1">
                            <div class="funnel-preview" style="width: 100%"><?php

                                $allSteps = $funnel->steps;
                                $steps = array_splice( $allSteps, 0, 15 );

                                foreach ( $steps as $step ) {

                                    // if we're here, the step type is registered
	                                $step_type = Plugin::instance()->step_manager->get_element( $step->data->step_type );

	                                ?>
                                    <div class="step-icon <?php echo $step_type->get_type() ?> <?php echo $step_type->get_group() ?>">
		                                <?php if ( $step_type->icon_is_svg() ): ?>
			                                <?php echo $step_type->get_icon_svg(); ?>
		                                <?php else: ?>
                                            <img src="<?php echo esc_url( $step_type->get_icon() ); ?>">
		                                <?php endif; ?>
                                        <div class="gh-tooltip top">
			                                <?php _e( $step->data->step_title ) ?>
                                        </div>
                                    </div>
	                                <?php

                                }

                                if ( ! empty( $allSteps ) ) {
	                                ?>
                                    <div class="step-icon more">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 16 16">
                                            <path fill="#000" d="M4 8a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm6 0a2 2 0 1 1-4 0 2 2 0 0 1 4 0Zm4 2a2 2 0 1 0 0-4 2 2 0 0 0 0 4Z"/>
                                        </svg>
                                        <div class="gh-tooltip top">
			                                <?php printf( _n( '%d more step...', '%d more steps',  count( $allSteps ), 'groundhogg' ), count( $allSteps ) ) ?>
                                        </div>
                                    </div>
	                                <?php
                                }

                                ?>

                            </div>
                            <p><?php echo $funnel->get_meta( 'description' ); ?></p>
                            <?php if ( $funnel->is_premium ): ?>
                                <a style="margin-top: auto" class="gh-button primary text" href="https://groundhogg.io/pricing/" target="_blank"><?php _ex( '🔓 Upgrade to unlock!', 'action', 'groundhogg' ); ?></a>
                            <?php else: ?>
                                <button style="margin-top: auto" class="gh-button primary" name="funnel_template"
                                        value="<?php echo $funnel->ID ?>"><?php _ex( 'Use Template', 'action', 'groundhogg' ); ?></button>
                            <?php endif; ?>
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
    </form>

<?php else: ?>
    <div class="gh-tools-wrap">
        <p class="tools-help"><?php _e( 'If you have a flow import file (ends in .funnel) you can upload it here!', 'groundhogg' ); ?></p>
        <form method="post" enctype="multipart/form-data" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <p class="description"><?php _e( 'Upload a .funnel export file.', 'groundhogg' ); ?></p>
            <hr/>
            <input type="file" name="funnel_template" id="funnel_template" accept=".funnel,.json">
            <button style="float: right" class="gh-button primary" name="funnel_import"
                    value="import"><?php _ex( 'Import Flow', 'action', 'groundhogg' ); ?></button>
            <div class="wp-clearfix"></div>
        </form>
        <form method="post" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <p class="description"><?php _e( 'Copy and paste JSON from a .funnel export file if you are having issue uploading.', 'groundhogg' ); ?></p>
            <hr/>
            <textarea style="width: 100%;margin-bottom: 5px;" rows="3" name="funnel_json" id="funnel_json"
                      placeholder="<?php esc_attr_e( 'Paste JSON from .funnel file.', 'groundhogg' ); ?>"></textarea>
            <button style="float: right" class="gh-button primary" name="funnel_import"
                    value="import"><?php _ex( 'Import Flow', 'action', 'groundhogg' ); ?></button>
            <div class="wp-clearfix"></div>
        </form>
    </div>
<?php endif;

