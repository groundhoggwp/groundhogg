<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use Groundhogg\Email;
use WP_Error;

/**
 * Retrieves a single saved Groundhogg email by ID. Global blocks (message_type = 'global_block') are fetchable here
 * even though they're excluded from list-email-templates, since a caller who already has the id (e.g. from
 * groundhogg/query-table) has a legitimate reason to inspect one.
 *
 * An email built with the block editor is returned as an outline by default: a line per block with its id, type and
 * a summary, instead of the whole body. The body of a designed email is routinely tens of thousands of characters,
 * mostly inline styles and markup, which is more than some MCP clients will return at all - and an agent that wants
 * to change one sentence doesn't need it. With the ids, `blocks` returns just the blocks to look at, and
 * groundhogg/edit-email changes them. `view: "full"` still returns everything.
 *
 * Emails that aren't built with blocks have no outline, so their content is always returned.
 */
class Get_Email_Template extends Ability {

	protected const NAME       = 'groundhogg/get-email-template';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'view_emails';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	protected function get_args(): array {

		$schema = Email_Schema::get_schema();

		$schema['properties']['outline'] = [
			'type'        => 'array',
			'description' => __( 'For an email built with the block editor, unless view is "full": every block in order, with its id, type, the block and column it\'s in (for blocks in columns), and a summary of what\'s in it.', 'groundhogg' ),
			'items'       => self::outline_item_schema(),
		];

		$schema['properties']['blocks'] = [
			'type'                 => 'object',
			'description'          => __( 'The markup of each block asked for in `blocks`, by id: from its opening comment to its closing comment, including the blocks inside it.', 'groundhogg' ),
			'additionalProperties' => [ 'type' => 'string' ],
		];

		$schema['properties']['specs'] = [
			'type'        => 'array',
			'description' => __( 'With view "specs": the blocks as block specs, the same format groundhogg/create-email-template and groundhogg/edit-email take as `blocks`, with only the settings that aren\'t the defaults. Every top level block in order (with the blocks of a columns block in its `columns`), or the blocks asked for in `blocks`.', 'groundhogg' ),
			'items'       => [
				'type'                 => 'object',
				'additionalProperties' => true,
			],
		];

		$schema['properties']['content_length'] = [
			'type'        => 'integer',
			'description' => __( 'How many characters the full content has, when it isn\'t returned.', 'groundhogg' ),
		];

		$schema['properties']['problems'] = [
			'type'        => 'array',
			'description' => __( 'What\'s wrong with the block content, if anything. The editor may not open such an email properly, and groundhogg/edit-email can\'t change it until the whole body is replaced with groundhogg/update-email-template.', 'groundhogg' ),
			'items'       => [ 'type' => 'string' ],
		];

		return [
			'label'       => __( 'Get Email Template', 'groundhogg' ),
			'description' => __( 'Retrieve one saved Groundhogg email by ID. For an email built with the block editor this returns an outline of its blocks (id, type and a summary of each) instead of the whole body, which can be very large: pass block ids in `blocks` to get just those blocks\' markup, and change them with groundhogg/edit-email. view "specs" returns the blocks as compact block specs instead, the format groundhogg/edit-email and groundhogg/create-email-template take as `blocks`. view "full" returns the whole body and plain text. Emails that aren\'t built with blocks always include their content. Find IDs with groundhogg/list-email-templates.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => [
					'email_id' => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'view'     => [
						'type'        => 'string',
						'enum'        => [ 'outline', 'specs', 'full' ],
						'default'     => 'outline',
						'description' => __( '"outline": the email\'s settings, custom CSS and an outline of its blocks. "specs": its settings and its blocks as block specs, to change and give back to groundhogg/edit-email. "full": also its whole body and plain text - only when you really need all of it, it can be tens of thousands of characters.', 'groundhogg' ),
					],
					'blocks'   => [
						'type'        => 'array',
						'items'       => [ 'type' => 'string' ],
						'description' => __( 'Ids of blocks to return, from the outline: their markup, or with view "specs" only their specs.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => $schema,
		];
	}

	/**
	 * The schema of a block in the outline, shared with groundhogg/edit-email's output
	 *
	 * @return array
	 */
	public static function outline_item_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'id'      => [
					'type' => 'string',
				],
				'type'    => [
					'type' => 'string',
				],
				'parent'  => [
					'type'        => 'string',
					'description' => __( 'The block this one is inside of, like a columns block.', 'groundhogg' ),
				],
				'column'  => [
					'type'        => 'integer',
					'description' => __( 'Which of the parent\'s columns it\'s in, from 0.', 'groundhogg' ),
				],
				'summary' => [
					'type'        => 'string',
					'description' => __( 'The start of its text, an image\'s alt text and file name, or a button\'s text and link.', 'groundhogg' ),
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		$view   = $input['view'] ?? 'outline';
		$full   = $view === 'full';
		$specs  = $view === 'specs';
		$blocks = array_values( array_filter( (array) ( $input['blocks'] ?? [] ), 'is_string' ) );

		if ( $email->get_editor_type() !== 'blocks' ) {

			if ( ! empty( $blocks ) ) {
				return new WP_Error( 'groundhogg_email_not_blocks', __( 'This email isn\'t built with the block editor, so it has no blocks. Its content is returned without `blocks`.', 'groundhogg' ) );
			}

			return Email_Schema::transform( $email, [ 'content', 'plain_text', 'css', 'version' ] );
		}

		// the custom CSS is usually small, so it's in the outline too
		$data = Email_Schema::transform( $email, $full ? [ 'content', 'plain_text', 'css', 'version' ] : [ 'css', 'version' ] );
		$doc  = new Email_Block_Document( (string) $email->get_content() );

		$missing = array_values( array_filter( $blocks, function ( $id ) use ( $doc ) {
			return ! $doc->has( $id );
		} ) );

		if ( ! empty( $missing ) ) {
			return new WP_Error(
				'groundhogg_block_not_found',
				/* translators: %s: block ids */
				sprintf( __( 'This email has no blocks with these ids: %s. Get the ids from the outline.', 'groundhogg' ), implode( ', ', $missing ) )
			);
		}

		if ( $specs ) {

			$renderer = new Email_Block_Renderer( Email_Block_Renderer::context_of( $email ) );

			if ( empty( $blocks ) ) {
				$data['specs'] = $renderer->to_specs( (string) $email->get_content() );
			} else {
				$found         = $renderer->specs_of( (string) $email->get_content(), $blocks );
				$data['specs'] = array_values( array_filter( array_map( function ( $id ) use ( $found ) {
					return $found[ $id ] ?? null;
				}, $blocks ) ) );
			}

			$data['content_length'] = strlen( $email->get_content() );

		} else {

			if ( ! $full ) {
				$data['outline']        = $doc->outline();
				$data['content_length'] = strlen( $email->get_content() );
			}

			if ( ! empty( $blocks ) ) {

				$data['blocks'] = [];

				foreach ( $blocks as $id ) {
					$data['blocks'][ $id ] = $doc->markup( $id );
				}
			}
		}

		if ( ! $doc->is_valid() ) {
			$data['problems'] = $doc->errors();
		}

		return $data;
	}
}
