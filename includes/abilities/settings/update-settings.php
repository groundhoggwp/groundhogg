<?php

namespace Groundhogg\Abilities\Settings;

use Groundhogg\Abilities\Ability;
use Groundhogg\Plugin;
use WP_Error;

/**
 * Updates one or more settings registered via Settings::add_setting()
 * (includes/settings.php) by id - see groundhogg/list-settings to discover valid ids.
 *
 * Every value passed still goes through the setting's own sanitize_callback and enum
 * check (Settings::sanitize_registered_setting(), hooked to WP's
 * `sanitize_option_{$option}` filter) exactly as it would from wp-admin, so an invalid
 * enum choice is silently coerced to the setting's default rather than stored as-is -
 * the returned `value` for each entry is always what actually ended up in the database,
 * not necessarily what was passed in.
 *
 * Rejects the whole request up front if any id isn't registered, rather than partially
 * applying the known ones - so a typo'd id never results in a silent partial update.
 */
class Update_Settings extends Ability {

	protected const NAME       = 'groundhogg/update-settings';
	protected const CATEGORY   = 'groundhogg-settings';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Settings', 'groundhogg' ),
			'description' => __( 'Update one or more Groundhogg settings by id. See groundhogg/list-settings for valid ids, types, and allowed values. Rejects the whole request if any id is not registered.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'settings' ],
				'properties'           => [
					'settings' => [
						'type'                 => 'object',
						'additionalProperties' => true,
						'description'          => __( 'Key/value pairs to update, keyed by setting id - see groundhogg/list-settings.', 'groundhogg' ),
					],
					'reveal' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'default'     => [],
						'description' => __( 'Ids of sensitive settings (see groundhogg/list-settings\' sensitive flag) to return unredacted in the response. Only named ids are ever exposed, and only if WP_DEBUG is on or the groundhogg/settings/expose_sensitive_values filter allows it; otherwise they come back redacted even though you just set them.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'updated' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id' => [
									'type' => 'string',
								],
								'value' => [
									'description' => __( 'The value now stored, after sanitization - may differ from what was passed in. Redacted for sensitive settings unless named in `reveal` (and WP_DEBUG is on or the groundhogg/settings/expose_sensitive_values filter allows it).', 'groundhogg' ),
								],
								'changed' => [
									'type'        => 'boolean',
									'description' => __( 'False if the sanitized value was already the same as before.', 'groundhogg' ),
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$settings_registry = Plugin::$instance->settings;

		$incoming = $input['settings'] ?? [];

		if ( empty( $incoming ) || ! is_array( $incoming ) ) {
			return new WP_Error( 'groundhogg_no_changes', __( 'No settings to update were given.', 'groundhogg' ) );
		}

		$unknown = array_values( array_filter( array_keys( $incoming ), function ( $id ) use ( $settings_registry ) {
			return ! $settings_registry->is_setting_registered( $id );
		} ) );

		if ( ! empty( $unknown ) ) {
			return new WP_Error(
				'groundhogg_unknown_setting',
				sprintf(
					__( 'These settings are not registered for ability access: %s. See groundhogg/list-settings for valid ids.', 'groundhogg' ),
					implode( ', ', $unknown )
				)
			);
		}

		$reveal = array_map( function ( $id ) {
			return preg_replace( '/^gh_/', '', (string) $id );
		}, (array) ( $input['reveal'] ?? [] ) );

		$updated = [];

		foreach ( $incoming as $id => $value ) {

			$before = $settings_registry->get_option( $id );

			$settings_registry->update_option( $id, $value );

			$after = $settings_registry->get_option( $id );

			$short_id  = preg_replace( '/^gh_/', '', $id );
			$requested = in_array( $short_id, $reveal, true );

			$updated[] = [
				'id'      => $short_id,
				'value'   => $settings_registry->get_redacted_value( $id, $after, $requested ),
				'changed' => $before !== $after,
			];
		}

		return [
			'updated' => $updated,
		];
	}
}
