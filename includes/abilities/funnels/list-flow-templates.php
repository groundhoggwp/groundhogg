<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use Groundhogg\Plugin;

/**
 * Lists the ready-made flows from the template library - the same ones the Add Flow screen shows (see
 * Library::get_available_funnel_templates()), so only templates whose step types are all registered on this site.
 * groundhogg/import-flow-template turns one into a flow.
 *
 * The library is remote, so this makes a request (cached for a day, see Library::request()).
 */
class List_Flow_Templates extends Ability {

	protected const NAME       = 'groundhogg/list-flow-templates';
	protected const CATEGORY   = 'groundhogg-funnels';
	protected const CAPABILITY = 'add_funnels';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'List Flow Templates', 'groundhogg' ),
			'description' => __( 'List the ready-made flow templates this site can use, with their steps, like the Add Flow screen. Starting from a template that\'s close to what\'s wanted, then changing it with groundhogg/edit-flow, is often quicker than building from scratch. Import one with groundhogg/import-flow-template.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'properties'           => [
					'search' => [
						'type'        => 'string',
						'description' => __( 'Only templates whose title or description contains this.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'templates' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'id'          => [
									'type'        => 'integer',
									'description' => __( 'Pass this to groundhogg/import-flow-template.', 'groundhogg' ),
								],
								'title'       => [ 'type' => 'string' ],
								'description' => [ 'type' => 'string' ],
								'campaigns'   => [
									'type'  => 'array',
									'items' => [ 'type' => 'string' ],
								],
								'premium'     => [
									'type'        => 'boolean',
									'description' => __( 'Uses paid features this site doesn\'t have, so it can\'t be imported.', 'groundhogg' ),
								],
								'steps'       => [
									'type'        => 'array',
									'description' => __( 'Every step, in order. Steps in branches follow the step they branch from.', 'groundhogg' ),
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'type'  => [ 'type' => 'string' ],
											'group' => [ 'type' => 'string' ],
											'title' => [ 'type' => 'string' ],
										],
									],
								],
							],
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$search    = strtolower( trim( (string) ( $input['search'] ?? '' ) ) );
		$templates = [];

		foreach ( Plugin::instance()->library->get_available_funnel_templates() as $template ) {

			/* @var $template Funnel */

			$description = trim( wp_strip_all_tags( (string) $template->get_meta( 'description' ) ) );

			if ( $search && ! str_contains( strtolower( $template->get_title() . ' ' . $description ), $search ) ) {
				continue;
			}

			$steps = array_map( function ( $step ) {
				return (object) ( (object) $step )->data;
			}, $template->steps );

			usort( $steps, function ( $a, $b ) {
				return absint( $a->step_order ?? 0 ) <=> absint( $b->step_order ?? 0 );
			} );

			$templates[] = [
				'id'          => absint( $template->ID ),
				'title'       => $template->get_title(),
				'description' => $description,
				'campaigns'   => array_values( array_map( function ( $campaign ) {
					return (string) ( (object) ( (object) $campaign )->data )->name;
				}, $template->campaigns ) ),
				'premium'     => (bool) $template->is_premium,
				'steps'       => array_map( function ( $step ) {
					return [
						'type'  => (string) ( $step->step_type ?? '' ),
						'group' => (string) ( $step->step_group ?? '' ),
						'title' => wp_strip_all_tags( (string) ( $step->step_title ?? '' ) ),
					];
				}, $steps ),
			];
		}

		return [
			'templates' => $templates,
		];
	}
}
