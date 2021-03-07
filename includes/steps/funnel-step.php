<?php

namespace Groundhogg\Steps;

use Groundhogg\Contact;
use Groundhogg\Contact_Query;
use function Groundhogg\dashicon;
use function Groundhogg\ensure_array;
use function Groundhogg\get_db;
use function Groundhogg\html;
use Groundhogg\Event;
use function Groundhogg\get_array_var;
use function Groundhogg\get_request_var;
use function Groundhogg\isset_not_empty;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use Groundhogg\Supports_Errors;
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
 * @see WPGH_Form_Filled for an example.
 *
 * @package     Elements
 * @subpackage  Elements/Benchmarks
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */
abstract class Funnel_Step extends Supports_Errors {
	protected static $step_properties = [];

	/**
	 * The current step
	 *
	 * @var Step
	 */
	protected $current_step = null;
	protected $current_contact = null;

	/**
	 * @var array
	 */
	protected $posted_settings = [];

	const ACTION = 'action';
	const BENCHMARK = 'benchmark';

	/**
	 * Setup all of the filters and actions to register this step and save it.
	 *
	 * WPGH_Funnel_Step constructor.
	 */
	public function __construct() {
		add_filter( "groundhogg/steps/{$this->get_group()}s", [ $this, 'register' ] );

		if ( is_admin() && ( $this->is_editing_screen() || wp_doing_ajax() ) ) {

			/**
			 * New filters/actions for better usability and extendability
			 *
			 * @since 1.1
			 */

			add_action( "groundhogg/steps/{$this->get_type()}/sortable", [ $this, 'pre_html' ], 1 );
			add_action( "groundhogg/steps/{$this->get_type()}/sortable", [ $this, 'sortable_item' ] );

			add_action( "groundhogg/steps/{$this->get_type()}/html", [ $this, 'pre_html' ], 1 );
			add_action( "groundhogg/steps/{$this->get_type()}/html", [ $this, 'html' ] );

			add_action( "groundhogg/steps/{$this->get_type()}/html_v2", [ $this, 'pre_html' ], 1 );
			add_action( "groundhogg/steps/{$this->get_type()}/html_v2", [ $this, 'html_v2' ] );

			add_action( "groundhogg/steps/{$this->get_type()}/save", [ $this, 'pre_save' ], 1 );
			add_action( "groundhogg/steps/{$this->get_type()}/save", [ $this, 'save' ], 11 );
			add_action( "groundhogg/steps/{$this->get_type()}/save", [ $this, 'after_save' ], 99 );

			add_action( "admin_enqueue_scripts", [ $this, 'admin_scripts' ] );
		}

		/**
		 * New filters/actions for better usability and extendability
		 *
		 * @since 1.1
		 */
		add_action( "groundhogg/steps/{$this->get_type()}/import", [ $this, 'pre_import' ], 1, 2 );
		add_action( "groundhogg/steps/{$this->get_type()}/import", [ $this, 'import' ], 10, 2 );

		add_filter( "groundhogg/steps/{$this->get_type()}/export", [ $this, 'pre_export' ], 1, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/export", [ $this, 'export' ], 10, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/enqueue", [ $this, 'pre_enqueue' ], 1 );
		add_filter( "groundhogg/steps/{$this->get_type()}/enqueue", [ $this, 'enqueue' ] );
		add_filter( "groundhogg/steps/{$this->get_type()}/run", [ $this, 'pre_run' ], 1, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/run", [ $this, 'run' ], 10, 2 );
		add_filter( "groundhogg/steps/{$this->get_type()}/icon", [ $this, 'get_icon' ] );
		add_action( "wp_enqueue_scripts", [ $this, 'frontend_scripts' ] );

		$this->add_additional_actions();
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
	 * Get the delay time in seconds.
	 *
	 * @param Step
	 *
	 * @return int
	 */
	public function get_delay_time( $step ) {
		return 0;
	}

	/**
	 * Enqueue the step in the event queue...
	 *
	 * @param $step Step
	 *
	 * @return int
	 */
	public function enqueue( $step ) {
		return time() + $this->get_delay_time( $step );
	}

	/**
	 * Get the ICON of this action/benchmark
	 *
	 * @return string
	 */
	protected function get_default_icon() {
		return GROUNDHOGG_ASSETS_URL . 'images/funnel-icons/no-icon.png';
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
	 * @param array $args
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
	 * @param bool $default
	 *
	 * @return mixed
	 */
	protected function get_setting( $key = '', $default = false ) {
		$val = $this->get_current_step()->get_meta( $key );

		return $val ? $val : $default;
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
	 * @param bool $default
	 *
	 * @return mixed
	 */
	protected function get_posted_data( $key = '', $default = false ) {
		return get_array_var( $this->posted_settings, $key, $default );
	}

	/**
	 * @return Step
	 */
	public function get_current_step() {
		return $this->current_step;
	}

	/**
	 * @param Step $step
	 */
	protected function set_current_step( Step $step ) {
		$this->current_step = $step;
	}

	/**
	 * @param Contact $contact
	 */
	protected function set_current_contact( Contact $contact ) {
		$this->current_contact = $contact;
	}

	/**
	 * @return Contact
	 */
	protected function get_current_contact() {
		return $this->current_contact;
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
	 * Todo Get the reporting interval.
	 * Returns the reporting interval for the reporting view.
	 *
	 * @return array
	 */
	protected function get_reporting_interval() {
		$times = [
			'start_time' => Plugin::$instance->reporting->get_start_time(),
			'end_time'   => Plugin::$instance->reporting->get_end_time(),
			'range'      => Plugin::$instance->reporting->get_range(),
		];

		return $times;
	}

	/**
	 * Get the reporting view for the STEP
	 * Most steps will use the default step reporting given here...
	 *
	 * @param $step Step
	 *
	 * @deprecated  Version 2.2 use Dashboard APi to add graphs
	 */
	public function reporting_v2( $step ) {

		?>
		<div class="step-title-wrap">
			<div class="step-title-view">
				<?php printf( __( 'Reporting %s', 'groundhogg' ), html()->e( 'span', [ 'class' => 'title' ], $step->get_step_title() ) ); ?>
			</div>
		</div>
		<div class="reporting-results">
			<h3><?php _e( 'History', 'groundhogg' ); ?></h3>
			<?php

			$stats = $this->quick_stats( $step );

			$cols  = wp_list_pluck( $stats, 0 );
			$stats = wp_list_pluck( $stats, 1 );

			html()->list_table( [ 'style' => [ 'margin-bottom' => '10px' ] ], $cols, [ $stats ], false );

			?>
		</div>
		<?php
	}

	/**
	 * Get the reporting view for the STEP
	 * Most steps will use the default step reporting given here...
	 *
	 * @param $step Step
	 */
	public function reporting( $step ) {

		$times = $this->get_reporting_interval();

		$start_time = $times['start_time'];
		$end_time   = $times['end_time'];

		$cquery = new Contact_Query();

		if ( $step->is_action() ):

			$num_events_waiting = $cquery->query( [
				'count'  => true,
				'report' => [
					'step'   => $step->get_id(),
					'funnel' => $step->get_funnel_id(),
					'status' => 'waiting'
				]
			] );

			?>
			<p class="report">
				<?php _e( 'Waiting:', 'groundhogg' ) ?>
				<a target="_blank" href="<?php echo add_query_arg( [
					'report' => [
						'step'   => $step->get_id(),
						'funnel' => $step->get_funnel_id(),
						'status' => 'waiting'
					]
				], admin_url( 'admin.php?page=gh_contacts' ) ); ?>">
					<b><?php echo $num_events_waiting; ?></b>
				</a>
			</p>
			<hr>
		<?php
		endif;

		$num_events_completed = $cquery->query( [
			'count'  => true,
			'report' => [
				'start'  => $start_time,
				'end'    => $end_time,
				'step'   => $step->get_id(),
				'funnel' => $step->get_funnel_id(),
				'status' => 'complete'
			]
		] );

		?>
		<p class="report">
			<?php _e( 'Completed:', 'groundhogg' ) ?>
			<a target="_blank" href="<?php echo add_query_arg( [
				'report' => [
					'step'   => $step->get_id(),
					'funnel' => $step->get_funnel_id(),
					'status' => 'complete',
					'start'  => $start_time,
					'end'    => $end_time,
				]
			], admin_url( 'admin.php?page=gh_contacts' ) ); ?>">
				<b><?php echo $num_events_completed; ?></b>
			</a>
		</p>
		<?php
	}

	/**
	 * Get the reporting view for the STEP
	 * Most steps will use the default step reporting given here...
	 *
	 * @param $step Step
	 *
	 * @return array
	 */
	public function quick_stats( $step ) {

		$times = $this->get_reporting_interval();

		$start_time = $times['start_time'];
		$end_time   = $times['end_time'];

		$cquery = new Contact_Query();

		$stats = [];

		$num_events_waiting = $cquery->query( [
			'count'  => true,
			'report' => [
				'step'   => $step->get_id(),
				'funnel' => $step->get_funnel_id(),
				'status' => 'waiting'
			]
		] );

		$stats[] = [
			__( 'Waiting', 'groundhogg' ),
			html()->e( 'a', [
				'target' => '_blank',
				'class'  => 'number',
				'href'   => add_query_arg( [
					'report' => [
						'step'   => $step->get_id(),
						'funnel' => $step->get_funnel_id(),
						'status' => 'waiting'
					]
				], admin_url( 'admin.php?page=gh_contacts' ) )
			], absint( $num_events_waiting ), false )
		];

		$num_events_completed = $cquery->query( [
			'count'  => true,
			'report' => [
				'start'  => $start_time,
				'end'    => $end_time,
				'step'   => $step->get_id(),
				'funnel' => $step->get_funnel_id(),
				'status' => 'complete'
			]
		] );

		$stats[] = [
			__( 'Complete', 'groundhogg' ),
			html()->e( 'a', [
				'target' => '_blank',
				'class'  => 'number',
				'href'   => add_query_arg( [
					'report' => [
						'start'  => $start_time,
						'end'    => $end_time,
						'step'   => $step->get_id(),
						'funnel' => $step->get_funnel_id(),
						'status' => 'complete'
					]
				], admin_url( 'admin.php?page=gh_contacts' ) )
			], absint( $num_events_completed ), false )
		];

		return apply_filters( "groundhogg/steps/{$this->get_type()}/reporting_v2", $stats );
	}

	/**
	 * Get similar steps which can be used by benchmarks.
	 * @return Step[]
	 */
	public function get_like_steps( $query = [] ) {

		$args  = [ 'step_type' => $this->get_type(), 'step_group' => $this->get_group() ];
		$query = array_merge( $query, $args );

		$raw_steps = get_db( 'steps' )->query( $query );

		$steps = [];

		foreach ( $raw_steps as $raw_step ) {
			$step = new Step( absint( $raw_step->ID ) );

			if ( $step ) {
				$steps[] = $step;
			}
		}

		return $steps;

	}

	/**
	 * @param Step $step
	 */
	public function pre_html( Step $step ) {
		$this->set_current_step( $step );
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

	/**
	 * @param $step Step
	 */
	public function sortable_item( $step ) {

		?>
		<div data-id="<?php echo $step->get_id(); ?>" data-type="<?php esc_attr_e( $this->get_type() ); ?>"
		     title="<?php echo $step->get_title() ?>" id="<?php echo $step->get_id(); ?>"
		     class="step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?> <?php if ( $step->get_meta( 'is_active' ) ) {
			     echo 'active';
		     } ?>">
			<input type="hidden" name="step_ids[]" value="<?php echo $step->get_id(); ?>">
			<?php echo html()->input( [
				'type'  => 'hidden',
				'name'  => $this->setting_name_prefix( 'is_active' ),
				'id'    => $this->setting_id_prefix( 'is_active' ),
				'value' => $this->get_setting( 'is_active' ),
				'class' => 'is_active'
			] ); ?>

			<span class="actions">
            <!-- DELETE -->
            <button title="Delete" type="button" class="handlediv delete-step">
                <span class="dashicons dashicons-trash"></span>
            </button>
				<!-- DUPLICATE -->
            <button title="Duplicate" type="button" class="handlediv duplicate-step">
                <span class="dashicons dashicons-admin-page"></span>
            </button>
            <?php
            echo html()->modal_link( [
	            'title'              => __( 'Add Step' ),
	            'text'               => dashicon( 'plus' ),
	            'footer_button_text' => __( 'Cancel' ),
	            'class'              => 'add-step button button-secondary no-padding',
	            'source'             => 'steps',
	            'height'             => 700,
	            'width'              => 600,
	            'footer'             => 'true',
	            'preventSave'        => 'true',
            ] );
            ?>
            </span>
			<h2 class="hndle ui-sortable-handle">
				<img class="hndle-icon" width="50"
				     src="<?php echo $this->get_icon() ? $this->get_icon() : $this->get_default_icon(); ?>">
				<span>
	                <?php

	                $title = $step->get_title();

	                if ( $step->get_meta( 'has_errors' ) ) {
		                $title = "<span class='step-error'>&#x26A0</span> " . $title;
	                }

	                echo html()->e( 'span', [
		                'class' => 'step-title',
	                ], $title );

	                echo "<br/>";

	                ?>
                </span>
				<?php echo html()->e( 'span', [
					'class' => 'step-name',
				], $this->get_name() );
				?>
				<div class="wp-clearfix"></div>
			</h2>
		</div>
		<?php

	}

	/**
	 * @param $step Step
	 */
	public function html_v2( $step ) {
		?>
		<div data-id="<?php echo $step->get_id(); ?>" data-type="<?php esc_attr_e( $this->get_type() ); ?>"
		     title="<?php echo $step->get_title() ?>" id="settings-<?php echo $step->get_id(); ?>"
		     class="step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?> <?php echo ( $step->get_meta( 'is_active' ) ) ? 'active' : 'hidden'; ?>">

			<div class="step-background">
				<div class="inside">
					<!-- SETTINGS -->
					<div class="step-edit">
						<div class="step-title-wrap">
							<img class="step-icon"
							     src="<?php echo $this->get_icon() ? $this->get_icon() : $this->get_default_icon(); ?>">
							<div class="step-title-edit hidden">
								<?php
								$args = array(
									'id'      => $this->setting_id_prefix( 'title' ),
									'name'    => $this->setting_name_prefix( 'title' ),
									'value'   => __( $step->get_title(), 'groundhogg' ),
									'title'   => __( 'Step Title', 'groundhogg' ),
									'class'   => 'step-title-large edit-title',
									'data-id' => $step->get_id(),
								);

								echo Plugin::$instance->utils->html->input( $args );
								?>
							</div>
							<div class="step-title-view">
								<?php echo html()->e( 'span', [ 'class' => 'title' ], $step->get_step_title() ); ?>
							</div>
						</div>
						<div class="custom-settings">
							<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/before", $step ); ?>
							<?php do_action( 'groundhogg/steps/settings/before', $this ); ?>
							<?php $this->settings( $step ); ?>
							<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/after", $step ); ?>
							<?php do_action( 'groundhogg/steps/settings/after', $this ); ?>
						</div>
					</div>
					<!-- REPORTING  -->
					<!--                <div class="step-reporting">-->
					<!--					--><?php //do_action( "groundhogg/steps/{$this->get_type()}/reporting/before", $step ); ?>
					<!--					--><?php //do_action( 'groundhogg/steps/reporting/before', $step ); ?>
					<!--					--><?php //$this->reporting_v2( $step ); ?>
					<!--					--><?php //do_action( "groundhogg/steps/{$this->get_type()}/reporting/after", $step ); ?>
					<!--					--><?php //do_action( 'groundhogg/steps/reporting/after', $step ); ?>
					<!--                </div>-->


				</div>
			</div>
			<div class="step-notes">
				<div class="step-notes" style="margin-top: 10px;padding-bottom: 30px">
					<?php
					echo html()->textarea( [
						'id'          => $this->setting_id_prefix( 'step-notes' ),
						'name'        => $this->setting_name_prefix( 'step_notes' ),
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
	 * @param $step Step
	 *
	 * @deprecated since 2.1
	 */
	public function html( $step ) {

		$closed = $step->get_meta( 'is_closed' ) ? 'closed' : '';

		?>
		<div data-type="<?php esc_attr_e( $this->get_type() ); ?>" title="<?php echo $step->get_title() ?>"
		     id="<?php echo $step->get_id(); ?>"
		     class="postbox step <?php echo $step->get_group(); ?> <?php echo $step->get_type(); ?> <?php echo $closed; ?>">
			<button type="button" class="handlediv collapse"><span class="toggle-indicator" aria-hidden="true"></span>
			</button>
			<input type="hidden" class="collapse-input" name="<?php echo $this->setting_name_prefix( 'closed' ); ?>"
			       value="<?php echo $this->get_setting( 'is_closed' ); ?>">
			<input type="hidden" name="step_ids[]" value="<?php echo $step->get_id(); ?>">

			<!-- DELETE -->
			<button title="Delete" type="button" class="handlediv delete-step">
				<span class="dashicons dashicons-trash"></span>
			</button>
			<!-- DUPLICATE -->
			<button title="Duplicate" type="button" class="handlediv duplicate-step">
				<span class="dashicons dashicons-admin-page"></span>
			</button>
			<!-- HELP -->
			<button title="Help" type="button" class="handlediv help">
				<?php echo html()->help_icon( $this->get_help_article() ); ?>
			</button>
			<!-- HNDLE -->
			<h2 class="hndle ui-sortable-handle">
				<img class="hndle-icon" width="50"
				     src="<?php echo $this->get_icon() ? $this->get_icon() : $this->get_default_icon(); ?>">

				<?php $args = array(
					'id'    => $this->setting_id_prefix( 'title' ),
					'name'  => $this->setting_name_prefix( 'title' ),
					'value' => __( $step->get_title(), 'groundhogg' ),
					'title' => __( 'Step Title', 'groundhogg' ),
				);

				echo Plugin::$instance->utils->html->input( $args ); ?>

				<?php if ( Plugin::$instance->settings->is_global_multisite() ): ?>
					<!-- MULTISITE BLOG OPTION -->
					<div class="wpmu-options">
						<label style="padding-left: 30px">
							<?php _e( 'Run on which blog?', 'groundhogg' ); ?>
							<?php

							$sites = get_sites();

							$options = array();
							foreach ( $sites as $site ) {
								$options[ $site->blog_id ] = get_blog_details( $site->blog_id )->blogname;
							}

							echo Plugin::$instance->utils->html->dropdown( array(
								'id'          => $this->setting_id_prefix( 'blog_id' ),
								'name'        => $this->setting_name_prefix( 'blog_id' ),
								'options'     => $options,
								'selected'    => $step->get_meta( 'blog_id' ),
								'option_none' => __( 'Any blog', 'groundhogg' )
							) );

							?>
						</label>
					</div>
					<!-- END MULTISITE BLOG OPTION -->
				<?php endif; ?>
			</h2>
			<!-- INSIDE -->
			<div class="inside">
				<!-- SETTINGS -->
				<div class="step-edit">
					<div class="custom-settings">
						<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/before", $step ); ?>
						<?php do_action( 'groundhogg/steps/settings/before', $this ); ?>
						<?php $this->settings( $step ); ?>
						<?php do_action( "groundhogg/steps/{$this->get_type()}/settings/after", $step ); ?>
						<?php do_action( 'groundhogg/steps/settings/after', $this ); ?>
					</div>
				</div>
				<!-- REPORTING  -->
				<?php //TODO Reporting enabled?
				?>
				<!--                <div class="step-reporting -->
				<?php //echo Plugin::$instance->admin->get_page( 'funnels' )->is_reporting_enabled() ? '' : 'hidden'; ?><!--">-->
				<!--					--><?php //do_action( "groundhogg/steps/{$this->get_type()}/reporting/before", $step ); ?>
				<!--					--><?php //do_action( 'groundhogg/steps/reporting/before', $step ); ?>
				<!--					--><?php //$this->reporting( $step ); ?>
				<!--					--><?php //do_action( "groundhogg/steps/{$this->get_type()}/reporting/after", $step ); ?>
				<!--					--><?php //do_action( 'groundhogg/steps/reporting/after', $step ); ?>
				<!--                </div>-->
			</div>
		</div>
		<?php
	}

	/**
	 * Initialize the posted settings array
	 *
	 * @param $step Step
	 */
	public function pre_save( Step $step ) {
		$this->set_current_step( $step );
		$this->posted_settings = wp_unslash( $_POST['steps'][ $step->get_id() ] );

		$args = array(
			'step_title'  => sanitize_text_field( $this->get_posted_data( 'title' ) ),
			'step_order'  => $this->get_posted_order(),
			'step_status' => 'ready',
		);

		$step->update( $args );

		$step->update_meta( 'step_notes', sanitize_textarea_field( $this->get_posted_data( 'step_notes' ) ) );

		if ( $this->get_posted_data( 'blog_id', false ) ) {
			$step->update_meta( 'blog_id', absint( $this->get_posted_data( 'blog_id', false ) ) );
		} else {
			$step->delete_meta( 'blog_id' );
		}

		if ( $this->get_posted_data( 'is_active', false ) ) {
			$step->update_meta( 'is_active', 1 );
		} else {
			$step->delete_meta( 'is_active' );
		}

		if ( $this->get_posted_data( 'is_closed', false ) ) {
			$step->update_meta( 'is_closed', 1 );
		} else {
			$step->delete_meta( 'is_closed' );
		}

		$step->delete_meta( 'has_errors' );
	}

	/**
	 * Save the step based on the given ID
	 *
	 * @param $step Step
	 */
	abstract public function save( $step );

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
	}

	/**
	 * Setup args before the action/benchmark is run
	 *
	 * @param $contact Contact
	 * @param $event Event
	 *
	 * @return Contact
	 */
	public function pre_run( $contact, $event ) {
		$this->set_current_step( $event->get_step() );
		$this->set_current_contact( $contact );

		return $contact;
	}

	/**
	 * Run the action/benchmark
	 *
	 * @param $contact Contact
	 * @param $event Event
	 *
	 * @return bool
	 */
	public function run( $contact, $event ) {
		return true;
	}

	/**
	 * @param $step
	 *
	 * @return Step
	 */
	public function pre_enqueue( $step ) {
		$this->set_current_step( $step );

		return $step;
	}

	/**
	 * @param $step
	 */
	public function pre_import( $args, $step ) {
		$this->set_current_step( $step );
	}

	/**
	 * @param $args array of args
	 * @param $step Step
	 */
	public function import( $args, $step ) {
		//silence is golden
	}

	/**
	 * @param $args
	 * @param $step
	 *
	 * @return array
	 */
	public function pre_export( $args, $step ) {
		$this->set_current_step( $step );

		return $args;
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
}