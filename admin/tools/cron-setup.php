<?php

namespace Groundhogg\Admin\Tools\Cron;

use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\get_url_var;
use function Groundhogg\gh_cron_installed;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

function create_cron_job_instructions() {

	$host = groundhogg_get_host();

	switch ( $host ):
		case 'SiteGround':
			?>
            <p><?php _e( 'Not sure how to create an external cron job?', 'groundhogg' ); ?></p>
            <ul style="list-style-type: disc; padding-left: 20px">
                <li>
                    <a target="_blank"
                       href="https://help.groundhogg.io/article/469-setting-up-a-cron-job-on-siteground"><?php _e( 'Create a cron job using <b>SiteGround</b>.', 'groundhogg' ) ?></a>
                </li>
            </ul>
			<?php
			break;
		default:
			?>
            <p><?php _e( 'Not sure how to create an external cron job?', 'groundhogg' ); ?></p>
            <ul style="list-style-type: disc; padding-left: 20px">
                <li>
                    <a target="_blank"
                       href="https://help.groundhogg.io/article/49-add-an-external-cron-job-cron-job-org"><?php _e( 'Create a cron job using <b>cron-job.org</b>.', 'groundhogg' ) ?></a> <?php _e( '(Recommended)', 'groundhogg' ) ?>
                </li>
                <li>
                    <a target="_blank"
                       href="https://help.groundhogg.io/article/51-add-an-external-cron-job-cpanel"><?php _e( 'Create a cron job using <b>cPanel</b>.', 'groundhogg' ) ?></a>
                </li>
                <li>
                    <a target="_blank"
                       href="https://help.groundhogg.io/article/469-setting-up-a-cron-job-on-siteground"><?php _e( 'Create a cron job using <b>SiteGround</b>.', 'groundhogg' ) ?></a>
                </li>
            </ul>
			<?php
			break;
	endswitch;
}

$cron_jobs = [
	'wp-cron' => [
		'active'   => time() - get_option( 'wp_cron_last_ping' ) <= 15 * MINUTE_IN_SECONDS,
		'purpose'  => __( 'WordPress core', 'groundhogg' ),
		'interval' => __( 'every 15 minutes', 'groundhogg' )
	],
	'gh-cron' => [
		'active'   => time() - get_option( 'gh_cron_last_ping' ) <= MINUTE_IN_SECONDS,
		'purpose'  => __( 'emails and funnels', 'groundhogg' ),
		'interval' => __( 'every 1 minute', 'groundhogg' )
	]
];

$cron_jobs = apply_filters( 'groundhogg/cron_jobs', $cron_jobs );

$step = get_url_var( 'step' );

$cron_jobs_active = array_reduce( $cron_jobs, function ( $carry, $cron_job ) {
	return $carry && $cron_job['active'];
}, true );

if ( apply_filters( 'groundhogg/cron/verified', $cron_jobs_active && gh_cron_installed() ) ) {
	$step = 'verify';
}

switch ( $step ):

	default:
	case 'start':
		?>
        <div class="gh-tools-wrap">
            <p class="tools-help"><?php _e( 'Cron Job Setup', 'groundhogg' ); ?></p>
            <div class="gh-tools-box gh-panel">
                <p><?php printf( __( 'Follow these steps to optimize your WordPress & %s installation!', 'groundhogg' ), white_labeled_name() ); ?></p>
                <p><?php _e( 'It should only take a few minutes to complete.', 'groundhogg' ); ?></p>
				<?php

				html()->e( 'a', [
					'href'  => admin_page_url( [ 'tab' => 'cron', 'step' => 'install_gh_cron' ] ),
					'class' => 'gh-button primary'
				], __( 'Get Started', 'groundhogg' ), false, true );

				?>
            </div>
        </div>
		<?php
		break;
	case 'install_gh_cron':
		?>
        <div class="gh-tools-wrap">
            <p class="tools-help"><?php _e( 'Install Cron Files', 'groundhogg' ); ?></p>
            <div class="gh-tools-box gh-panel">
                <h3><?php printf( __( '%s Cron File' ), white_labeled_name() ) ?></h3>
                <p><?php _e( 'This file can be installed automatically.', 'groundhogg' ); ?></p>
                <p>
					<?php if ( ! gh_cron_installed() ): ?>
                        <a class="gh-button primary"
                           href="<?php echo esc_url( action_url( 'install_gh_cron' ) ); ?>">
							<?php _e( 'Install Automatically!', 'groundhogg' ); ?>
                        </a>
					<?php else: ?>
                        <span
                                style="color: green"><?php _e( "<code>gh-cron.php</code> is installed!", 'groundhogg' ); ?></span>
                        <a class="gh-button secondary"
                           href="<?php echo esc_url( action_url( 'uninstall_gh_cron' ) ); ?>">
							<?php _e( 'Uninstall', 'groundhogg' ); ?>
                        </a>
					<?php endif; ?>
                </p>
                <hr/>
                <p><?php _e( 'If automatic installation does not work, install it manually.', 'groundhogg' ); ?></p>
                <ol>
                    <li>
                        <a href="<?php echo esc_url( action_url( 'install_gh_cron_manually' ) ); ?>"><?php _e( 'Download the <code>gh-cron.txt</code> file.', 'groundhogg' ) ?></a>
                    </li>
                    <li><?php _e( 'Upload it to the root directory of WordPress. This is the same folder as your <code>wp-config.php</code> file.', 'groundhogg' ); ?></li>
                    <li><?php _e( 'Change the file extension from <code>.txt</code> to <code>.php</code>', 'groundhogg' ); ?></li>
                </ol>
				<?php

				do_action( 'groundhogg/cron/install_gh_cron' );

				html()->e( 'a', [
					'href'  => admin_page_url( [ 'tab' => 'cron', 'step' => 'create_external_jobs' ] ),
					'class' => 'gh-button ' . ( gh_cron_installed() ? 'primary' : 'secondary' )
				], __( 'Next &rarr;', 'groundhogg' ), false, true );

				?>
            </div>
        </div>
		<?php
		break;
	case 'create_external_jobs':
		?>
        <div class="gh-tools-wrap">
            <p class="tools-help"><?php _e( 'Create External Cron Jobs', 'groundhogg' ); ?></p>
            <div class="gh-tools-box gh-panel">
                <p><?php _e( 'You must create <b>external cron jobs</b> which will ping your site on regular intervals and make sure that scheduled events, like emails, run on time.', 'groundhogg' ); ?></p>
                <hr/>
				<?php foreach ( $cron_jobs as $cron_job_id => $cron_job ): ?>
                    <p><?php printf( __( 'This cron job is for <b>%s</b>, and should execute <code>%s</code>', 'groundhogg' ), $cron_job['purpose'], $cron_job['interval'] ); ?></p>
                    <p><input type="text" class="code regular-text" onfocus="this.select()"
                              value="<?php esc_attr_e( home_url( sprintf( '%s.php', $cron_job_id ) ) ); ?>" readonly>
                    </p>
                    <hr/>
				<?php endforeach;

				create_cron_job_instructions();

				html()->e( 'a', [
					'href'  => admin_page_url( [ 'tab' => 'cron', 'step' => 'verify' ] ),
					'class' => 'gh-button primary'
				], __( 'Verify setup &rarr;', 'groundhogg' ), false, true );

				?>
            </div>
        </div>
		<?php
		break;
	case 'verify':
		?>
        <div class="gh-tools-wrap">
            <p class="tools-help"><?php _e( 'Verify Setup', 'groundhogg' ); ?></p>
            <div class="gh-tools-box gh-panel">
                <p><?php _e( "Let's check to make sure you set up everything up correctly.", 'groundhogg' ); ?></p>
                <hr/>
				<?php if ( ! gh_cron_installed() ): ?>
                    <p>❌ <?php _e( 'The <code>gh-cron.php</code> file is not installed.', 'groundhogg' ); ?></p>
                    <p><a class=""
                          href="<?php echo esc_url( action_url( 'install_gh_cron' ) ); ?>">
							<?php _e( 'Install It Now!', 'groundhogg' ); ?>
                        </a></p>
				<?php else: ?>
                    <p>✅️ <?php _e( "The <code>gh-cron.php</code> file is installed!", 'groundhogg' ); ?></p>
				<?php endif; ?>
                <hr/>
				<?php do_action( 'groundhogg/cron/verify_files_installed' ); ?>
				<?php foreach ( $cron_jobs as $cron_job_id => $cron_job ): ?>
					<?php if ( ! $cron_job['active'] ): ?>
                        <p>
                            ❌ <?php printf( __( 'It looks like your cron job for <b>%s</b> is not working.', 'groundhogg' ), $cron_job['purpose'] ); ?></p>
                        <p><?php printf( __( 'This cron job is for <b>%s</b>, and should execute <code>%s</code>', 'groundhogg' ), $cron_job['purpose'], $cron_job['interval'] ); ?></p>
                        <p><input type="text" class="code regular-text" onfocus="this.select()"
                                  value="<?php esc_attr_e( home_url( sprintf( '%s.php', $cron_job_id ) ) ); ?>"
                                  readonly>
                        </p>
					<?php else: ?>
                        <p>
                            ✅️ <?php printf( __( "Success! Your cron job for <b>%s</b> is working.", 'groundhogg' ), $cron_job['purpose'] ); ?></p>
					<?php endif; ?>
                    <hr/>
				<?php endforeach; ?>
				<?php if ( $cron_jobs_active ): ?>
                    <p>
                        🎉 <?php _e( "Yay! You have successfully configured your cron jobs! That wasn't so hard was it?", 'groundhogg' ); ?></p>
					<?php if ( ! is_white_labeled() ) {
						html()->e( 'a', [
							'href'  => admin_page_url( 'groundhogg' ),
							'class' => 'gh-button primary'
						], __( 'Finish', 'groundhogg' ), false, true );
					} ?>
				<?php else: ?>
                    <p><?php _e( "Uh oh... one or more of your cron jobs could not be verified. Please re-check your setup and click the button below to re-verify.", 'groundhogg' ); ?></p>
					<?php

					create_cron_job_instructions();

					html()->e( 'a', [
						'href'  => admin_page_url( [ 'tab' => 'cron', 'step' => 'verify' ] ),
						'class' => 'gh-button primary'
					], __( 'Try again! &olarr;', 'groundhogg' ), false, true ); ?>
				<?php endif; ?>
            </div>
        </div>
		<?php
		break;
endswitch;
