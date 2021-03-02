<?php

// Disabled Internal WP Cron
// Configured External Cron Job
// Synced Users & Contacts
// Imported a list
// Sent a broadcast email
// Launched a funnel

use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\files;
use function Groundhogg\get_db;
use function Groundhogg\html;

$checklist_items = [
	[
		'title'       => __( 'Disable Internal WP Cron', 'groundhogg' ),
		'description' => __( 'This is a WordPress best practice and will improve the performance of your site.', 'groundhogg' ),
		'completed'   => defined( 'DISABLE_WP_CRON' ),
		'fix'         => action_url( 'disable_wp_cron' ),
		'cap'         => 'manage_options'
	],
	[
		'title'       => __( 'Configure An External Cron Job', 'groundhogg' ),
		'description' => __( 'This is what will ensure Groundhogg sends emails on time!', 'groundhogg' ),
		'completed'   => time() - get_option( 'gh_cron_last_ping' ) <= MINUTE_IN_SECONDS,
		'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'advanced_cron' ] ),
		'cap'         => 'manage_options'
	],
    [
		'title'       => __( 'Integrate An SMTP Service', 'groundhogg' ),
		'description' => __( "You need a proper SMTP service to ensure your email reaches the inbox.", 'groundhogg' ),
		'completed'   => Groundhogg_Email_Services::get_marketing_service() !== 'wp_mail' || function_exists( 'mailhawk_mail' ),
		'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'advanced_cron' ] ),
		'cap'         => 'manage_options'
	],
	[
		'title'       => __( 'Sync Your Users & Contacts', 'groundhogg' ),
		'description' => __( "It looks like you have existing users in your site, let's sync them with your contacts so you can send them email.", 'groundhogg' ),
		'completed'   => count_users()['total_users'] <= get_db( 'contacts' )->count(),
		'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'sync_create_users' ] ),
		'cap'         => 'import_contacts'
	],
	[
		'title'       => __( 'Import Your List Of Contacts', 'groundhogg' ),
		'description' => __( "Let's bring in all your contacts, upload a CSV and import them into Groundhogg.", 'groundhogg' ),
		'completed'   => count( files()->get_imports() ) > 0,
		'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'import', 'action' => 'add' ] ),
		'cap'         => 'import_contacts'
	],
	[
		'title'       => __( 'Send A Broadcast Email To Your List', 'groundhogg' ),
		'description' => __( "Let's make sure your subscribers can hear you. Send them a broadcast email and say hello!", 'groundhogg' ),
		'completed'   => get_db( 'broadcasts' )->count( [ 'status' => 'sent' ] ) > 0,
		'fix'         => action_url( 'send_welcome_broadcast_email' ),
		'cap'         => 'edit_emails'
	],
	[
		'title'       => __( 'Launch A Funnel', 'groundhogg' ),
		'description' => __( "We're going to launch a funnel that will welcome new subscribers to the list. It will only take a few minutes.", 'groundhogg' ),
		'completed'   => get_db( 'funnels' )->count( [ 'status' => 'active' ] ) > 0,
		'fix'         => action_url( '' ),
		'cap'         => 'edit_funnels'
	],
];

?>

<div class="col">
    <div class="postbox onboarding-checklist">
        <div class="postbox-header">
            <h3 class="hndle"><span>🚀 <?php _e( 'Quickstart Checklist', 'groundhogg' ) ?></span></h3>
        </div>
		<?php foreach ( $checklist_items as $item ):
            if ( ! current_user_can( $item[ 'cap' ] ) ) continue; ?>
            <div class="checklist-row">
                <div class="item-status <?php echo $item['completed'] ? 'done' : 'todo' ?>">
					<?php if ( $item['completed'] ): ?>
                        ✅
					<?php else: ?>
                        ❌
					<?php endif; ?>
                </div>
                <div class="details">
                    <h3 class="item-title"><?php echo $item['title']; ?></h3>
                    <p class="description"><?php echo $item['description']; ?></p>
                </div>
				<?php if ( ! $item['completed'] ): ?>
                    <span class="fix-link"><?php echo html()->e( 'a', [ 'href' => $item['fix'] ], __( 'Fix', 'groundhogg' ) ); ?></span>
				<?php endif; ?>
            </div>
		<?php endforeach; ?>
    </div>
</div>