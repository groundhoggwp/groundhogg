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

do_action( 'wpfn_before_new_funnel' );

?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Add New Funnel', 'groundhogg');?></h1>
        <hr class="wp-header-end">
        <form method="post" id="poststuff" >
            <!-- search form -->
            <?php do_action('wpfn_add_new_funnel_form_before'); ?>
            <?php wp_nonce_field( 'add_new_funnel', 'add_new_funnel_nonce' ); ?>

            <p><?php _e('Select an funnel template below or an existing email to copy.', 'groundhogg' ); ?></p>

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

            <?php do_action('wpfn_add_new_funnel_form_after'); ?>
        </form>
    </div>
<?php


