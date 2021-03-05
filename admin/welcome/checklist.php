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
use function Groundhogg\gh_cron_installed;
use function Groundhogg\html;
use function Groundhogg\modal_link_url;

$gh_cron_setup = time() - get_option( 'gh_cron_last_ping' ) <= MINUTE_IN_SECONDS;
$wp_cron_setup = time() - get_option( 'wp_cron_last_ping' ) <= 15 * MINUTE_IN_SECONDS;

// MailHawk is installed but not connected -> redirect to the mailhawk connect page
if ( function_exists( 'mailhawk_is_connected' ) && ! mailhawk_is_connected() ):
	$smtp_fix_link = admin_page_url( 'mailhawk' );

// The number of registered services is > 1, means that an integration is installed.
elseif ( count( Groundhogg_Email_Services::get() ) > 1 ):
	$smtp_fix_link = admin_page_url( 'gh_settings', [ 'tab' => 'email' ] );

// No other service is currently in use.
else:
	$smtp_fix_link = admin_page_url( 'gh_guided_setup', [ 'step' => '3' ] );

endif;

$checklist_items = [
	[
		'title'       => __( 'Configure Cron Jobs', 'groundhogg' ),
		'description' => __( 'This is a best practice and will improve the performance of your site.', 'groundhogg' ),
		'completed'   => $gh_cron_setup && $wp_cron_setup && gh_cron_installed() && defined( 'DISABLE_WP_CRON' ),
		'fix'         => admin_page_url( 'gh_tools', [ 'tab' => 'cron' ] ),
		'cap'         => 'manage_options'
	],
	[
		'title'       => __( 'Integrate An SMTP Service', 'groundhogg' ),
		'description' => __( "You need a proper SMTP service to ensure your email reaches the inbox. We recommend <a href='https://mailhawk.io'>MailHawk!</a>", 'groundhogg' ),
		'completed'   => Groundhogg_Email_Services::get_marketing_service() !== 'wp_mail' || function_exists( 'mailhawk_mail' ),
		'fix'         => $smtp_fix_link,
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
		'fix'         => modal_link_url( [
			'title'              => __( 'Send your first broadcast!', 'groundhogg' ),
			'footer_button_text' => __( 'Close' ),
			'source'             => 'send-your-first-broadcast-video',
			'class'              => 'img-link no-padding',
			'height'             => 555,
			'width'              => 800,
			'footer'             => 'true',
			'preventSave'        => 'true',
		] ),
		'cap'         => 'edit_emails'
	],
	[
		'title'       => __( 'Launch A Funnel', 'groundhogg' ),
		'description' => __( "We're going to launch a funnel that will welcome new subscribers to the list. It will only take a few minutes.", 'groundhogg' ),
		'completed'   => get_db( 'funnels' )->count( [ 'status' => 'active' ] ) > 0,
		'fix'         => modal_link_url( [
			'title'              => __( 'Create your first funnel!', 'groundhogg' ),
			'footer_button_text' => __( 'Close' ),
			'source'             => 'create-your-first-funnel-video',
			'class'              => 'img-link no-padding',
			'height'             => 555,
			'width'              => 800,
			'footer'             => 'true',
			'preventSave'        => 'true',
		] ),
		'cap'         => 'edit_funnels'
	],
];

$all_completed = array_reduce( $checklist_items, function ( $carry, $item ) {
	return $carry && $item['completed'];
}, true );

$hidden = get_user_option( 'gh_hide_groundhogg_quickstart' ) !== false;

?>
<div class="col">
    <div id="checklist" class="postbox onboarding-checklist <?php esc_attr_e( $hidden ? 'closed' : 'open' );?>">
        <div class="postbox-header">
            <h3 class="hndle"><span>🚀 <?php _e( 'Quickstart Checklist', 'groundhogg' ) ?></span></h3>
	        <div class="handle-actions">
		        <?php html()->e( 'a', [ 'href' => action_url( $hidden ? 'show_checklist' : 'hide_checklist' ) ], $hidden ? __( 'Show', 'groundhogg' ) : __( 'Hide', 'groundhogg' ), false, true ); ?>
	        </div>
        </div>
		<?php

		if ( $all_completed ):
            ?>
            <div class="inside checklist-complete">
	            <?php
	            html()->e( 'img', [
	            	'src' => GROUNDHOGG_ASSETS_URL . 'images/phil-confetti.png'
	            ], false, true, true )
	            ?>
                <p>🎉 <?php _e( "Yay! You finished the quickstart checklist! Groundhogg is now completely configured and you're ready to launch!", 'groundhogg' ); ?></p>
            </div>
            <?php
		else :

			foreach ( $checklist_items as $item ):

				if ( ! current_user_can( $item['cap'] ) ) {
					continue;
				} ?>
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
			<?php endforeach;
		endif; ?>
    </div>
</div>
