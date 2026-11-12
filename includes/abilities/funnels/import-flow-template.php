<?php

namespace Groundhogg\Abilities\Funnels;

use Groundhogg\Abilities\Ability;
use Groundhogg\Funnel;
use Groundhogg\Plugin;
use WP_Error;

/**
 * Creates a flow from a template in the library, the same as "Use Template" on the Add Flow screen
 * (Funnels_Page::process_add() → Funnel::import()). The flow is created inactive, and the emails its send email steps
 * use are copied into the site's emails.
 */
class Import_Flow_Template extends Ability {

	protected const NAME     = 'groundhogg/import-flow-template';
	protected const CATEGORY = 'groundhogg-funnels';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Import Flow Template', 'groundhogg' ),
			'description' => __( 'Create a flow from a template, see groundhogg/list-flow-templates. The flow is created inactive, and its emails are copied into the site\'s emails. Templates carry the library\'s own placeholder content: form consent text naming another business, success pages and links on other sites, generic email copy. Look through it with groundhogg/get-flow and replace that with the site\'s own using groundhogg/edit-flow and the email abilities before it\'s turned on.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'template_id' ],
				'properties'           => [
					'template_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'title'       => [
						'type'        => 'string',
						'description' => __( 'A title for the new flow. Defaults to the template\'s.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'flow_id'    => [ 'type' => 'integer' ],
					'title'      => [ 'type' => 'string' ],
					'status'     => [ 'type' => 'string' ],
					'admin_link' => [
						'type'        => 'string',
						'description' => __( 'Where a person edits the flow.', 'groundhogg' ),
					],
				],
			],
		];
	}

	/**
	 * The Add Flow screen needs both
	 *
	 * @param mixed $input
	 *
	 * @return bool
	 */
	public function can_execute( $input = null ) {
		return current_user_can( 'add_funnels' ) && current_user_can( 'import_funnels' );
	}

	public function __invoke( $input ) {

		$id = absint( $input['template_id'] );

		// only the ones the Add Flow screen offers, so not a template with step types this site doesn't have
		$available = array_filter( Plugin::instance()->library->get_available_funnel_templates(), function ( Funnel $template ) use ( $id ) {
			return absint( $template->ID ) === $id;
		} );

		if ( empty( $available ) ) {
			return new WP_Error( 'groundhogg_template_not_found', __( 'There is no template with that ID that this site can use. See groundhogg/list-flow-templates.', 'groundhogg' ) );
		}

		if ( reset( $available )->is_premium ) {
			return new WP_Error( 'groundhogg_template_premium', __( 'That template uses paid features this site doesn\'t have.', 'groundhogg' ) );
		}

		$template = Plugin::instance()->library->get_funnel_template( $id );

		if ( empty( $template ) || is_wp_error( $template ) ) {
			return new WP_Error( 'groundhogg_template_not_found', __( 'The template could not be downloaded from the library.', 'groundhogg' ) );
		}

		$funnel    = new Funnel();
		$funnel_id = $funnel->import( $template );

		if ( is_wp_error( $funnel_id ) ) {
			return $funnel_id;
		}

		$funnel = new Funnel( $funnel_id );

		if ( ! $funnel->exists() ) {
			return new WP_Error( 'groundhogg_flow_not_created', __( 'The flow could not be created from the template.', 'groundhogg' ) );
		}

		if ( ! empty( $input['title'] ) ) {
			$funnel->update( [ 'title' => sanitize_text_field( $input['title'] ) ] );
		}

		return [
			'flow_id'    => $funnel->get_id(),
			'title'      => $funnel->get_title(),
			'status'     => $funnel->get_status(),
			'admin_link' => $funnel->admin_link(),
		];
	}
}
