<?php
namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Groundhogg autoloader.
 *
 * Groundhogg autoloader handler class is responsible for loading the different
 * classes needed to run the plugin.
 *
 * @since 1.6.0
 */
class Autoloader {

	const ALIASES_DEPRECATION_RANGE = 0.2;

	/**
	 * Classes map.
	 *
	 * Maps Groundhogg classes to file names.
	 *
	 * @since 1.6.0
	 * @access private
	 * @static
	 *
	 * @var array Classes used by groundhogg.
	 */
	private static $classes_map = [
		'Notices'       => 'includes/notices.php',
		'Scripts'       => 'includes/scripts.php',
		'Preferences'   => 'includes/preferences.php',
		'Tracking'      => 'includes/tracking.php',
		'Settings'      => 'includes/settings.php',
		'Bulk_Job'      => 'includes/bulk-jobs/bulk-job.php',
		'HTML'          => 'includes/utils/html.php',
		'Utils'         => 'includes/utils/utils.php',
		'Files'         => 'includes/utils/files.php',
		'Location'      => 'includes/utils/location.php',
		'Updater'       => 'includes/utils/updater.php',
		'Date_Time'     => 'includes/utils/date-time.php',
		'Compliance'    => 'includes/compliance.php',
		'Supports_Errors' => 'includes/supports-errors.php',
		'Base_Object'   => 'includes/classes/base-object.php',
		'Base_Object_With_Meta' => 'includes/classes/base-object-with-meta.php',
		'Contact'       => 'includes/classes/contact.php',
	];

	/**
	 * Classes aliases.
	 *
	 * Maps Groundhogg classes to aliases.
	 *
	 * @since 1.6.0
	 * @access private
	 * @static
	 *
	 * @var array Classes aliases.
	 */
	private static $classes_aliases = [
		
	];

	/**
	 * Run autoloader.
	 *
	 * Register a function as `__autoload()` implementation.
	 *
	 * @since 1.6.0
	 * @access public
	 * @static
	 */
	public static function run() {
		spl_autoload_register( [ __CLASS__, 'autoload' ] );
	}

	/**
	 * Get classes aliases.
	 *
	 * Retrieve the classes aliases names.
	 *
	 * @since 1.6.0
	 * @access public
	 * @static
	 *
	 * @return array Classes aliases.
	 */
	public static function get_classes_aliases() {
		return self::$classes_aliases;
	}

	/**
	 * Load class.
	 *
	 * For a given class name, require the class file.
	 *
	 * @since 1.6.0
	 * @access private
	 * @static
	 *
	 * @param string $relative_class_name Class name.
	 */
	private static function load_class( $relative_class_name ) {
		if ( isset( self::$classes_map[ $relative_class_name ] ) ) {
			$filename = GROUNDHOGG_PATH . '/' . self::$classes_map[ $relative_class_name ];
		} else {
			$filename = strtolower(
				preg_replace(
					[ '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
					[ '$1-$2', '-', DIRECTORY_SEPARATOR ],
					$relative_class_name
				)
			);

//			var_dump( $filename );

			$filename = GROUNDHOGG_PATH . $filename . '.php';
		}

		if ( is_readable( $filename ) ) {
			require $filename;
		}
	}

	/**
	 * Autoload.
	 *
	 * For a given class, check if it exist and load it.
	 *
	 * @since 1.6.0
	 * @access private
	 * @static
	 *
	 * @param string $class Class name.
	 */
	private static function autoload( $class ) {
		if ( 0 !== strpos( $class, __NAMESPACE__ . '\\' ) ) {
			return;
		}

		$relative_class_name = preg_replace( '/^' . __NAMESPACE__ . '\\\/', '', $class );

		$has_class_alias = isset( self::$classes_aliases[ $relative_class_name ] );

		// Backward Compatibility: Save old class name for set an alias after the new class is loaded
		if ( $has_class_alias ) {
			$alias_data = self::$classes_aliases[ $relative_class_name ];

			$relative_class_name = $alias_data['replacement'];
		}

		$final_class_name = __NAMESPACE__ . '\\' . $relative_class_name;

		if ( ! class_exists( $final_class_name ) ) {
			self::load_class( $relative_class_name );
		}

		if ( $has_class_alias ) {
			class_alias( $final_class_name, $class );

			preg_match( '/^[0-9]+\.[0-9]+/', GROUNDHOGG_VERSION, $current_version_as_float );

			$current_version_as_float = (float) $current_version_as_float[0];

			preg_match( '/^[0-9]+\.[0-9]+/', $alias_data['version'], $alias_version_as_float );

			$alias_version_as_float = (float) $alias_version_as_float[0];

			if ( $current_version_as_float - $alias_version_as_float >= self::ALIASES_DEPRECATION_RANGE ) {
				_deprecated_file( $class, $alias_data['version'], $final_class_name );
			}
		}
	}
}
