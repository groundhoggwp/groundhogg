<?php

use function Groundhogg\action_url;
use function Groundhogg\get_url_var;
use function Groundhogg\gh_cron_installed;

switch ( get_url_var( 'step' ) ):
	default:

		break;


endswitch;




?>

<h3><?php _e( 'Advanced Cron Setup', 'groundhogg' ); ?></h3>
<p><?php _e( 'As your business scales up and you start sending more emails and you get more contacts, you might need to optimize your Groundhogg installation for best performance.', 'groundhogg' ); ?></p>
<p><?php _e( 'There are performance savings to be had by bypassing the WP Cron system and accessing the Groundhogg Event Queue directly via an external cron job.', 'groundhogg' ); ?></p>
<h4><?php _e( '1. Install <code>gh-cron.php</code>', 'groundhogg' ); ?></h4>
<p><?php _e( 'This file can be installed automatically.', 'groundhogg' ); ?></p>
<p>
	<?php if ( ! gh_cron_installed() ): ?>
        <a class="button button-secondary" href="<?php echo esc_url( action_url( 'install_gh_cron' ) ); ?>">
			<?php _e( 'Install Automatically!', 'groundhogg' ); ?>
        </a>
	<?php else: ?>
        <span style="color: green"><?php _e( "<code>gh-cron.php</code> is installed!", 'groundhogg' ); ?></span>
        <a class="button button-secondary" href="<?php echo esc_url( action_url( 'uninstall_gh_cron' ) ); ?>">
			<?php _e( 'Uninstall', 'groundhogg' ); ?>
        </a>
	<?php endif; ?>
</p>
<p><?php _e( 'If automatic installation does not work, install it manually.', 'groundhogg' ); ?></p>
<ol>
    <li>
        <a href="<?php echo esc_url( action_url( 'install_gh_cron_manually' ) ); ?>"><?php _e( 'Download the <code>gh-cron.txt</code> file.', 'groundhogg' ) ?></a>
    </li>
    <li><?php _e( 'Upload it to the root directory of WordPress. This is the same folder as your <code>wp-config.php</code> file.', 'groundhogg' ); ?></li>
    <li><?php _e( 'Change the file extension from <code>.txt</code> to <code>.php</code>', 'groundhogg' ); ?></li>
</ol>
<h4><?php _e( '2. Create an external cron job for the Groundhogg Event Queue.', 'groundhogg' ); ?></h4>
<p><?php _e( 'You must create an external CRON JOB which makes a request to the cron file you just created.', 'groundhogg' ); ?></p>
<p><?php _e( 'This new cron job should execute <code>every 1 minute</code> to the url below.', 'groundhogg' ); ?></p>
<p><input type="text" class="code regular-text" onfocus="this.select()"
          value="<?php esc_attr_e( home_url( 'gh-cron.php' ) ); ?>" readonly>
</p>
<ol type="a" style="list-style-type: lower-alpha">
    <li>
        <a href="https://help.groundhogg.io/article/49-add-an-external-cron-job-cron-job-org"><?php _e( 'Create a cron job using <b>cron-job.org</b>.', 'groundhogg' ) ?></a>
    </li>
    <li>
        <a href="https://help.groundhogg.io/article/51-add-an-external-cron-job-cpanel"><?php _e( 'Create a cron job using <b>cPanel</b>.', 'groundhogg' ) ?></a>
    </li>
</ol>
<h4><?php _e( '3. Unhook the Groundhogg Event Queue from WP Cron', 'groundhogg' ); ?></h4>
<p><?php _e( 'Now that the Groundhogg Event Queue has its own external cron job, you do not need to have it processed with other WordPress scheduled tasks.', 'groundhogg' ); ?></p>
<p>
    <a class="button button-secondary" href="<?php echo esc_url( action_url( 'unschedule_gh_cron' ) ); ?>">
		<?php _e( 'Unhook from WP Cron', 'groundhogg' ); ?>
    </a>
	<?php if ( ! wp_next_scheduled( 'groundhogg_process_queue' ) ): ?>
        <span style="color: green"><?php _e( "The event queue is currently unhooked!", 'groundhogg' ); ?></span>
	<?php endif; ?>
</p>
<p><b><?php _e( 'All done!', 'groundhogg' ); ?></b></p>
