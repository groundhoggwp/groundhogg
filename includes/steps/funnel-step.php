<?php

namespace Groundhogg\Steps;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Supports_Errors;
use function Groundhogg\array_map_to_class;
use function Groundhogg\ensure_array;
use function Groundhogg\force_custom_step_names;
use function Groundhogg\get_array_var;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\isset_not_empty;
use function Groundhogg\notices;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Funnel Step Parent
 *
 * Provides an easy way to add new funnel steps to the funnel builder.
 * Just extend this class and overwrite the following functions
 *
 * save()
 * run()
 *
 * if it's a benchmark, make a call to __construct() and add the function
 *
 * complete()
 *
 * @since       File available since Release 0.9
 * @see         WPGH_Form_Filled for an example.
 *
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Elements
 */
abstract class Funnel_Step extends Supports_Errors implements \JsonSerializable {
	protected static $step_properties = [];

	/**
	 * The current step
	 *
	 * @var Step
	 */
	protected $current_step = null;
	protected $current_contact = null;
	protected $current_event = null;

	/**
	 * @var array
	 */
	protected $posted_settings = [];

	const ACTION = 'action';
	const BENCHMARK = 'benchmark';
	const LOGIC = 'logic';

	public function is_legacy() {
		return false;
	}

	/**
	 * Setup all of the filters and actions to register this step and save it.
	 *
	 * WPGH_Funnel_Step constructor.
	 */
	public function __construct() {
		add_filter( "groundhogg/steps/{$this->get_group()}s", [ $this, 'register' ] );

		if ( is_admin() && ( $this->is_editing_screen() || wp_doing_ajax() ) ) {
			add_action( "admin_enqueue_scripts", [ $this, 'admin_scripts' ] );
			add_action( 'groundhogg/step/duplicated', [ $this, 'after_duplicate' ], 10, 2 );
		}

		add_action( "wp_enqueue_scripts", [ $this, 'frontend_scripts' ] );

		$this->add_additional_actions();
	}

	/**
	 * Process after duplicate step stuff
	 *
	 * @param $new      Step
	 * @param $original Step
	 *
	 * @return void
	 */
	public function after_duplicate( $new, $original ) {

		// not the right step type, exit out
		if ( $new->get_type() !== $this->get_type() ) {
			return;
		}

		$this->set_current_step( $new );

		$this->duplicate( $new, $original );
	}

	protected function add_additional_actions() {
	}

	/**
	 * Whether we are looking at the editing screen.
	 *
	 * @return bool
	 */
	protected function is_editing_screen() {
		return get_request_var( 'page' ) === 'gh_funnels' && get_request_var( 'action' ) === 'edit';
	}


	/**
	 * Get the element name
	 *
	 * @return string
	 */
	abstract public function get_name();

	/**
	 * Get the element type
	 *
	 * @return string
	 */
	abstract public function get_type();

	/**
	 * Get the element group
	 *
	 * @return string
	 */
	abstract public function get_group();

	/**
	 * Get the element group
	 *
	 * @return string
	 */
	public function get_sub_group() {
		return 'other';
	}

	/**
	 * Get the description
	 *
	 * @return string
	 */
	abstract public function get_description();

	/**
	 * Get the icon URL
	 *
	 * @return string
	 */
	abstract public function get_icon();

	/**
	 * If the icon is an SVG return the svg xml content
	 *
	 * @return string
	 */
	public function get_icon_svg() {

		$icon = $this->get_icon();

		if ( $icon && str_ends_with( $icon, '.svg' ) ) {

			// get the absolute path of the svg file relative to wp-content
			$icon_path = str_replace( WP_PLUGIN_URL, WP_PLUGIN_DIR, $icon );

			return file_get_contents( $icon_path );
		}

		return false;
	}

	/**
	 * If the icon is an svg
	 *
	 * @return bool
	 */
	public function icon_is_svg() {
		return $this->get_icon() && str_ends_with( $this->get_icon(), '.svg' );
	}

	/**
	 * Enqueue any admin scripts/styles
	 */
	public function admin_scripts() {
	}

	/**
	 * Enqueue any frontend scripts/styles
	 */
	public function frontend_scripts() {
	}

	/**
	 * Replacement for enqueue/get_delay_time
	 * This method should be overridden by child classes.
	 *
	 * @param int  $baseTimestamp
	 * @param Step $step
	 *
	 * @return int
	 */
	public function calc_run_time( int $baseTimestamp, Step $step ): int {

		// Step is still using the legacy enqueue method
		if ( method_exists( $this, 'enqueue' ) ) {
			_deprecated_function( get_called_class() . '::enqueue', '3.4', __CLASS__ . '::calc_run_time' );

			return $this->enqueue( $step );
		}

		// Step is still using the legacy get_delay_time method
		if ( method_exists( $this, 'get_delay_time' ) ) {
			_deprecated_function( get_called_class() . '::get_delay_time', '3.4', __CLASS__ . '::calc_run_time' );

			return $baseTimestamp + $this->get_delay_time( $step );
		}

		// Run now
		return $baseTimestamp;
	}

	/**
	 * Get the ICON of this action/benchmark
	 *
	 * @return string
	 */
	protected function get_default_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png';
	}

	protected function get_error_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/warning.svg';
	}

	/**
	 * Register the this action/benchmark with the globals...
	 *
	 * @param $array
	 *
	 * @return mixed
	 */
	public function register( $array ) {
		$array[ $this->get_type() ] = $this;

		self::$step_properties[ $this->get_type() ] = [
			'type'        => $this->get_type(),
			'name'        => $this->get_name(),
			'description' => $this->get_description(),
			'icon'        => $this->get_icon(),
			'svg'         => $this->get_icon_svg(),
		];

		return $array;
	}

	/**
	 * @param $type
	 *
	 * @return mixed
	 */
	public static function get_step_properties( $type ) {
		return self::$step_properties[ $type ];
	}

	/**
	 * Start a control section.
	 *
	 * @param array $args
	 */
	protected function start_controls_section( $args = [] ) {
		Plugin::$instance->utils->html->start_form_table( $args );
	}

	/**
	 * Ends a control section.
	 *
	 * @param array $args
	 */
	protected function end_controls_section() {
		Plugin::$instance->utils->html->end_form_table();
	}

	/**
	 * @param string $setting
	 * @param array  $args
	 */
	protected function add_control( $setting = '', $args = [] ) {
		$args = wp_parse_args( $args, [
			'label'       => '',
			'type'        => HTML::INPUT,
			'default'     => '',
			'field'       => [],
			'description' => '',
		] );

		$args['field']['id']   = $this->setting_id_prefix( $setting );
		$args['field']['name'] = $this->setting_name_prefix( $setting );

		// Multiple compatibility
		if ( isset_not_empty( $args['field'], 'multiple' ) && $args['field']['multiple'] === true ) {
			$args['field']['name']     .= '[]';
			$args['field']['multiple'] = true;
		}

		switch ( $args['type'] ) {
			default:
				$args['field']['value'] = esc_html( $this->get_setting( $setting, $args['default'] ) );
				break;
			case HTML::TAG_PICKER:
			case HTML::SELECT2:
			case HTML::ROUND_ROBIN:
			case HTML::DROPDOWN_SMS:
			case HTML::DROPDOWN_EMAILS:
			case HTML::DROPDOWN_CONTACTS:
			case HTML::DROPDOWN_OWNERS:
				$args['field']['selected'] = ensure_array( $this->get_setting( $setting, $args['default'] ) );
				break;
			case HTML::DROPDOWN:
				$args['field']['selected'] = esc_attr( $this->get_setting( $setting, $args['default'] ) );
				break;
			case HTML::CHECKBOX:
				$args['field']['checked'] = (bool) $this->get_setting( $setting, $args['default'] );
				break;
		}

		Plugin::$instance->utils->html->add_form_control( $args );
	}

	/**
	 * @param string $setting
	 *
	 * @return string
	 */
	protected function setting_id_prefix( $setting = '' ) {
		if ( empty( $setting ) ) {
			$setting = uniqid();
		}

		return sprintf( 'step_%d_%s', $this->get_current_step()->get_id(), $setting );
	}

	/**
	 * Return the name structure for settings within the step settings
	 *
	 * @param string $setting
	 *
	 * @return string
	 */
	protected function setting_name_prefix( $setting = '' ) {
		return sprintf( 'steps[%d][%s]', $this->get_current_step()->get_id(), $setting );
	}

	/**
	 * Retrieves a setting from the settings array provide by the step meta.
	 *
	 * @param string $key
	 * @param bool   $default
	 *
	 * @return mixed
	 */
	protected function get_setting( $key = '', $default = null ) {
		$val = $this->get_current_step()->get_meta( $key );

		// get the default from the schema
		if ( $default === null && $this->in_settings_schema( $key ) ) {
			$schema = $this->get_setting_schema( $key );

			if ( $schema && isset( $schema['default'] ) ) {
				$default = $schema['default'];
			}
		}

		return $val ?: $default;
	}

	/**
	 * Update a setting.
	 *
	 * @param string $setting
	 * @param string $val
	 */
	protected function save_setting( $setting = '', $val = '' ) {
		if ( empty( $val ) ) {
			$this->get_current_step()->delete_meta( $setting );
		} else {
			$this->get_current_step()->update_meta( $setting, $val );
		}
	}

	/**
	 * Get a normalized array of data for saving the step.
	 *
	 * @return array
	 */
	protected function get_posted_settings() {
		return $this->posted_settings;
	}

	/**
	 * Retrieves a setting from the posted settings when saving.
	 *
	 * @param string $key
	 * @param bool   $default
	 *
	 * @return mixed
	 */
	protected function get_posted_data( $key = '', $default = false ) {
		return get_array_var( $this->posted_settings, $key, $default );
	}

	/**
	 * @param Step $step
	 */
	public function set_current_step( Step $step ) {
		$this->current_step = $step;
	}

	/**
	 * @param Contact $contact
	 */
	public function set_current_contact( Contact $contact ) {
		$this->current_contact = $contact;
	}

	/**
	 * @param Event $event
	 */
	public function set_current_event( Event $event ) {
		$this->current_event = $event;
	}

	/**
	 * @return Contact
	 */
	protected function get_current_contact() {
		return $this->current_contact;
	}

	/**
	 * @return Step
	 */
	public function get_current_step() {
		return $this->current_step;
	}

	/**
	 * @return Event
	 */
	protected function get_current_event() {
		return $this->current_event;
	}

	/**
	 * Gets the step order
	 *
	 * @return false|int|string
	 */
	private function get_posted_order() {
		return array_search( $this->get_current_step()->get_id(), wp_parse_id_list( $_POST['step_ids'] ) ) + 1;
	}

	/**
	 * Display the settings based on the given ID
	 *
	 * @param $step Step
	 */
	abstract public function settings( $step );

	/**
	 * Get similar steps which can be used by benchmarks.
	 *
	 * @return Step[]
	 */
	public function get_like_steps( $query = [] ) {

		$args = [
			'step_type'  => $this->get_type(),
			'step_group' => $this->get_group()
		];

		$query = array_merge( $query, $args );

		$steps = get_db( 'steps' )->query( $query );

		array_map_to_class( $steps, Step::class );

		return $steps;

	}

	/**
	 * @param Step $step
	 */
	public function pre_html( Step $step ) {
		$this->set_current_step( $step );

		if ( $step->is_action() && $step->get_order() === 1 ) {
			$step->add_error( new \WP_Error( 'cant_start', __( 'A benchmark must be used to start the funnel.' ) ) );
		}

		$this->validate_settings( $step );
	}

	public function validate_settings( Step $step ) {

	}

	/**
	 * @return string
	 */
	public function get_help_article() {
		return 'https://help.groundhogg.io';
	}

	public function add_error( $code = '', $message = '', $data = [] ) {
		$this->get_current_step()->update_meta( 'has_errors', true );

		return parent::add_error( $code, $message, $data ); // TODO: Change the autogenerated stub
	}

	protected function labels() {

	}

	/**
	 * Retrieve the step title
	 *
	 * @param Step $step
	 *
	 * @return mixed
	 */
	public function get_title( $step = null ) {

		$generated = $this->generate_step_title( $step );
		if ( ! force_custom_step_names() && $generated ) {
            return $generated;
		}

		// mDefaults to the saved title
		return $step->get_title_formatted();
	}

	/**
	 * @param $step Step
	 */
	public function sortable_item( $step ) {

		$classes = [
			$step->get_group(),
			$step->get_type(),
			$step->step_status,
		];

		if ( $step->has_errors() || $this->has_errors() ) {
			$classes[] = 'has-errors';
		}

		if ( $step->has_changes() ) {
			$classes[] = 'has-changes';
		}

		$classes = apply_filters( 'groundhogg/steps/sortable/classes', $classes, $step, $this );

		?>
        <div
                id="step-<?php echo $step->get_id(); ?>"
                data-id="<?php echo $step->get_id(); ?>"
                data-type="<?php esc_attr_e( $this->get_type() ); ?>"
                class="step sortable-item <?php echo implode( ' ', $classes ) ?>">
            <input type="hidden" name="step_ids[]" value="<?php echo $step->get_id(); ?>">
            <input type="hidden" id="<?php echo $this->setting_id_prefix( 'branch' ) ?>" name="<?php echo $this->setting_name_prefix( 'branch' ) ?>" value="<?php esc_attr_e( $step->branch ); ?>">
            <div class="step-labels display-flex gap-10">
				<?php $this->labels(); ?>
				<?php if ( $step->is_benchmark() && $step->is_entry() ): ?>
                    <div class="step-label">Entry</div>
				<?php endif; ?>
				<?php if ( $step->is_benchmark() && $step->can_passthru() ): ?>
                    <div class="step-label">Pass-through</div>
				<?php endif; ?>
				<?php if ( $step->is_benchmark() && $step->is_conversion() ): ?>
                    <div class="step-label">Conversion</div>
				<?php endif; ?>
				<?php do_action( 'groundhogg/steps/sortable/labels', $step, $this ); ?>
            </div>
			<?php do_action( 'groundhogg/steps/sortable/inside', $step, $this ); ?>
			<?php do_action( "groundhogg/steps/{$this->get_type()}/sortable/inside", $step ); ?>
            <div class="actions has-box-shadow">
                <!-- DUPLICATE -->
                <button title="Duplicate" type="button" class="gh-button secondary text icon duplicate-step">
                    <span class="dashicons dashicons-admin-page"></span>
                    <div class="gh-tooltip top">Duplicate</div>
                </button>
                <!-- DELETE -->
                <button title="Delete" type="button" class="gh-button danger text icon delete-step">
                    <span class="dashicons dashicons-trash"></span>
                    <div class="gh-tooltip top">Delete</div>
                </button>
            </div>
            <div class="hndle">
				<?php if ( $step->has_errors() || $this->has_errors() ): ?>
                    <img class="hndle-icon error"
                         src="<?php echo $this->get_error_icon(); ?>">
				<?php else:

					if ( $this->icon_is_svg() ) {
						echo html()->e( 'div', [
							'class' => 'hndle-icon'
						], $this->get_icon_svg() );
					} else {
						?>
                        <img class="hndle-icon"
                             src="<?php echo $this->get_icon() ? $this->get_icon() : $this->get_default_icon(); ?>">
						<?php
					}

				endif; ?>
                <div>
					<?php
					echo html()->e( 'span', [
						'class' => 'step-title',
					], $this->get_title( $step ) );

					echo html()->e( 'span', [
						'class' => 'step-name',
					], $this->get_name() );
					?>
                </div>
            </div>
            <?php if ( $step->is_benchmark() ): ?>
            <div class="benchmark logic-line"></div>
            <?php endif; ?>
        </div>
		<?php

	}

	protected function before_step_notes( Step $step ) {
	}

	/**
	 * Generates the step title from the settings
	 *
	 * @param $step Step
	 *
	 * @return string|false
	 */
	public function generate_step_title( $step ) {
		return false;
	}

	/**
	 * @param $step Step
	 *
	 * @return void
	 */
	public function step_title_edit( $step ) {

		// If custom step names are not enforced and a generated step title is available
		if ( ! force_custom_step_names() && $this->generate_step_title( $step ) ) {
			?>
            <div class="gh-panel-header">
                <h2><?php printf( '%s Settings', $this->get_name() ) ?></h2>
            </div>
			<?php
			return;
		}

		$icon   = $this->get_icon() ? $this->get_icon() : $this->get_default_icon();
		$is_svg = preg_match( '/\.svg$/', $icon );

		?>
        <div class="step-title-wrap">
            <img class="step-icon <?php echo $is_svg ? 'is-svg' : '' ?>"
                 src="<?php echo $icon ?>">
            <div class="step-title-edit hidden">
				<?php
				$args = array(
					'id'      => $this->setting_id_prefix( 'title' ),
					'name'    => $this->setting_name_prefix( 'title' ),
					'value'   => __( $step->get_title(), 'groundhogg' ),
					'title'   => __( 'Step Title', 'groundhogg' ),
					'class'   => 'step-title-large edit-title full-width',
					'data-id' => $step->get_id(),
				);

				echo html()->input( $args );
				?>
            </div>
            <div class="step-title-view">
				<?php echo html()->e( 'span', [ 'class' => 'title' ], $step->get_step_title() ); ?>
            </div>
        </div>
		<?php
	}

	public function before_step_warnings() {
	}

	public function after_step_warnings() {
	}

	protected function before_settings( Step $step ) {

	}

	protected function after_settings( Step $step ) {
	}

	/**
	 * @param $step Step
	 */
	public function html_v2( $step ) {
		?>
        <div data-id="<?php echo $step->get_id(); ?>" data-type="<?php esc_attr_e( $this->get_type() ); ?>"
             id="settings-<?php echo $step->get_id(); ?>"
             class="step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?>">

            <!-- WARNINGS -->
			<?php $this->before_step_warnings() ?>
			<?php if ( $step->has_errors() || $this->has_errors() ): ?>
                <div class="step-warnings">
					<?php foreach ( $step->get_errors() as $error ): ?>

                        <div id="<?php $error->get_error_code() ?>"
                             class="notice notice-warning is-dismissible">
							<?php echo wpautop( wp_kses_post( $error->get_error_message() ) ); ?>
                        </div>
					<?php endforeach; ?>
					<?php foreach ( $this->get_errors() as $error ): ?>

                        <div id="<?php $error->get_error_code() ?>"
                             class="notice notice-warning is-dismissible">
							<?php echo wpautop( wp_kses_post( $error->get_error_message() ) ); ?>
                        </div>
					<?php endforeach; ?>
                </div>
			<?php endif; ?>
			<?php $this->after_step_warnings() ?>
            <!-- SETTINGS -->
            <div class="step-flex">
                <div class="step-edit panels">

					<?php $this->before_settings( $step ); ?>

                    <div class="gh-panel">
						<?php $this->step_title_edit( $step ); ?>
                        <div class="custom-settings">
							<?php $this->settings( $step ); ?>
                        </div>
                    </div>

					<?php $this->after_settings( $step ); ?>

					<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/before", $step ); ?>
					<?php do_action( 'groundhogg/steps/settings/before', $this ); ?>
					<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/after", $step ); ?>
					<?php do_action( 'groundhogg/steps/settings/after', $this ); ?>
                </div>
                <div class="step-notes">
					<?php $this->before_step_notes( $step ); ?>
					<?php if ( $step->is_benchmark() ): ?>
                        <div class="gh-panel benchmark-settings">
                            <div class="gh-panel-header">
                                <h2><?php _e( 'Settings', 'groundhogg' ); ?></h2>
                            </div>
                            <div class="inside display-flex gap-20 column">
								<?php if ( ! $step->is_starting() ):

									echo html()->checkbox( [
										'label'   => 'Allow contacts to enter the funnel at this step',
										'name'    => $this->setting_name_prefix( 'is_entry' ),
										'checked' => $step->is_entry()
									] );

									echo html()->checkbox( [
										'label'   => 'Allow contacts to pass through this benchmark',
										'name'    => $this->setting_name_prefix( 'can_passthru' ),
										'checked' => $step->can_passthru()
									] );

								endif;

								echo html()->checkbox( [
									'label'   => 'Track conversion when completed',
									'name'    => $this->setting_name_prefix( 'is_conversion' ),
									'checked' => $step->is_conversion()
								] );

								?>
                            </div>
                        </div>
					<?php endif; ?>
					<?php
					echo html()->textarea( [
						'id'          => $this->setting_id_prefix( 'step-notes' ),
						'name'        => 'step_notes',
						'value'       => $step->get_step_notes(),
						'placeholder' => __( 'You can use this area to store custom notes about the step.', 'groundhogg' ),
						'class'       => 'step-notes-textarea'
					] );
					?>
                </div>
            </div>
        </div>
		<?php
	}

	/**
	 * A schema to define the various different settings and their relevant sanitization functions and defaults
	 * This is an alternative to explicitly defining the save functions in the ::save() method
	 *
	 * @return array
	 */
	public function get_settings_schema() {
		return [];
	}

	/**
	 * Get the schema for a singular setting
	 *
	 * @param string $setting the setting
	 *
	 * @return array|bool if not found
	 */
	public function get_setting_schema( $setting ) {
		return $this->in_settings_schema( $setting ) ? $this->get_settings_schema()[ $setting ] : false;
	}

	/**
	 * Initialize the posted settings array
	 *
	 * @param $step Step
	 */
	public function pre_save( Step $step ) {

		$this->set_current_step( $step );

		// Something happened
		if ( ! $step->get_id() ) {
			return;
		}

		$this->posted_settings = wp_unslash( $_POST['steps'][ $step->get_id() ] );


		$step->delete_meta( 'has_errors' );

		// Loop through the schema and do any obvious work ahead of time.
		foreach ( $this->get_settings_schema() as $setting => $schema ) {

			// setting was not defined in the payload, skip because it may have been updated elsewhere
			if ( ! isset( $this->posted_settings[ $setting ] ) ) {
				continue;
			}

			// we don't need to sanitize first because the Step::update_meta() method will handle it.
			$this->save_setting( $setting, $this->posted_settings[ $setting ] );
		}

		$data = [
			'branch'     => sanitize_key( $this->get_posted_data( 'branch', 'main' ) ),
			'step_order' => $this->get_posted_order()
		];

		if ( $this->get_posted_data( 'title' ) ) {
			$data['title'] = sanitize_text_field( $this->get_posted_data( 'title' ) );
		}

		if ( $step->is_benchmark() ) {
			$data = array_merge( $data, [
				'is_conversion' => (bool) $this->get_posted_data( 'is_conversion', false ),
				'is_entry'      => (bool) $this->get_posted_data( 'is_entry', false ),
				'can_passthru'  => (bool) $this->get_posted_data( 'can_passthru', false ),
			] );
		}

		$step->update( $data );

	}

	/**
	 * Whether a setting is in the settings schema
	 *
	 * @param $key
	 *
	 * @return bool
	 */
	public function in_settings_schema( $key ) {
		return isset_not_empty( $this->get_settings_schema(), $key );
	}

	/**
	 * Given a setting and a value, find it in the schema then apply sanitization
	 *
	 * @param string $setting
	 * @param mixed  $value
	 * @param mixed  $old_value the old value if available
	 *
	 * @return false|mixed
	 */
	public function sanitize_setting( string $setting, $value ) {
		$schema = $this->get_settings_schema();

		if ( ! isset( $schema[ $setting ] ) ) {
			return false;
		}

		$setting_schema = wp_parse_args( $schema[ $setting ], [
			'default'  => false,
			'sanitize' => '\Groundhogg\sanitize_payload',
		] );

		if ( empty( $value ) ) {
			$value = $schema['default'];
		}

		return call_user_func( $setting_schema['sanitize'], $value );
	}

	/**
	 * Save the step based on the given ID
	 *
	 * @param $step Step
	 */
	public function save( $step ) {
	}

	/**
	 * @param $step Step
	 */
	public function after_save( $step ) {
		do_action( 'groundhogg/steps/save/after', $this, $step );

		if ( $this->has_errors() ) {
			foreach ( $this->get_errors() as $error ) {
				notices()->add( $error );
			}
		}

		if ( $step->has_errors() ) {
			foreach ( $step->get_errors() as $error ) {
				notices()->add( $error );
			}
		}

		// Maybe save a generated step title
		if ( ! force_custom_step_names() ) {
			$generated_title = $this->generate_step_title( $step );
			if ( $generated_title ) {
				$step->update( [
					'step_title' => $generated_title
				] );
			}
		}

		$step->set_slug();

		$this->clear_errors();
	}

	/**
	 * Setup args before the action/benchmark is run
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return Contact
	 */
	public function pre_run( $contact, $event ) {
		$this->set_current_event( $event );
		$this->set_current_step( $event->get_step() );
		$this->set_current_contact( $contact );

		return $contact;
	}

	/**
	 * Run the action/benchmark
	 *
	 * @param $contact Contact
	 * @param $event   Event
	 *
	 * @return bool
	 */
	public function run( $contact, $event ) {
		return true;
	}

	/**
	 * When the step is deleted
	 *
	 * @param Step $step
	 *
	 * @return void
	 */
	public function delete( Step $step ) {

	}

	/**
	 * @param $args array of args
	 * @param $step Step
	 */
	public function import( $args, $step ) {
		//silence is golden
	}

	/**
	 * Cleanup actions after the import of a step
	 *
	 * @param $step
	 *
	 * @return void
	 */
	public function post_import( $step ) {

	}

	/**
	 * Stuff to do when duplicating a step
	 *
	 * @param $new
	 * @param $original
	 *
	 * @return void
	 */
	public function duplicate( $new, $original ) {

	}

	/**
	 * @param $args array of args
	 * @param $step Step
	 *
	 * @return array
	 */
	public function export( $args, $step ) {
		//silence is golden
		return $args;
	}

	public function __toString() {
		// TODO: Implement __toString() method.
	}

	public function get_context() {
		return null;
	}

	/**
	 * Serialize the step for JS
	 *
	 * @return array
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {


		return [
			'icon'    => $this->get_icon(),
			'svg'     => $this->get_icon_svg(),
			'name'    => $this->get_name(),
			'type'    => $this->get_type(),
			'group'   => $this->get_group(),
			'context' => $this->get_context()
		];
	}
}
