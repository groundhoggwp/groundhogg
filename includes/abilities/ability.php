<?php

namespace Groundhogg\Abilities;

abstract class Ability {

	protected const string NAME       = '';
	protected const string CATEGORY   = '';
	protected const string CAPABILITY = '';

	protected const bool PUBLIC      = true;
	protected const bool READONLY    = false;
	protected const bool DESTRUCTIVE = false;
	protected const bool IDEMPOTENT  = false;

	/**
	 * Register the ability.
	 *
	 * @return void
	 */
	public function __construct() {

		$args = $this->get_args();

		$args['category'] = static::CATEGORY;

		$args['execute_callback'] = $this;

		$args['permission_callback'] ??= [ $this, 'can_execute' ];

		$args['meta'] = array_merge(
			[
				'public' => static::PUBLIC,
			],
			$args['meta'] ?? []
		);

		$args['meta']['annotations'] = array_merge(
			[
				'readonly'    => static::READONLY,
				'destructive' => static::DESTRUCTIVE,
				'idempotent'  => static::IDEMPOTENT,
			],
			$args['meta']['annotations'] ?? []
		);

		wp_register_ability(
			static::NAME,
			$args
		);
	}

	/**
	 * Ability registration arguments.
	 *
	 * At minimum this should usually provide:
	 *
	 * - label
	 * - description
	 * - input_schema
	 * - output_schema
	 *
	 * @return array
	 */
	abstract protected function get_args(): array;

	/**
	 * Execute the ability.
	 *
	 * @param mixed $input
	 *
	 * @return mixed
	 */
	abstract public function __invoke( $input );

	/**
	 * Check whether the current user can execute the ability.
	 *
	 * @param mixed $input
	 *
	 * @return bool|\WP_Error
	 */
	public function can_execute( $input = null ) {

		if ( ! static::CAPABILITY ) {
			return true;
		}

		return current_user_can( static::CAPABILITY );
	}
}
