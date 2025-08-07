<?php

use function Groundhogg\action_input;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\event_queue_db;
use function Groundhogg\html;
use function Groundhogg\kses_e;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

$count_unprocessed = event_queue_db()->count_unprocessed();

?>
<p></p>
<div class="post-box-grid">
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php esc_html_e( 'Purge historical event logs', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><b><?php esc_html_e( 'Cancelled/Skipped/Failed Events', 'groundhogg' ); ?></b></p>
            <p><?php kses_e( __( 'You can safely purge <b>cancelled</b>, <b>skipped</b>, and <b>failed</b> event logs to free up some space as they do not affect reporting and are primarily used for debugging purposes.', 'groundhogg' ) ); ?></p>
			<?php echo html()->e( 'a', [
				'href'  => action_url( 'purge' ),
				'class' => 'gh-button secondary small'
			], 'Purge cancelled, skipped, and failed event logs' ); ?>
            <p><b><?php esc_html_e( 'Completed Events' ); ?></b></p>
            <p><?php kses_e( __( 'Purging completed event logs will free up space, but will adversely affect reporting and may impact flow automation for some contacts. You may want to download a backup of your database first. <b>Proceed with extreme caution.</b>', 'groundhogg' ) ); ?></p>
            <p><?php esc_html_e( 'Delete completed event logs older than...', 'groundhogg' ); ?></p>
            <form class="display-flex column gap-10" method="post">
				<?php

				html()->hidden_GET_inputs();
				action_input( 'purge_completed_tool', true, true );

				?>
                <div class="gh-input-group">
					<?php

					echo html()->input( [
						'name'        => 'time_range',
						'type'        => 'number',
						'class'       => 'input',
						'placeholder' => 3
					] );

					echo html()->dropdown( [
						'name'        => 'time_unit',
						'options'     => [
							'years'  => esc_html__( 'Years' ),
							'months' => esc_html__( 'Months' ),
							'weeks'  => esc_html__( 'Weeks' ),
							'days'   => esc_html__( 'Days' ),
						],
						'option_none' => false,
					] ) ?>
                </div>
                <span><?php esc_html_e( 'What type of logs should be deleted?', 'groundhogg' ) ?></span>
                <div class="gh-input-group">
					<?php

					echo html()->dropdown( [
						'name'        => 'what_to_delete',
						'options'     => [
							'all'       => _x( 'Everything', 'delete activity option', 'groundhogg' ),
							'funnel'    => _x( 'Flow events', 'delete activity option', 'groundhogg' ),
							'broadcast' => _x( 'Broadcast events', 'delete activity option', 'groundhogg' ),
							'other'     => _x( 'Other events', 'delete activity option', 'groundhogg' ),
						],
						'option_none' => false,
					] ) ?>
                </div>
                <div class="gh-input-group">
					<?php

					echo html()->input( [
						'name'        => 'confirm',
						'type'        => 'text',
						'class'       => 'full-width',
						'placeholder' => 'Type "confirm" to delete logs.',
						'required'    => true,
					] );

					echo html()->button( [
						'type'  => 'submit',
						'text'  => esc_html__( 'Delete', 'groundhogg' ),
						'class' => 'gh-button danger small'
					] )

					?>
                </div>
            </form>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php esc_html_e( 'Cancel Waiting/Paused Events', 'groundhogg' ) ?></h2>
        </div>
        <div class="inside">
            <p><?php esc_html_e( 'Cancelling events will prevent any automation from continuing and emails from being sent. After events are cancelled, they can be purged to free up space.', 'groundhogg' ) ?></p>
            <p><?php esc_html_e( 'Select which events to cancel.', 'groundhogg' ) ?></p>
            <form method="post" class="display-flex column gap-10">
				<?php

				html()->hidden_GET_inputs();
				action_input( 'cancel_events_tool', true, true );

				?>
                <div class="gh-input-group">
					<?php

					echo html()->dropdown( [
						'name'        => 'what_to_cancel',
						'options'     => [
							'all'       => esc_html__( 'Everything', 'groundhogg' ),
							'waiting'   => esc_html__( 'All waiting events', 'groundhogg' ),
							'paused'    => esc_html__( 'All paused events', 'groundhogg' ),
							'broadcast' => esc_html__( 'All broadcast events', 'groundhogg' ),
							'funnel'    => esc_html__( 'All flow events', 'groundhogg' ),
						],
						'option_none' => false,
					] ) ?>
                </div>
                <div class="gh-input-group">
					<?php

					echo html()->input( [
						'name'        => 'confirm',
						'type'        => 'text',
						'placeholder' => 'Type "confirm" to cancel events.',
						'class'       => 'full-width',
						'required'    => true,
					] );

					echo html()->button( [
						'type'  => 'submit',
						'text'  => esc_html__( 'Cancel', 'groundhogg' ),
						'class' => 'gh-button danger small'
					] )

					?>
                </div>
            </form>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php esc_html_e( 'Fix unprocessed events', 'groundhogg' ) ?></h2>
        </div>
        <div class="inside">
            <p><?php esc_html_e( 'Unprocessed events can accumulate in the event queue if there are hosting related issues that prevent Groundhogg from running normally.', 'groundhogg' ) ?></p>

			<?php if ( $count_unprocessed > 0 ): ?>
                <p><a href="<?php echo admin_page_url( 'gh_events', [
						'status' => 'unprocessed'
					] ) ?>"><?php printf( esc_html__( 'View a list of %s unprocessed events.', 'groundhogg' ), number_format_i18n( $count_unprocessed ) ) ?></a>
                </p>
                <p><?php esc_html_e( 'How would you like to handle unprocessed events?', 'groundhogg' ) ?></p>
                <form class="display-flex column gap-10" method="post">
					<?php

					html()->hidden_GET_inputs();
					action_input( 'fix_unprocessed', true, true );

					?>
                    <div class="gh-input-group">
						<?php

						echo html()->dropdown( [
							'name'        => 'fix_or_cancel',
							'options'     => [
								'cancel' => esc_html__( 'Cancel them' ),
								'fix'    => esc_html__( 'Fix them and then run immediately' ),
							],
							'option_none' => false,
						] ) ?>
                    </div>
                    <span><?php esc_html_e( 'Apply to events that are...', 'groundhogg' ) ?></span>
                    <div class="gh-input-group">
						<?php

						echo html()->dropdown( [
							'name'        => 'older_or_younger',
							'options'     => [
								'older'   => esc_html__( 'Older than' ),
								'younger' => esc_html__( 'Within the last' ),
							],
							'option_none' => false,
						] );

						echo html()->input( [
							'name'        => 'time_range',
							'type'        => 'number',
							'class'       => 'input',
							'placeholder' => 3,
							'required'    => true,

						] );

						echo html()->dropdown( [
							'name'        => 'time_unit',
							'options'     => [
								'years'  => esc_html__( 'Years' ),
								'months' => esc_html__( 'Months' ),
								'weeks'  => esc_html__( 'Weeks' ),
								'days'   => esc_html__( 'Days' ),
							],
							'option_none' => false,
						] ) ?>
                    </div>
                    <div class="gh-input-group">
						<?php

						echo html()->input( [
							'name'        => 'confirm',
							'type'        => 'text',
							'placeholder' => 'Type "confirm" to continue.',
							'class'       => 'full-width',
							'required'    => true,
						] );

						echo html()->button( [
							'type'  => 'submit',
							'text'  => esc_html__( 'Submit', 'groundhogg' ),
							'class' => 'gh-button danger small'
						] )

						?>
                    </div>
                </form>
			<?php else: ?>
                <p>✅ <?php esc_html_e( 'We have not detected any unprocessed events!', 'groundhogg' ) ?></p>
			<?php endif; ?>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php esc_html_e( 'Purge historical activity logs', 'groundhogg' ) ?></h2>
        </div>
        <div class="inside">
            <p><?php kses_e( __( 'Purging activity logs will free up space, but will adversely affect reporting and may impact flow automation for some contacts. You may want to download a backup of your database first. <b>Proceed with extreme caution.</b>', 'groundhogg' ) ); ?></p>
            <p><?php esc_html_e( 'Delete activity event logs older than...', 'groundhogg' ) ?></p>
            <form class="display-flex column gap-10" method="post">
				<?php

				html()->hidden_GET_inputs();
				action_input( 'purge_activity_tool', true, true );

				?>
                <div class="gh-input-group">
					<?php

					echo html()->input( [
						'name'        => 'time_range',
						'type'        => 'number',
						'class'       => 'input',
						'placeholder' => 3
					] );

					echo html()->dropdown( [
						'name'        => 'time_unit',
						'options'     => [
							'years'  => esc_html__( 'Years' ),
							'months' => esc_html__( 'Months' ),
							'weeks'  => esc_html__( 'Weeks' ),
							'days'   => esc_html__( 'Days' ),
						],
						'option_none' => false,
					] ) ?>
                </div>
                <span><?php esc_html_e( 'What type of activity should be deleted?' ) ?></span>
                <div class="gh-input-group">
					<?php

					echo html()->dropdown( [
						'name'        => 'what_to_delete',
						'options'     => [
							'all'    => esc_html__( 'Everything' ),
							'opens'  => esc_html__( 'Email Opens' ),
							'clicks' => esc_html__( 'Email Clicks' ),
							'login'  => esc_html__( 'Login history' ),
						],
						'option_none' => false,
					] ) ?>
                </div>
                <div class="gh-input-group">
					<?php

					echo html()->input( [
						'name'        => 'confirm',
						'type'        => 'text',
						'placeholder' => 'Type "confirm" to delete logs.',
						'class'       => 'full-width',
						'required'    => true,
					] );

					echo html()->button( [
						'type'  => 'submit',
						'text'  => esc_html__( 'Delete', 'groundhogg' ),
						'class' => 'gh-button danger small'
					] )

					?>
                </div>
            </form>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2 class="hndle"><?php esc_html_e( 'Restore missing flow events', 'groundhogg' ); ?></h2>
        </div>
        <div class="inside">
            <p><?php esc_html_e( 'If flow events get cancelled or deleted, this tool will restore contacts to their most recent position in any flows they were active in within the last 30 days.', 'groundhogg' ); ?></p>
            <p><?php echo html()->e( 'a', [
					'class' => 'gh-button danger',
					'href'  => action_url( 'restore_funnel_events' ),
				], esc_html__( 'Restore', 'groundhogg' ) ) ?></p>
        </div>
    </div>
</div>
