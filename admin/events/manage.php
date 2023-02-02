<?php

use function Groundhogg\action_input;
use function Groundhogg\action_url;
use function Groundhogg\admin_page_url;
use function Groundhogg\event_queue_db;
use function Groundhogg\html;

$count_unprocessed = event_queue_db()->count_unprocessed();

?>
<p></p>
<div class="post-box-grid">
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php _e( 'Purge historical event logs', 'groundhogg' ) ?></h2>
        </div>
        <div class="inside">
            <p><b><?php _e( 'Cancelled/Skipped/Failed Events', 'groundhogg' ) ?></b></p>
            <p><?php _e( 'You can safely purge <b>cancelled</b>, <b>skipped</b>, and <b>failed</b> event logs to free up some space as they do not affect reporting and are primarily used for debugging purposes.', 'groundhogg' ) ?></p>
			<?php echo html()->e( 'a', [
				'href'  => action_url( 'purge' ),
				'class' => 'gh-button secondary small'
			], 'Purge cancelled, skipped, and failed event logs' ) ?>
            <p><b><?php _e( 'Completed Events' ) ?></b></p>
            <p><?php _e( 'Purging completed event logs will free up space, but will adversely affect reporting and may impact funnel automation for some contacts. You may want to download a backup of your database first. <b>Proceed with extreme caution.</b>', 'groundhogg' ) ?></p>
            <p><?php _e( 'Delete completed event logs older than...', 'groundhogg' ) ?></p>
            <form class="display-flex column gap-10" method="post">
	            <?php

	            html()->hidden_GET_inputs();
	            action_input( 'purge_completed_tool', true, true );

	            ?>
                <div class="gh-input-group">
					<?php

					echo html()->input( [
                            'name' => 'time_range',
						'type'        => 'number',
						'class'       => 'input',
						'placeholder' => 3
					] );

					echo html()->dropdown( [
						'name' => 'time_unit',
						'options'     => [
							'years'  => __( 'Years' ),
							'months' => __( 'Months' ),
							'weeks'  => __( 'Weeks' ),
							'days'   => __( 'Days' ),
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
						'required'    => true,
					] );

					echo html()->button( [
						'type'  => 'submit',
						'text'  => __( 'Delete', 'groundhogg' ),
						'class' => 'gh-button danger small'
					] )

					?>
                </div>
            </form>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php _e( 'Cancel Waiting/Paused Events', 'groundhogg' ) ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'Cancelling events will prevent any automation from continuing and emails from being sent. After events are cancelled, they can be purged to free up space.', 'groundhogg' ) ?></p>
            <p><?php _e( 'Select which events to cancel.', 'groundhogg' ) ?></p>
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
							'all'       => __( 'Everything' ),
							'waiting'   => __( 'All waiting events' ),
							'paused'    => __( 'All paused events' ),
							'funnel'    => __( 'All broadcast events' ),
							'broadcast' => __( 'All funnel events' ),
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
						'required'    => true,
					] );

					echo html()->button( [
						'type'  => 'submit',
						'text'  => __( 'Cancel', 'groundhogg' ),
						'class' => 'gh-button danger small'
					] )

					?>
                </div>
            </form>
        </div>
    </div>
    <div class="gh-panel">
        <div class="gh-panel-header">
            <h2><?php _e( 'Fix unprocessed events', 'groundhogg' ) ?></h2>
        </div>
        <div class="inside">
            <p><?php _e( 'Unprocessed events can accumulate in the event queue if there are hosting related issues that prevent Groundhogg from running normally.', 'groundhogg' ) ?></p>

			<?php if ( $count_unprocessed > 0 ): ?>
                <p><a href="<?php echo admin_page_url( 'gh_events', [
						'status' => 'unprocessed'
					] ) ?>"><?php printf( __( 'View a list of %s unprocessed events.', 'groundhogg' ), number_format_i18n( $count_unprocessed ) ) ?></a>
                </p>
                <p><?php _e( 'How would you like to handle unprocessed events?', 'groundhogg' ) ?></p>
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
								'cancel' => __( 'Cancel them' ),
								'fix'    => __( 'Fix them and then run immediately' ),
							],
							'option_none' => false,
						] ) ?>
                    </div>
                    <span><?php _e( 'Apply to events that are...', 'groundhogg' ) ?></span>
                    <div class="gh-input-group">
						<?php

						echo html()->dropdown( [
							'name'        => 'older_or_younger',
							'options'     => [
								'older'   => __( 'Older than' ),
								'younger' => __( 'Within the last' ),
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
								'years'  => __( 'Years' ),
								'months' => __( 'Months' ),
								'weeks'  => __( 'Weeks' ),
								'days'   => __( 'Days' ),
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
							'required'    => true,
						] );

						echo html()->button( [
							'type'  => 'submit',
							'text'  => __( 'Submit', 'groundhogg' ),
							'class' => 'gh-button danger small'
						] )

						?>
                    </div>
                </form>
			<?php else: ?>
                <p>✅ <?php _e( 'We have not detected any unprocessed events!', 'groundhogg' ) ?></p>
			<?php endif; ?>
        </div>
    </div>

</div>
