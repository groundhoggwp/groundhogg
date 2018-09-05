<?php
/**
 * Add Email
 *
 * Allows the easy addition of emails from the admin menu.
 *
 * @package     groundhogg
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'wpgh_before_new_funnel' );

?>
<?php $active_tab = isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] : 'templates'; ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=gh_funnels&action=add&tab=templates" class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Funnel Templates', 'groundhogg'); ?></a>
    <a href="?page=gh_funnels&action=add&tab=import" class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Import Funnel', 'groundhogg'); ?></a>
</h2>
<!-- search form -->
<?php do_action('wpgh_add_new_funnel_form_before'); ?>

<?php if ( 'templates' === $active_tab ): ?>
    <form method="post" id="poststuff" >
        <?php wp_nonce_field(); ?>
        <?php include dirname(__FILE__) . '/../../templates/funnel-templates.php'; ?>
        <?php foreach ( $funnel_templates as $id => $funnel_args ): ?>
            <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                <h2 class="hndle"><?php echo $funnel_args['title']; ?></h2>
                <div class="inside">
                    <p><?php echo $funnel_args['description']; ?></p>
                    <div class="postbox">
                        <img src="<?php echo $funnel_args['src']; ?>" width="100%">
                    </div>
                    <button class="button-primary" name="funnel_template" value="<?php echo $id ?>"><?php _e('Start Building', 'groundhogg'); ?></button>
                </div>
            </div>

        <?php endforeach; ?>
    </form>
<?php else: ?>
    <div class="show-upload-view">
        <div class="upload-pluing-wrap">
            <div class="upload-plugin">
                <p class="install-help"><?php _e( 'If you have a funnel import file (ends in .funnel) you can upload it here!', 'groundhogg' ); ?></p>
                <form method="post" enctype="multipart/form-data" class="wp-upload-form">
                    <?php wp_nonce_field(); ?>

                    <input type="file" name="funnel_template" id="funnel_template" accept=".funnel">

                    <button class="button-primary" name="funnel_inport" value="import"><?php _e('Import Funnels', 'groundhogg'); ?></button>

                </form>
            </div>
        </div>
    </div>
<?php endif;?>
<?php do_action('wpgh_add_new_funnel_form_after'); ?>
<?php


