<?php

namespace Groundhogg\Abilities\Settings;

use Groundhogg\Abilities\Ability;
use Groundhogg\Plugin;

/**
 * Lists settings registered via Settings::add_setting() (includes/settings.php) - each
 * with its type, allowed values, description, and current stored value - so an agent can
 * discover what's available before calling groundhogg/update-settings.
 *
 * Only settings migrated to that registry are returned here. Most of Groundhogg's
 * settings still live solely in Settings_Page (admin/settings/settings-page.php), which
 * this ability doesn't read from - they simply won't appear until migrated.
 */
class List_Settings extends Ability {

	protected const NAME       = 'groundhogg/list-settings';
	protected const CATEGORY   = 'groundhogg-settings';
	protected const CAPABILITY = 'manage_options';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Settings', 'groundhogg' ),
			'description' => __( 'List Groundhogg settings available to abilities, each with its type, allowed values, description, and current value. Optionally filter by group. Use groundhogg/update-settings to change any of them.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'group' => [
						'type'        => 'string',
						'enum'        => array_keys( Plugin::$instance->settings->get_groups() ),
						'description' => __( 'Only include settings registered under this group.', 'groundhogg' ),
					],
					'reveal' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'default'     => [],
						'description' => __( 'Ids of sensitive settings to return unredacted - see the sensitive flag on each item. Only named ids are ever exposed, and only if WP_DEBUG is on or the groundhogg/settings/expose_sensitive_values filter allows it; otherwise they stay redacted regardless.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'settings' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id' => [
									'type'        => 'string',
									'description' => __( 'Pass this as the key when calling groundhogg/update-settings.', 'groundhogg' ),
								],
								'group' => [
									'type' => [ 'string', 'null' ],
								],
								'type' => [
									'type'        => 'string',
									'description' => __( 'The JSON type of the setting\'s value, e.g. string, integer, boolean, array, object.', 'groundhogg' ),
								],
								'enum' => [
									'type'        => 'array',
									'description' => __( 'Present only when the value is restricted to a fixed set of choices.', 'groundhogg' ),
								],
								'default'     => [
									'description' => __( 'The setting\'s default value, when one is defined.', 'groundhogg' ),
								],
								'description' => [
									'type'        => 'string',
									'description' => __( 'Notes when the value is redacted - see sensitive.', 'groundhogg' ),
								],
								'sensitive' => [
									'type'        => 'boolean',
									'description' => __( 'True if this is a secret (license key, API key, token, etc). Its value is redacted unless WP_DEBUG is on or the groundhogg/settings/expose_sensitive_values filter allows it.', 'groundhogg' ),
								],
								'value' => [
									'description' => __( 'The setting\'s current stored value, or a redacted placeholder when sensitive is true and the value isn\'t currently exposed.', 'groundhogg' ),
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

		$group = ! empty( $input['group'] ) ? sanitize_key( $input['group'] ) : null;

		$reveal = array_map( function ( $id ) {
			return preg_replace( '/^gh_/', '', (string) $id );
		}, (array) ( $input['reveal'] ?? [] ) );

		$settings = $settings_registry->get_settings( $group );

		$list = [];

		foreach ( $settings as $id => $args ) {

			$schema = $settings_registry->get_setting_schema( $id );

			$short_id  = preg_replace( '/^gh_/', '', $id );
			$requested = in_array( $short_id, $reveal, true );
			$value     = $settings_registry->get_redacted_value( $id, $settings_registry->get_option( $id ), $requested );

			$list[] = array_merge( $schema, [
				'id'        => $short_id,
				'group'     => $args['group'],
				'sensitive' => $settings_registry->is_setting_sensitive( $id ),
				'value'     => $value,
			] );
		}

		return [
			'settings' => $list,
		];
	}
}
