<?php

use Groundhogg\Funnel;

/**
 * Groundhogg's ability schemas must be walkable by WordPress' own validator,
 * rest_validate_value_from_schema(), which WP_Ability::execute() runs on both input
 * and output. It doesn't resolve `$ref`, and it requires a `type` on every schema it
 * visits - a missing one is a _doing_it_wrong() plus an undefined index warning, both
 * of which fail a test here.
 */
class Abilities_Schema_Tests extends GH_UnitTestCase {

	public function setUp(): void {
		parent::setUp();

		if ( ! function_exists( 'wp_get_ability' ) ) {
			$this->markTestSkipped( 'The WP Abilities API is not available.' );
		}

		wp_set_current_user( self::factory()->user->create( [ 'role' => 'administrator' ] ) );
	}

	/**
	 * Every sub-schema WordPress' validator can reach, mirroring the keywords it recurses into.
	 *
	 * @return string[] paths of the sub-schemas with no `type`
	 */
	protected function find_untyped_schemas( array $schema, string $path ): array {

		$untyped = [];

		if ( ! isset( $schema['type'] ) && ! isset( $schema['anyOf'] ) && ! isset( $schema['oneOf'] ) ) {
			$untyped[] = $path;
		}

		foreach ( [ 'properties', 'patternProperties' ] as $keyword ) {
			foreach ( $schema[ $keyword ] ?? [] as $key => $sub ) {
				$untyped = array_merge( $untyped, $this->find_untyped_schemas( $sub, "{$path}.{$keyword}.{$key}" ) );
			}
		}

		foreach ( [ 'additionalProperties', 'items' ] as $keyword ) {
			if ( isset( $schema[ $keyword ] ) && is_array( $schema[ $keyword ] ) ) {
				$untyped = array_merge( $untyped, $this->find_untyped_schemas( $schema[ $keyword ], "{$path}.{$keyword}" ) );
			}
		}

		foreach ( [ 'anyOf', 'oneOf' ] as $keyword ) {
			foreach ( $schema[ $keyword ] ?? [] as $i => $sub ) {
				// A combining schema's options inherit the parent's type
				if ( ! isset( $sub['type'] ) && isset( $schema['type'] ) ) {
					$sub['type'] = $schema['type'];
				}

				$untyped = array_merge( $untyped, $this->find_untyped_schemas( $sub, "{$path}.{$keyword}.{$i}" ) );
			}
		}

		return $untyped;
	}

	public function test_every_groundhogg_ability_schema_is_typed() {

		$abilities = array_filter( wp_get_abilities(), function ( WP_Ability $ability ) {
			return str_starts_with( $ability->get_name(), 'groundhogg/' );
		} );

		$this->assertNotEmpty( $abilities );

		$untyped = [];

		foreach ( $abilities as $ability ) {
			foreach ( [ 'input' => $ability->get_input_schema(), 'output' => $ability->get_output_schema() ] as $which => $schema ) {
				if ( ! empty( $schema ) ) {
					$untyped = array_merge( $untyped, $this->find_untyped_schemas( $schema, "{$ability->get_name()} {$which}" ) );
				}
			}
		}

		$this->assertSame( [], $untyped, 'These schemas have no `type`.' );
	}

	/**
	 * Every `$ref` in a schema, at any depth
	 *
	 * @return string[] path => ref
	 */
	protected function find_refs( array $schema, string $path ): array {

		$refs = [];

		foreach ( $schema as $key => $value ) {
			if ( $key === '$ref' && is_string( $value ) ) {
				$refs[ $path ] = $value;
			} else if ( is_array( $value ) ) {
				$refs = array_merge( $refs, $this->find_refs( $value, "{$path}.{$key}" ) );
			}
		}

		return $refs;
	}

	/**
	 * Whether a local `$ref` (a JSON pointer like #/definitions/node) points at a schema in the document
	 */
	protected function ref_resolves( array $schema, string $ref ): bool {

		if ( ! str_starts_with( $ref, '#/' ) ) {
			return false;
		}

		$target = $schema;

		foreach ( explode( '/', substr( $ref, 2 ) ) as $token ) {
			$token = str_replace( [ '~1', '~0' ], [ '/', '~' ], $token );

			if ( ! is_array( $target ) || ! array_key_exists( $token, $target ) ) {
				return false;
			}

			$target = $target[ $token ];
		}

		return is_array( $target );
	}

	/**
	 * Every `$ref` must still point at something after WordPress prepares the schema for clients - the
	 * abilities REST endpoint and the AI client both publish schemas through wp_prepare_json_schema_for_client(),
	 * which keeps only draft-04 keywords, so e.g. `$defs` is dropped while `$ref`s into it stay. The MCP adapter
	 * publishes the schemas as they are, so they must resolve there too.
	 */
	public function test_every_groundhogg_ability_schema_ref_resolves_for_clients() {

		$abilities = array_filter( wp_get_abilities(), function ( WP_Ability $ability ) {
			return str_starts_with( $ability->get_name(), 'groundhogg/' );
		} );

		$broken = [];
		$found  = 0;

		foreach ( $abilities as $ability ) {
			foreach ( [ 'input' => $ability->get_input_schema(), 'output' => $ability->get_output_schema() ] as $which => $schema ) {

				if ( empty( $schema ) ) {
					continue;
				}

				$versions = [ 'as registered' => $schema ];

				if ( function_exists( 'wp_prepare_json_schema_for_client' ) ) {
					$versions['prepared for clients'] = wp_prepare_json_schema_for_client( $schema );
				}

				foreach ( $versions as $version => $prepared ) {
					foreach ( $this->find_refs( $prepared, "{$ability->get_name()} {$which}" ) as $path => $ref ) {
						$found ++;

						if ( ! $this->ref_resolves( $prepared, $ref ) ) {
							$broken[] = "$path: $ref ($version)";
						}
					}
				}
			}
		}

		$this->assertGreaterThan( 0, $found, 'Expected some $refs to check.' );
		$this->assertSame( [], $broken, 'These $refs don\'t resolve.' );
	}

	public function test_create_flow_executes_through_abilities_api() {

		$result = wp_get_ability( 'groundhogg/create-flow' )->execute( [
			'title' => 'Schema test flow',
			'steps' => [
				[
					'type' => 'tag_applied',
				],
				[
					'type'     => 'if_else',
					'branches' => [
						'yes' => [
							[
								'type'     => 'apply_note',
								'settings' => [ 'note_text' => 'Went yes' ],
							],
						],
						'no'  => [
							[
								'type'     => 'apply_note',
								'settings' => [ 'note_text' => 'Went no' ],
							],
						],
					],
				],
			],
		] );

		$this->assertNotWPError( $result );

		$funnel = new Funnel( $result['id'] );

		$this->assertTrue( $funnel->exists() );
		$this->assertCount( 4, $funnel->get_steps() );
		$this->assertCount( 2, $result['steps'] );
		$this->assertCount( 1, $result['steps'][1]['branches']['yes'] );
	}

	public function test_create_flow_validates_top_level_step_nodes() {

		$result = wp_get_ability( 'groundhogg/create-flow' )->execute( [
			'title' => 'Schema test flow',
			'steps' => [
				[
					'type'    => 'tag_applied',
					'unknown' => true,
				],
			],
		] );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}

	public function test_create_flow_validates_nested_step_nodes_are_objects() {

		$result = wp_get_ability( 'groundhogg/create-flow' )->execute( [
			'title' => 'Schema test flow',
			'steps' => [
				[
					'type'     => 'if_else',
					'branches' => [
						'yes' => [ 'apply_note' ],
					],
				],
			],
		] );

		$this->assertWPError( $result );
		$this->assertSame( 'ability_invalid_input', $result->get_error_code() );
	}
}
