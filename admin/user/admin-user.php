<?php

namespace Groundhogg\Admin\User;

use Groundhogg\Admin\Card_Sections;
use Groundhogg\Main_Roles;
use Groundhogg\Pii;
use Groundhogg\Pii_Admin;
use Groundhogg\Reporting\My_Day;
use WP_User;
use function Groundhogg\andList;
use function Groundhogg\bold_it;
use function Groundhogg\get_owners;
use function Groundhogg\get_post_var;
use function Groundhogg\get_team;
use function Groundhogg\get_team_ids;
use function Groundhogg\get_url_var;
use function Groundhogg\get_valid_contact_tabs;
use function Groundhogg\html;
use function Groundhogg\is_user_preference_enabled;
use function Groundhogg\white_labeled_name;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

class Admin_User {

	/**
	 * @var Admin_User
	 */
	protected static $instance;

	/**
	 * The instance that hooked into the admin, so other screens can reuse the profile fields
	 *
	 * @return Admin_User
	 */
	public static function instance() {

		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'edit_user_profile', [ $this, 'show_fields' ] );
		add_action( 'show_user_profile', [ $this, 'show_fields' ] );

		add_action( 'edit_user_profile_update', [ $this, 'save_fields' ] );
		add_action( 'personal_options_update', [ $this, 'save_fields' ] );

		add_filter( 'admin_body_class', [ $this, 'theme_body_class' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_theme' ], 100 );

		add_filter( 'groundhogg/command_palette/commands', [ $this, 'command_palette_commands' ] );
		add_action( 'wp_ajax_gh_set_admin_theme', [ $this, 'ajax_set_theme' ] );
	}

	/**
	 * Save the interface theme a user picked
	 *
	 * @param int    $user_id
	 * @param string $theme the slug of the theme, anything unknown or the default removes it
	 *
	 * @return void
	 */
	protected function save_theme( $user_id, $theme ) {

		$theme = sanitize_key( $theme );

		if ( $theme && $theme !== 'default' && array_key_exists( $theme, self::get_themes() ) ) {
			update_user_meta( $user_id, 'gh_admin_theme', $theme );
		} else {
			delete_user_meta( $user_id, 'gh_admin_theme' );
		}
	}

	/**
	 * Add changing the interface theme to the command palette
	 *
	 * @param array $commands
	 *
	 * @return array
	 */
	public function command_palette_commands( $commands ) {

		if ( ! current_user_can( 'view_contacts' ) ) {
			return $commands;
		}

		$nonce      = wp_create_nonce( 'gh_set_admin_theme' );
		$stylesheet = add_query_arg( 'ver', GROUNDHOGG_VERSION, GROUNDHOGG_ASSETS_URL . 'css/admin/themes.css' );

		foreach ( self::get_themes() as $slug => $label ) {
			$commands[] = [
				'name'     => 'groundhogg/theme/' . $slug,
				/* translators: 1: plugin name, 2: name of the interface theme */
				'label'    => sprintf( __( '%1$s Theme: %2$s', 'groundhogg' ), white_labeled_name(), $label ),
				'keywords' => [
					__( 'Interface', 'groundhogg' ),
					__( 'Appearance', 'groundhogg' ),
					__( 'Dark mode', 'groundhogg' ),
					__( 'Light mode', 'groundhogg' ),
				],
				'category' => 'command',
				'action'   => 'set_admin_theme',
				'args'     => [
					'theme'      => $slug,
					'nonce'      => $nonce,
					'stylesheet' => $stylesheet,
				],
			];
		}

		return $commands;
	}

	/**
	 * Save the interface theme of the current user from the command palette
	 */
	public function ajax_set_theme() {

		check_ajax_referer( 'gh_set_admin_theme' );

		$theme = sanitize_key( get_post_var( 'theme' ) );

		if ( ! current_user_can( 'view_contacts' ) || ! array_key_exists( $theme, self::get_themes() ) ) {
			wp_send_json_error( null, 400 );
		}

		$this->save_theme( get_current_user_id(), $theme );

		wp_send_json_success( [ 'theme' => $theme ] );
	}

	/**
	 * The interface themes a user can choose from, slug => label
	 *
	 * @return string[]
	 */
	public static function get_themes() {
		return apply_filters( 'groundhogg/admin/themes', [
			'default'  => _x( 'Default', 'admin theme', 'groundhogg' ),
			'light'    => _x( 'Light', 'admin theme', 'groundhogg' ),
			'paper'    => _x( 'Paper', 'admin theme', 'groundhogg' ),
			'slate'    => _x( 'Slate (dark)', 'admin theme', 'groundhogg' ),
			'midnight' => _x( 'Midnight (dark)', 'admin theme', 'groundhogg' ),
		] );
	}

	/**
	 * The slug of the theme the current user picked, or empty for the default look
	 *
	 * @return string
	 */
	protected function get_current_theme() {

		$theme = get_user_meta( get_current_user_id(), 'gh_admin_theme', true );

		if ( ! $theme || $theme === 'default' || ! array_key_exists( $theme, self::get_themes() ) ) {
			return '';
		}

		return $theme;
	}

	/**
	 * Whether the current admin screen is a Groundhogg page
	 *
	 * @return bool
	 */
	protected function is_groundhogg_page() {
		$page = get_url_var( 'page' );

		return $page && ( $page === 'groundhogg' || str_starts_with( $page, 'gh_' ) );
	}

	/**
	 * Add the theme class to the body of Groundhogg pages
	 *
	 * @param $class string
	 *
	 * @return string
	 */
	public function theme_body_class( $class ) {

		$theme = $this->get_current_theme();

		if ( $theme && $this->is_groundhogg_page() ) {
			$class .= " gh-theme-$theme";
		}

		return $class;
	}

	/**
	 * Load the theme stylesheet, which only overrides variables and colors
	 */
	public function enqueue_theme() {

		if ( ! $this->get_current_theme() || ! $this->is_groundhogg_page() ) {
			return;
		}

		wp_enqueue_style( 'groundhogg-admin-themes', GROUNDHOGG_ASSETS_URL . 'css/admin/themes.css', [ 'groundhogg-admin' ], GROUNDHOGG_VERSION );
	}

	/**
	 * Show signature area
	 *
	 * @param $profile_user WP_User
	 */
	public function show_fields( $profile_user ) {

		// Do not show for non-relevant users...
		if ( ! user_can( $profile_user, 'view_contacts' ) ) {
			return;
		}

		?>
        <h2 id="groundhogg-options"><?php echo esc_html( white_labeled_name() ); ?></h2>
		<?php

		$this->fields_table( $profile_user );
	}

	/**
	 * The things a user can switch off for themselves, in the user meta of the same name. They used to be settings of the
	 * whole site, see is_user_preference_enabled().
	 *
	 * @param $profile_user WP_User
	 *
	 * @return string[] meta key => label
	 */
	public static function get_interface_preferences( $profile_user ) {

		$preferences = [
			'gh_hide_tooltips'                => __( 'Hide the tooltips shown in new installations.', 'groundhogg' ),
			'gh_is_admin_bar_widget_disabled' => __( 'Hide the admin toolbar widget.', 'groundhogg' ),
		];

		// only shown to the people who are able to see it
		if ( user_can( $profile_user, 'view_others_contacts' ) ) {
			/* translators: white label name of the plugin, usually Groundhogg */
			$preferences['gh_disable_email_detection'] = sprintf( __( 'Disable the contact peek in non-%s admin screens.', 'groundhogg' ), white_labeled_name() );
		}

		return $preferences;
	}

	/**
	 * The fields of the user, without a heading, so they can be shown on any screen that saves with save_fields()
	 *
	 * @param $profile_user WP_User
	 */
	public function fields_table( $profile_user ) {

		wp_enqueue_style( 'groundhogg-admin-element' );

//		add_filter( 'mce_css', function ( $mce_css ) {
//			return $mce_css . ', ' . GROUNDHOGG_ASSETS_URL . 'css/admin/email-wysiwyg-style.css';
//		} );

		$can_send       = user_can( $profile_user, 'send_emails' );
		$can_see_report = user_can( $profile_user, 'view_reports' );
		$can_manage_pii = current_user_can( 'edit_users' );
		$is_manager     = Main_Roles::is_sales_manager( $profile_user );

		?>
        <div class="gh-settings-sections">
			<?php

			Card_Sections::section_start(
				__( 'Appearance', 'groundhogg' ),
				/* translators: %s: the plugin/brand name */
				sprintf( __( 'How %s looks and where it opens for you.', 'groundhogg' ), white_labeled_name() )
			);

			Card_Sections::row( __( 'Interface Theme', 'groundhogg' ), '', function () use ( $profile_user ) {
				$current_theme = get_user_meta( $profile_user->ID, 'gh_admin_theme', true ) ?: 'default';
				?>
                <div class="gh-admin-theme-picker" role="radiogroup" aria-label="<?php esc_attr_e( 'Interface Theme', 'groundhogg' ); ?>">
					<?php foreach ( self::get_themes() as $slug => $label ): ?>
                        <label class="gh-admin-theme-option">
                            <input type="radio" name="gh_admin_theme" value="<?php echo esc_attr( $slug ); ?>" <?php checked( $current_theme, $slug ); ?>>
                            <span class="gh-admin-theme-swatch" data-theme="<?php echo esc_attr( $slug ); ?>"></span>
                            <span class="gh-admin-theme-name"><?php echo esc_html( $label ); ?></span>
                        </label>
					<?php endforeach; ?>
                </div>
				<?php
			}, esc_html_x( 'Choose how Groundhogg looks for you. Does not change how it works.', 'settings', 'groundhogg' ) );

			Card_Sections::row( __( 'Default Contact Tab', 'groundhogg' ), '', function () use ( $profile_user ) {
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- generated HTML, labels are escaped downstream
				echo html()->button_toggle( [
					'name'     => 'gh_default_contact_tab',
					'id'       => 'gh_default_contact_tab',
					'options'  => get_valid_contact_tabs(),
					'selected' => $profile_user->gh_default_contact_tab ?: get_option( 'gh_default_contact_tab' )
				] );
			}, esc_html_x( 'Which tab should be selected by default when opening a contact record.', 'settings', 'groundhogg' ) );

			Card_Sections::row( __( 'Interface', 'groundhogg' ), '', function () use ( $profile_user ) {
				?>
                <input type="hidden" name="gh_interface_preferences_present" value="1">
                <div class="display-flex column gap-10">
					<?php foreach ( self::get_interface_preferences( $profile_user ) as $key => $label ): ?>
                        <label class="gh-checkbox-label">
                            <input type="checkbox" name="<?php echo esc_attr( $key ); ?>" value="on" <?php checked( is_user_preference_enabled( $key, $profile_user->ID ) ); ?>>
                            <span class="checkbox-label"><?php echo esc_html( $label ); ?></span>
                        </label>
					<?php endforeach; ?>
                </div>
				<?php
			}, esc_html_x( 'These only change what you see, other users choose their own.', 'settings', 'groundhogg' ) );

			Card_Sections::section_end();

			if ( $can_send ) {

				Card_Sections::section_start(
					__( 'Email', 'groundhogg' ),
					__( 'How your emails appear in the inbox of the people you send them to.', 'groundhogg' )
				);

				Card_Sections::row( __( 'Sender From Name', 'groundhogg' ), 'gh_from_name', function () use ( $profile_user ) {
					html( 'input', [
						'type'        => 'text',
						'id'          => 'gh_from_name',
						'name'        => 'gh_from_name',
						'value'       => get_user_meta( $profile_user->ID, 'gh_from_name', true ),
						'placeholder' => $profile_user->display_name
					] );
				}, esc_html_x( 'Your name as shown in the inbox of a recipient whenever you send an email. Leave empty to use your display name.', 'settings', 'groundhogg' ) );

				Card_Sections::row( __( 'Sender From Email', 'groundhogg' ), 'gh_from_email', function () use ( $profile_user ) {
					html( 'input', [
						'type'        => 'email',
						'id'          => 'gh_from_email',
						'name'        => 'gh_from_email',
						'value'       => get_user_meta( $profile_user->ID, 'gh_from_email', true ),
						'placeholder' => $profile_user->user_email
					] );
				}, esc_html_x( 'The email address used when you send an email. Leave empty to use your account email.', 'settings', 'groundhogg' ) );

				Card_Sections::row( __( 'Email Signature', 'groundhogg' ), '', function () use ( $profile_user ) {
					wp_editor( get_user_meta( $profile_user->ID, 'signature', true ), 'signature', [
						'textarea_rows' => 10
					] );
				}, __( 'Accepts HTML. The signature can be merged using the <code>{owner_signature}</code> in any email.', 'groundhogg' ), 'wide' );

				Card_Sections::section_end();
			}

			// everyone who can see contacts has their day, the reports are for the people who can see reports
			Card_Sections::section_start(
				__( 'Notifications', 'groundhogg' ),
				__( 'Reports and updates sent directly to your inbox.', 'groundhogg' ),
				'',
				'',
				'groundhogg-notifications'
			);

			if ( $can_see_report ) {

				Card_Sections::row( __( 'Email performance reports', 'groundhogg' ), '', function () use ( $profile_user ) {
					html( 'div', [
						'class' => 'display-flex column gap-10'
					], [
						html()->checkbox( [
							'label'   => 'Next-day broadcast results.',
							'name'    => 'gh_broadcast_results',
							'checked' => get_user_meta( $profile_user->ID, 'gh_broadcast_results', true )
						] ),
						html()->checkbox( [
							'label'   => 'Monthly & weekly overview.',
							'name'    => 'gh_weekly_overview',
							'checked' => get_user_meta( $profile_user->ID, 'gh_weekly_overview', true )
						] )
					] );
				}, esc_html__( 'Get performance reports sent directly to your inbox.', 'groundhogg' ) );
			}

			Card_Sections::row( __( 'My day', 'groundhogg' ), '', function () use ( $profile_user, $is_manager ) {
				?>
                <input type="hidden" name="gh_my_day_present" value="1">
                <div class="display-flex column gap-10">
                    <label class="gh-checkbox-label">
                        <input type="checkbox" name="gh_my_day" value="1" <?php checked( (bool) get_user_meta( $profile_user->ID, My_Day::META_ENABLED, true ) ); ?>>
                        <span class="checkbox-label"><?php esc_html_e( 'A daily summary of your tasks, replies and new contacts.', 'groundhogg' ); ?></span>
                    </label>
					<?php if ( $is_manager ): ?>
                        <label class="gh-checkbox-label">
                            <input type="checkbox" name="gh_my_day_team" value="1" <?php checked( (bool) get_user_meta( $profile_user->ID, My_Day::META_TEAM, true ) ); ?>>
                            <span class="checkbox-label"><?php esc_html_e( 'Include a section for my team.', 'groundhogg' ); ?></span>
                        </label>
					<?php endif; ?>
                    <div class="display-flex gap-10">
                        <label class="screen-reader-text" for="gh_my_day_hour"><?php esc_html_e( 'Time to send', 'groundhogg' ); ?></label>
                        <select name="gh_my_day_hour" id="gh_my_day_hour">
							<?php for ( $hour = 0; $hour < 24; $hour ++ ): ?>
                                <option value="<?php echo esc_attr( $hour ); ?>" <?php selected( My_Day::get_hour( $profile_user->ID ), $hour ); ?>><?php echo esc_html( wp_date( get_option( 'time_format' ), $hour * HOUR_IN_SECONDS, new \DateTimeZone( 'UTC' ) ) ); ?></option>
							<?php endfor; ?>
                        </select>
                        <label class="screen-reader-text" for="gh_my_day_timezone"><?php esc_html_e( 'Timezone', 'groundhogg' ); ?></label>
                        <select name="gh_my_day_timezone" id="gh_my_day_timezone">
							<?php echo wp_timezone_choice( My_Day::get_timezone( $profile_user->ID )->getName(), get_user_locale( $profile_user ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped by WordPress ?>
                        </select>
                    </div>
                </div>
				<?php
			}, esc_html__( 'Sent at this time in your timezone, and only on days when there is something in it.', 'groundhogg' ) );

			Card_Sections::section_end();

			if ( $can_manage_pii || $is_manager ) {

				Card_Sections::section_start(
					__( 'Access & Team', 'groundhogg' ),
					__( 'Access to the personal details of contacts, and the sales team.', 'groundhogg' )
				);

				if ( $can_manage_pii ) {

					Card_Sections::row( __( 'Personal information', 'groundhogg' ), '', function () use ( $profile_user ) {
						$can_manage = Pii_Admin::can_manage_access() && ! ( is_multisite() && is_super_admin( $profile_user->ID ) );
						?>
                        <input type="hidden" name="gh_pii_access_present" value="1">
                        <label class="gh-checkbox-label">
                            <input type="checkbox" name="gh_pii_access" value="1" <?php checked( user_can( $profile_user, Pii::CAP ) ); ?> <?php disabled( ! $can_manage ); ?>>
                            <span class="checkbox-label"><?php esc_html_e( 'Can see the personal information of contacts.', 'groundhogg' ); ?></span>
                        </label>
						<?php
					}, esc_html__( 'When this is off, the personal details of contacts, like their name, email address, phone numbers and address, are replaced with [redacted] for this user, and for any AI agent that connects as them. They can still work with contacts by their ID, tags, activity and segments, but cannot search by personal information, or export, import or download contact files.', 'groundhogg' ) );
				}

				if ( $is_manager ) {

					if ( current_user_can( 'delete_users' ) ) {

						Card_Sections::row( __( 'Sales Team', 'groundhogg' ), '', function () use ( $profile_user ) {

							$reps        = array_filter( get_owners(), fn( $user ) => ! user_can( $user, 'view_others_contacts' ) );
							$rep_options = [];
							foreach ( $reps as $rep ) {
								$rep_options[ $rep->ID ] = sprintf( '%s (%s)', $rep->display_name, $rep->user_email );
							}

							html( 'div', [ 'style' => [ 'max-width' => '700px' ] ], html()->select2( [
								'name'        => 'sales_team[]',
								'id'          => 'sales_team',
								'multiple'    => true,
								'options'     => $rep_options,
								'selected'    => get_team_ids( $profile_user->ID ),
								'placeholder' => 'Assign reps to this manager.'
							] ) );
						}, esc_html__( 'Create a sales team by assigning sales representatives to this manager. If no team is assigned this user will see all contacts.', 'groundhogg' ) );

					} else {

						Card_Sections::row( __( 'Sales Team', 'groundhogg' ), '', function () use ( $profile_user ) {

							$team      = get_team( $profile_user->ID );
							$team      = array_filter( $team, fn( $user ) => $user->ID !== $profile_user->ID ); // remove the current user from the team list
							$team_list = andList( array_map( fn( $user ) => bold_it( esc_html( $user->display_name ) ), $team ) );

							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- we're escaping earlier
							echo $team_list;
						}, esc_html__( 'Your team can only be edited by an administrator.', 'groundhogg' ) );
					}
				}

				Card_Sections::section_end();
			}

			?>
        </div>
		<?php

		do_action( 'groundhogg/user_profile', $profile_user );
	}

	/**
	 * Save the Groundhogg user fields...
	 *
	 * @param $user_id
	 */
	public function save_fields( $user_id ) {

		update_user_meta( $user_id, 'signature', wp_kses_post( get_post_var( 'signature' ) ) );
		update_user_meta( $user_id, 'gh_default_contact_tab', sanitize_text_field( get_post_var( 'gh_default_contact_tab' ) ) );
		update_user_meta( $user_id, 'gh_from_name', sanitize_text_field( get_post_var( 'gh_from_name' ) ) );
		update_user_meta( $user_id, 'gh_from_email', sanitize_email( get_post_var( 'gh_from_email' ) ) );

		$this->save_theme( $user_id, get_post_var( 'gh_admin_theme' ) );

		// an unchecked box isn't posted, so only act when the checkboxes were on the form
		if ( get_post_var( 'gh_interface_preferences_present' ) ) {
			foreach ( array_keys( self::get_interface_preferences( get_userdata( $user_id ) ) ) as $key ) {
				update_user_meta( $user_id, $key, get_post_var( $key ) ? 'on' : 'off' );
			}
		}

		// a disabled checkbox isn't posted, so only act when the field was on the form
		if ( get_post_var( 'gh_pii_access_present' ) && Pii_Admin::can_manage_access() ) {
			Pii_Admin::set_user_access( $user_id, boolval( get_post_var( 'gh_pii_access' ) ) );
		}

		if ( user_can( $user_id, 'view_reports' ) ) {
			update_user_meta( $user_id, 'gh_broadcast_results', boolval( get_post_var( 'gh_broadcast_results' ) ) );
			update_user_meta( $user_id, 'gh_weekly_overview', boolval( get_post_var( 'gh_weekly_overview' ) ) );
		} else {
			delete_user_meta( $user_id, 'gh_broadcast_results' );
			delete_user_meta( $user_id, 'gh_weekly_overview' );
		}

		// an unchecked box isn't posted, so only act when the fields were on the form
		if ( get_post_var( 'gh_my_day_present' ) ) {
			My_Day::save_preferences( $user_id, [
				'enabled'  => get_post_var( 'gh_my_day' ),
				'team'     => get_post_var( 'gh_my_day_team' ),
				'hour'     => get_post_var( 'gh_my_day_hour' ),
				'timezone' => get_post_var( 'gh_my_day_timezone' ),
			] );
		}

        if ( current_user_can(  'delete_users' ) && Main_Roles::is_sales_manager( $user_id ) ) {

            $sales_team = wp_parse_id_list( get_post_var( 'sales_team' ) );
            if ( empty( $sales_team ) ){
                delete_user_meta( $user_id, 'gh_sales_team_ids' );
            } else {
	            update_user_meta( $user_id, 'gh_sales_team_ids', $sales_team );
            }
        }
	}

}
