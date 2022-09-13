<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use function Groundhogg\action_url;
use function Groundhogg\get_url_var;
use function Groundhogg\get_request_var;
use Groundhogg\Plugin;

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
    <h2 class="nav-tab-wrapper">
        <a id="funnel-templates" href="?page=gh_funnels&action=add&tab=templates"
           class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Funnel Templates', 'add_funnel_tab', 'groundhogg' ); ?></a>
        <a id="funnel-import" href="?page=gh_funnels&action=add&tab=import"
           class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>"><?php _ex( 'Import Funnel', 'add_funnel_tab', 'groundhogg' ); ?></a>
        <a class="gh-button secondary small alignright" href="<?php echo action_url( 'start_from_scratch' ) ?>"><?php _e( 'Start from scratch' ) ?></a>
    </h2>

	<?php if ( 'templates' === $active_tab ): ?>

	<?php

	$funnel_templates = Plugin::$instance->library->get_funnel_templates();

    $funnel_templates = array_map( function ( $template ) {
		return new Funnel( $template, true );
	}, $funnel_templates );

    // Filter out templates that have steps that are not registered on this site
    $funnel_templates = array_filter( $funnel_templates, function ( $funnel ){

        foreach ( $funnel->steps as $step ){
            $step = (object) $step;
            if ( ! Plugin::instance()->step_manager->type_is_registered( $step->data->step_type ) ){
                return false;
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
                    <div class="gh-panel">
                        <div class="gh-panel-header">
                            <h2 ><?php echo $funnel->get_title(); ?></h2>
                        </div>
                        <div class="inside">
                            <p><?php echo $funnel->get_meta( 'description' ); ?></p>
                            <button class="gh-button primary" name="funnel_template"
                                    value="<?php echo $funnel->ID ?>"><?php _ex( 'Use Template', 'action', 'groundhogg' ); ?></button>
                        </div>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
    </form>

<?php else: ?>
    <div class="gh-tools-wrap">
        <p class="tools-help"><?php _e( 'If you have a funnel import file (ends in .funnel) you can upload it here!', 'groundhogg' ); ?></p>
        <form method="post" enctype="multipart/form-data" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <p class="description"><?php _e( 'Upload a .funnel export file.', 'groundhogg' ); ?></p>
            <hr/>
            <input type="file" name="funnel_template" id="funnel_template" accept=".funnel">
            <button style="float: right" class="gh-button primary" name="funnel_import"
                    value="import"><?php _ex( 'Import Funnel', 'action', 'groundhogg' ); ?></button>
            <div class="wp-clearfix"></div>
        </form>
        <form method="post" class="gh-tools-box gh-panel">
			<?php wp_nonce_field(); ?>
            <p class="description"><?php _e( 'Copy and paste JSON from a .funnel export file if you are having issue uploading.', 'groundhogg' ); ?></p>
            <hr/>
            <textarea style="width: 100%;margin-bottom: 5px;" rows="3" name="funnel_json" id="funnel_json"
                      placeholder="<?php esc_attr_e( 'Paste JSON from .funnel file.', 'groundhogg' ); ?>"></textarea>
            <button style="float: right" class="gh-button primary" name="funnel_import"
                    value="import"><?php _ex( 'Import Funnel', 'action', 'groundhogg' ); ?></button>
            <div class="wp-clearfix"></div>
        </form>
    </div>
<?php endif;

