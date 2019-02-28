<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-02-27
 * Time: 11:03 AM
 */

class WPGH_Guided_Setup_Step_Finished extends WPGH_Guided_Setup_Step
{

    public function get_title()
    {
        return _x( 'Finished', 'guided_setup', 'groundhogg' );
    }

    public function get_slug()
    {
        return 'finished';
    }

    public function get_description()
    {
        return _x( 'Congratulations! Groundhogg has been successfully setup. Here are your next steps...', 'guided_setup', 'groundhogg' );
    }

    public function get_settings()
    {
        ob_start();

        wpgh_update_option( 'gh_guided_setup_finished', 1 );

        if ( wpgh_is_option_enabled( 'gh_opted_in_stats_collection' ) ):

        ?>
        <h3><?php _e( 'Next Steps...', 'Groundhogg' ); ?></h3>
        <div class="postbox" style="padding: 0 10px 0 20px">
            <h3><?php _e( 'Get 30% Off When You Help Us Make Groundhogg Better', 'Groundhogg' ); ?></h3>
            <p>
                <a class="button button-primary" href="<?php echo wp_nonce_url( $_SERVER[ 'REQUEST_URI' ] . '&action=opt_in_to_stats' , 'opt_in_to_stats' ); ?>" ><?php _e( 'Yes, I want to help make Groundhogg better!' ); ?></a>
                <a href="https://www.groundhogg.io/privacy-policy/#usage-tracking" target="_blank"><?php _e( 'Learn more', 'groundhogg' ); ?></a>
            </p>
            <p><?php _e( "Want sweet discounts and to help us make Groundhogg even better? When you optin to our stats collection you will get a <b>30% discount off</b> any premium extension or subscription in our store by sharing <b>anonymous</b> data about your site. You can opt out any time from the settings page. Your email address & display name will be collected so we can send you the discount code.", 'groundhogg' ); ?></p>
        </div>
        <?php
        endif;
        ?>
        <div class="postbox" style="padding: 0 10px 0 20px">
            <h3><?php _e( 'Write your first Email!', 'Groundhogg' ); ?></h3>
            <p><?php _e( "Let's get you going with something easy, like writing your first email!", 'groundhogg' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=gh_emails&action=add' ) ?>" ><?php _e( 'Write my first Email!' ); ?></a>
            </p>
        </div>
        <div class="postbox" style="padding: 0 10px 0 20px">
            <h3><?php _e( 'Create your first Funnel!', 'Groundhogg' ); ?></h3>
            <p><?php _e( "Let's jump in and create your first marketing funnel!", 'groundhogg' ); ?></p>
            <p>
                <a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=add' ) ?>" ><?php _e( 'Create my first Funnel!' ); ?></a>
            </p>
        </div>
        <?php

        return ob_get_clean();
    }

    public function save()
    {
        // TODO: Implement save() method.
    }

}