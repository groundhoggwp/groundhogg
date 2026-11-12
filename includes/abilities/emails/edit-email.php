<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email;
use WP_Error;
use function Groundhogg\check_lock;
use function Groundhogg\get_edit_version;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Changes the content of an email built with the block editor a block at a time, with a list of operations applied
 * in order, all or nothing - like groundhogg/edit-flow does for flows.
 *
 * groundhogg/update-email-template can only replace the whole body, so changing a sentence meant reading the whole
 * email (often tens of thousands of characters) and writing all of it back. Here a change only sends what changes:
 * find and replace a piece of text, or replace, add, move or delete a block by its id, which
 * groundhogg/get-email-template's outline lists.
 *
 * The operations work on the content as it is (see Email_Block_Document), so the blocks they don't touch stay
 * exactly as they were. Every change is checked the way the editor would read it, and nothing is saved if any
 * operation fails or leaves a block the editor couldn't open. The plain-text version is rebuilt from the result, as
 * the editor does when it saves.
 *
 * Like the flow abilities, it won't change an email someone else has open in the email editor, and takes the
 * `version` from groundhogg/get-email-template to make sure the email wasn't changed since it was read.
 */
class Edit_Email extends Ability {

	protected const NAME       = 'groundhogg/edit-email';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'edit_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = true;
	protected const IDEMPOTENT  = false;

	const OPERATIONS = [ 'replace_text', 'set_content', 'set_props', 'add', 'replace', 'move', 'delete' ];

	protected function get_args(): array {

		$block_schema = [
			'type'        => 'string',
			'description' => __( 'A block id, from the outline groundhogg/get-email-template returns.', 'groundhogg' ),
		];

		return [
			'label'       => __( 'Edit Email', 'groundhogg' ),
			'description' => __( 'Change an email built with the block editor a block at a time, instead of rewriting the whole body with groundhogg/update-email-template: find and replace text, or change, add, move and delete blocks by id. Get the block ids from groundhogg/get-email-template (its outline), and read only the blocks you need to change with its `blocks` input. Operations are applied in order, all or nothing, and every change is checked so the email still opens in the editor. Use groundhogg/update-email-template for the subject, sender, status and other settings.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id', 'operations' ],
				'properties'           => [
					'email_id'         => [
						'type'    => 'integer',
						'minimum' => 1,
					],
					'expected_version' => [
						'type'        => 'string',
						'description' => __( 'The version groundhogg/get-email-template returned. If the email was changed since, nothing is changed and an error is returned, so read it again and retry.', 'groundhogg' ),
					],
					'dry_run'          => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'Check the operations and describe the result without saving it.', 'groundhogg' ),
					],
					'operations'       => [
						'type'     => 'array',
						'minItems' => 1,
						'items'    => [
							'type'                 => 'object',
							'additionalProperties' => false,
							'required'             => [ 'op' ],
							'properties'           => [
								'op'      => [
									'type'        => 'string',
									'enum'        => self::OPERATIONS,
									'description' => __( '"replace_text": `find` and `replace`, optionally only in `block`; the cheapest way to change wording or a link. It must match exactly once unless `all` is true. It matches the HTML as written, so text split by tags like <strong> won\'t match. Matching text in the block settings (like a button\'s) is changed too. "set_content": `block` and `html`, the new HTML inside the block\'s <td>; the <td> (padding, background) and settings stay. "set_props": `block` and `props`, changes only the given settings in the block\'s comment, null removes one; the email\'s HTML isn\'t rebuilt from them, so this is for settings used when sending (like include_filters or a global block\'s templateId) or only when editing. "add": `blocks` (specs) or `html` with one or more new blocks, and `at`. "replace": `block`, and `blocks` or `html` with the blocks to put in its place; ids of the blocks being replaced can be reused. "move": `block` and `at`, it moves with the blocks inside it. "delete": `block`, and the blocks inside it.', 'groundhogg' ),
								],
								'block'   => $block_schema,
								'find'    => [
									'type' => 'string',
								],
								'replace' => [
									'type' => 'string',
								],
								'all'     => [
									'type'        => 'boolean',
									'default'     => false,
									'description' => __( 'For "replace_text": replace every match instead of needing exactly one.', 'groundhogg' ),
								],
								'blocks'  => [
									'type'        => 'array',
									'items'       => [
										'type'                 => 'object',
										'required'             => [ 'type' ],
										'additionalProperties' => true,
									],
									'description' => __( 'For "add" and "replace", instead of `html`: the new blocks as block specs, like in groundhogg/create-email-template, which writes their markup. To change a block\'s settings, get its spec from groundhogg/get-email-template (view "specs"), change it, and "replace" the block with it, keeping its id.', 'groundhogg' ),
								],
								'html'    => [
									'type'        => 'string',
									'description' => __( 'For "add" and "replace", instead of `blocks`: block markup in the same format as the email\'s content, one or more blocks, each <!--type:id {...}--><td id="b-{id}">...</td><!--/type:id-->, with or without the <tr> around it. New ids must not be used by other blocks in the email. For "set_content": the HTML inside the block\'s <td>, without block comments.', 'groundhogg' ),
								],
								'props'   => [
									'type'                 => 'object',
									'additionalProperties' => true,
									'description'          => __( 'For "set_props": the settings to change.', 'groundhogg' ),
								],
								'at'      => [
									'type'                 => 'object',
									'additionalProperties' => false,
									'description'          => __( 'For "add" and "move", where to put the blocks. Exactly one of: {"after": block}, {"before": block}, {"in": block, "column": n, "position": "start"|"end"} for column n (from 0) of a columns block, or {"position": "start"|"end"} of the email.', 'groundhogg' ),
									'properties'           => [
										'after'    => $block_schema,
										'before'   => $block_schema,
										'in'       => $block_schema,
										'column'   => [
											'type'    => 'integer',
											'minimum' => 0,
											'default' => 0,
										],
										'position' => [
											'type'    => 'string',
											'enum'    => [ 'start', 'end' ],
											'default' => 'end',
										],
									],
								],
							],
						],
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'email_id' => [
						'type' => 'integer',
					],
					'dry_run'  => [
						'type' => 'boolean',
					],
					'version'  => [
						'type'        => 'string',
						'description' => __( 'The email\'s version after the change, to give as expected_version next time. Unchanged on a dry run.', 'groundhogg' ),
					],
					'changed'  => [
						'type'        => 'array',
						'description' => __( 'The blocks added or changed, in their new order, as in groundhogg/get-email-template\'s outline.', 'groundhogg' ),
						'items'       => Get_Email_Template::outline_item_schema(),
					],
					'deleted'  => [
						'type'        => 'array',
						'description' => __( 'The ids of the blocks deleted.', 'groundhogg' ),
						'items'       => [
							'type' => 'string',
						],
					],
					'blocks'   => [
						'type'        => 'integer',
						'description' => __( 'How many blocks the email has now.', 'groundhogg' ),
					],
					'warnings' => [
						'type'  => 'array',
						'items' => [
							'type' => 'string',
						],
					],
				],
			],
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( $email->get_editor_type() !== 'blocks' ) {
			return new WP_Error(
				'groundhogg_email_not_blocks',
				__( 'This email isn\'t built with the block editor, so it has no blocks to edit. Change its content with groundhogg/update-email-template.', 'groundhogg' )
			);
		}

		$locked_by = check_lock( $email );

		if ( $locked_by ) {
			return new WP_Error( 'groundhogg_email_locked', sprintf(
			/* translators: %s: the name of the user editing the email */
				__( '%s is editing this email in the email editor, so it can\'t be changed until they\'re done.', 'groundhogg' ),
				get_userdata( $locked_by )->display_name
			) );
		}

		if ( ! empty( $input['expected_version'] ) && get_edit_version( $email ) !== $input['expected_version'] ) {
			return new WP_Error( 'groundhogg_email_changed', __( 'The email was changed since it was read. Read it again with groundhogg/get-email-template and retry.', 'groundhogg' ) );
		}

		$doc = new Email_Block_Document( (string) $email->get_content() );

		if ( ! $doc->is_valid() ) {
			return Email_Block_Document::error_from( $doc->errors(), __( 'The email\'s content has problems that have to be fixed first, by replacing the whole body with groundhogg/update-email-template:', 'groundhogg' ) );
		}

		$before  = $doc->ids();
		$touched = [];
		$context = Email_Block_Renderer::context_of( $email );

		foreach ( array_values( (array) $input['operations'] ) as $i => $operation ) {

			$operation = $this->render_blocks( $doc, (array) $operation, $context );

			$result = is_wp_error( $operation ) ? $operation : $this->apply( $doc, $operation );

			if ( is_wp_error( $result ) ) {
				return new WP_Error(
					$result->get_error_code(),
					/* translators: 1: the operation number, 2: the operation, 3: the error */
					sprintf( __( 'Operation %1$d ("%2$s") failed, so nothing was changed: %3$s', 'groundhogg' ), $i + 1, $operation['op'] ?? '', $result->get_error_message() ),
					$result->get_error_data()
				);
			}

			$touched = array_merge( $touched, $result );
		}

		$doc->normalize();

		$sanitized = $doc->sanitize();

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$after   = $doc->ids();
		$touched = array_intersect( $after, $touched );
		$outline = array_column( $doc->outline(), null, 'id' );
		$dry_run = ! empty( $input['dry_run'] );

		if ( ! $dry_run ) {

			// like the editor, which makes them again whenever it saves
			$made = ( new Email_Block_Renderer( $context ) )->css_and_plain_text( $doc->html() );

			$email->update( [
				'content'      => $doc->html(),
				'plain_text'   => $made['plain_text'],
				'last_updated' => current_time( 'mysql' ),
			] );

			$email->update_meta( 'css', $made['css'] );

			// Mirrors Emails_Api::do_object_updated_action(), like groundhogg/update-email-template
			do_action( 'groundhogg/api/email/updated', $email );
		}

		$output = [
			'email_id' => $email->get_id(),
			'dry_run'  => $dry_run,
			'version'  => get_edit_version( $email ),
			'changed'  => array_values( array_map( function ( $id ) use ( $outline ) {
				return $outline[ $id ];
			}, $touched ) ),
			'deleted'  => array_values( array_diff( $before, $after ) ),
			'blocks'   => count( $after ),
		];

		if ( ! empty( $doc->warnings() ) ) {
			$output['warnings'] = $doc->warnings();
		}

		return $output;
	}

	/**
	 * For "add" and "replace" with `blocks`, the block specs written as markup in `html`
	 *
	 * @param Email_Block_Document $doc
	 * @param array                $operation
	 * @param array                $context the email's template settings
	 *
	 * @return array|WP_Error the operation
	 */
	protected function render_blocks( Email_Block_Document $doc, array $operation, array $context ) {

		$op = $operation['op'] ?? '';

		if ( ! isset( $operation['blocks'] ) || ! in_array( $op, [ 'add', 'replace' ], true ) ) {
			return $operation;
		}

		if ( isset( $operation['html'] ) ) {
			return new WP_Error( 'groundhogg_blocks_and_html', __( 'Give the new blocks as `blocks` or as `html`, not both.', 'groundhogg' ) );
		}

		$used      = $doc->ids();
		$top_level = true;

		if ( $op === 'replace' ) {

			$block = $operation['block'] ?? null;

			if ( ! is_string( $block ) || ! $doc->has( $block ) ) {
				/* translators: %s: block id */
				return new WP_Error( 'groundhogg_block_not_found', sprintf( __( 'There\'s no block "%s".', 'groundhogg' ), is_scalar( $block ) ? $block : '' ) );
			}

			// the ids of the blocks being replaced can be used again
			$used      = array_diff( $used, array_merge( [ $block ], $doc->descendants( $block ) ) );
			$top_level = $doc->get( $block )['parent'] === null;

		} else {

			$at = (array) ( $operation['at'] ?? [] );

			if ( isset( $at['in'] ) ) {
				$top_level = false;
			} else {
				foreach ( [ 'after', 'before' ] as $where ) {
					if ( isset( $at[ $where ] ) && is_string( $at[ $where ] ) && $doc->has( $at[ $where ] ) ) {
						$top_level = $doc->get( $at[ $where ] )['parent'] === null;
					}
				}
			}
		}

		$rows = ( new Email_Block_Renderer( $context, $used ) )->render_rows( (array) $operation['blocks'], $top_level );

		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		unset( $operation['blocks'] );
		$operation['html'] = $rows['html'];

		return $operation;
	}

	/**
	 * Apply one operation
	 *
	 * @param Email_Block_Document $doc
	 * @param array                $operation
	 *
	 * @return string[]|WP_Error the ids of the blocks added or changed
	 */
	protected function apply( Email_Block_Document $doc, array $operation ) {

		$op    = $operation['op'] ?? '';
		$block = $operation['block'] ?? null;

		foreach ( self::required( $op ) as $field ) {
			if ( ! isset( $operation[ $field ] ) ) {
				/* translators: 1: the field, 2: the operation */
				return new WP_Error( 'groundhogg_missing_field', sprintf( __( '"%2$s" needs `%1$s`.', 'groundhogg' ), $field, $op ) );
			}
		}

		if ( isset( $operation['at'] ) ) {

			$at   = (array) $operation['at'];
			$refs = count( array_intersect( array_keys( $at ), [ 'after', 'before', 'in' ] ) );

			if ( $refs > 1 || ( $refs === 1 && isset( $at['position'] ) && ! isset( $at['in'] ) ) ) {
				return new WP_Error( 'groundhogg_invalid_position', __( '`at` takes exactly one of {"after": block}, {"before": block}, {"in": block, "column": n, "position": "start"|"end"} or {"position": "start"|"end"}.', 'groundhogg' ) );
			}
		}

		switch ( $op ) {

			case 'replace_text':
				$result = $doc->replace_text( $operation['find'], $operation['replace'], $block, ! empty( $operation['all'] ) );

				return is_wp_error( $result ) ? $result : $result['blocks'];

			case 'set_content':
				$result = $doc->set_content( $block, $operation['html'] );

				return is_wp_error( $result ) ? $result : [ $block ];

			case 'set_props':
				$result = $doc->set_props( $block, $operation['props'] );

				return is_wp_error( $result ) ? $result : [ $block ];

			case 'add':
				return $doc->insert( $operation['html'], (array) $operation['at'] );

			case 'replace':
				$result = $doc->replace( $block, $operation['html'] );

				return is_wp_error( $result ) ? $result : $result['added'];

			case 'move':
				$result = $doc->move( $block, (array) $operation['at'] );

				return is_wp_error( $result ) ? $result : [ $block ];

			case 'delete':
				$result = $doc->delete( $block );

				return is_wp_error( $result ) ? $result : [];
		}

		/* translators: %s: the operation */
		return new WP_Error( 'groundhogg_invalid_operation', sprintf( __( '"%s" isn\'t an operation.', 'groundhogg' ), $op ) );
	}

	/**
	 * The fields an operation needs
	 *
	 * @param string $op
	 *
	 * @return string[]
	 */
	protected static function required( string $op ): array {

		switch ( $op ) {
			case 'replace_text':
				return [ 'find', 'replace' ];
			case 'set_content':
			case 'replace':
				return [ 'block', 'html' ];
			case 'set_props':
				return [ 'block', 'props' ];
			case 'add':
				return [ 'html', 'at' ];
			// `blocks` is written as `html` before the fields are checked, see render_blocks()
			case 'move':
				return [ 'block', 'at' ];
			case 'delete':
				return [ 'block' ];
		}

		return [];
	}
}
