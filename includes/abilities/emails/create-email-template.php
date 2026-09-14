<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use Groundhogg\Campaign;
use Groundhogg\Email;
use WP_Error;
use function Groundhogg\email_kses;
use function Groundhogg\get_sender_profiles;

/**
 * Creates a new saved Groundhogg email, mirroring the create half of the
 * POST gh/v4/emails route (Emails_Api::create_single()) - a saved email that
 * can then be sent with groundhogg/send-email-template, attached to a
 * broadcast, or used as a funnel step's email.
 *
 * Unlike Emails_Api::create_single(), there is no `force` option: this always
 * inserts a brand-new row (Base_Object::create() directly) rather than the
 * query-first-then-create-if-nothing-matches path the REST route takes when
 * `force` is omitted. An ability named "create" should always create -
 * silently handing back an unrelated pre-existing email just because its
 * subject/content happened to match would be a surprising, hard-to-debug
 * result for a caller that never asked for that behaviour.
 *
 * `message_type` is restricted to "marketing"/"transactional" - "global_block"
 * emails are reusable content fragments embedded into other emails, not
 * standalone emails, and groundhogg/list-email-templates already excludes
 * them from listings for the same reason.
 *
 * Created with status "draft" by default - only a "ready" email sends
 * cleanly through groundhogg/send-email-template, so callers should leave it
 * as a draft until the content is final, then explicitly pass status: "ready".
 *
 * `editor` controls which editor `content` is written for (see Email_Schema's
 * `editor_type`):
 * - "html" (default): `content` is plain HTML, no particular structure
 *   required. Opens in wp-admin's HTML editor.
 * - "blocks": `content` must already be in the block editor's own format -
 *   ordinary HTML with each block wrapped in a pair of matching comments
 *   carrying its type and JSON props, e.g.
 *   `<!--text:018f... {"p":{"fontSize":"16"}}-->...<!--/text:018f...-->`.
 *   There's no separate "block tree" to submit - that annotated HTML *is*
 *   the block data; wp-admin parses it back into editable blocks on open
 *   (see email-block-editor.js's parseBlocksFromContent()). Use
 *   groundhogg/list-email-templates with expand: ["content"] on an existing
 *   block-editor email to see the exact format to match, block type by
 *   block type (image, text, columns, spacer, footer, social, button, menu,
 *   ...) - there's no schema for it here since it's an internal editor
 *   format, not a public API contract.
 *
 * HTML comments are stripped unconditionally by wp_kses() (the sanitizer
 * behind Groundhogg's email_kses()) with no filter to keep them, so for
 * "blocks" content the block comments are swapped for plain-text tokens
 * before sanitizing and restored after - the same swap-sanitize-restore
 * trick email_kses() already uses internally for rgb() colors and quoted
 * font families.
 *
 * `template_settings` sets the outer page layout/background this email
 * renders inside of (see templates/email/{boxed,full_width,full_width_contained}.php
 * and the "Template Settings" panel in wp-admin's email editor) - distinct
 * from `content`/`editor`, which only cover the message body itself. All of
 * this lives in email meta (`template`, `width`, `alignment`, `direction`,
 * `backgroundColor`, etc.), read back via Email_Schema's `template_settings`.
 */
class Create_Email_Template extends Ability {

	protected const NAME       = 'groundhogg/create-email-template';
	protected const CATEGORY   = 'groundhogg-email';
	protected const CAPABILITY = 'add_emails';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Create Email Template', 'groundhogg' ),
			'description' => __( 'Create a new saved Groundhogg email (subject + HTML body). Always creates a brand-new email, never returns an existing one. Use the returned id with groundhogg/send-email-template, or attach it to a broadcast/funnel step. For a full guide to authoring block-editor content (editor: "blocks") - block types, merge tags, worked examples - and a downloadable Claude skill that packages it, see https://groundhogg.io/doc/working-with-the-abilities-api-mcp/.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'subject', 'content' ],
				'properties'           => [
					'subject' => [
						'type'        => 'string',
						'description' => __( 'The subject line. May contain merge tags like {first_name}, resolved per-recipient at send time. See groundhogg/list-replacement-codes for every code available on this site.', 'groundhogg' ),
					],
					'title' => [
						'type'        => 'string',
						'description' => __( 'The internal admin title, shown in the emails list. Defaults to the subject line when omitted.', 'groundhogg' ),
					],
					'content' => [
						'type'        => 'string',
						'description' => __( 'The email body. Sanitized with the same allowed-HTML rules as the email editor. Supports merge-field replacements, resolved per-recipient at send time - see groundhogg/list-replacement-codes for every code available. Shape depends on `editor`: plain HTML for "html", or the block editor\'s comment-annotated HTML for "blocks" - see the class description for the exact format.', 'groundhogg' ),
					],
					'editor' => [
						'type'        => 'string',
						'enum'        => [ 'html', 'blocks' ],
						'default'     => 'html',
						'description' => __( 'Which editor `content` is written for, and which one wp-admin opens the email in. "html": plain HTML, no structure required. "blocks": `content` must already be in the block editor\'s comment-annotated format - see the class description. Get an example of that format from an existing block-editor email via groundhogg/list-email-templates (expand: ["content"]).', 'groundhogg' ),
					],
					'plain_text' => [
						'type'        => 'string',
						'description' => __( 'Plain-text alternative body. Omit to fall back to a stripped-down version of the HTML content at send time.', 'groundhogg' ),
					],
					'pre_header' => [
						'type'        => 'string',
						'description' => __( 'Preview text shown next to the subject line in most inboxes.', 'groundhogg' ),
					],
					'message_type' => [
						'type'        => 'string',
						'enum'        => [ 'marketing', 'transactional' ],
						'default'     => 'marketing',
						'description' => __( 'Which sending rules and compliance footer apply. "marketing" respects unsubscribes/consent; "transactional" bypasses them.', 'groundhogg' ),
					],
					'status' => [
						'type'        => 'string',
						'enum'        => [ 'draft', 'ready' ],
						'default'     => 'draft',
						'description' => __( 'Only a "ready" email sends cleanly through groundhogg/send-email-template. Defaults to "draft".', 'groundhogg' ),
					],
					'is_template' => [
						'type'        => 'boolean',
						'default'     => false,
						'description' => __( 'True to save this as a reusable template (Emails > Templates) rather than a regular one-off email.', 'groundhogg' ),
					],
					'from_profile' => [
						'type'        => 'string',
						'default'     => 'default',
						'description' => __( 'Which configured sender to send as - see groundhogg/list-sender-profiles for valid values. Defaults to the site\'s default sender.', 'groundhogg' ),
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Existing campaign IDs to tag this email with.', 'groundhogg' ),
					],
					'template_settings' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'description'          => __( 'The outer page layout/background this email renders inside of - distinct from `content` itself. Mirrors the "Template Settings" panel in wp-admin\'s email editor. Any property left unset falls back to Groundhogg\'s own default for it.', 'groundhogg' ),
						'properties'           => Email_Schema::template_settings_properties( true ),
					],
				],
			],

			'output_schema' => Email_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		$valid_profiles = array_keys( get_sender_profiles() );
		$from_profile    = ! empty( $input['from_profile'] ) ? sanitize_text_field( $input['from_profile'] ) : 'default';

		if ( ! in_array( $from_profile, $valid_profiles, true ) ) {
			return new WP_Error(
				'groundhogg_invalid_from_profile',
				__( 'Not a valid sender profile. See groundhogg/list-sender-profiles.', 'groundhogg' )
			);
		}

		$message_type = $input['message_type'] ?? 'marketing';
		$status       = $input['status'] ?? 'draft';
		$editor       = ( $input['editor'] ?? 'html' ) === 'blocks' ? 'blocks' : 'html';

		$content = $editor === 'blocks'
			? self::kses_preserving_block_comments( $input['content'] )
			: email_kses( $input['content'] );

		$data = [
			'subject'      => sanitize_text_field( $input['subject'] ),
			'content'      => $content,
			'message_type' => in_array( $message_type, [ 'marketing', 'transactional' ], true ) ? $message_type : 'marketing',
			'status'       => in_array( $status, [ 'draft', 'ready' ], true ) ? $status : 'draft',
			'is_template'  => ! empty( $input['is_template'] ) ? 1 : 0,
			'from_profile' => $from_profile,
		];

		if ( isset( $input['title'] ) ) {
			$data['title'] = sanitize_text_field( $input['title'] );
		}

		if ( isset( $input['plain_text'] ) ) {
			$data['plain_text'] = sanitize_textarea_field( $input['plain_text'] );
		}

		if ( isset( $input['pre_header'] ) ) {
			$data['pre_header'] = sanitize_text_field( $input['pre_header'] );
		}

		$email = new Email();
		$email->create( $data );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_created', __( 'The email could not be created.', 'groundhogg' ) );
		}

		// Mark which editor this content is for via the same meta keys the block
		// editor itself writes when switching modes (Email::get_editor_type()
		// reads these to decide which editor wp-admin opens the email in).
		// Without this, wp-admin would guess based on content patterns and could
		// misdetect the result as "legacy_blocks"/"legacy_plain" and open the
		// wrong editor.
		$meta_updates = $editor === 'blocks'
			? [ 'type' => 'blocks', 'blocks' => true ]
			: [ 'type' => 'html', 'blocks' => false ];

		// Page-level layout/background - stored as its own set of meta keys,
		// read back via templates/email/{layout}.php and Email_Schema's
		// `template_settings`. Same key names the block editor itself writes
		// (camelCase for the background_* properties) so this stays consistent
		// whether the email is later opened in wp-admin or read back here.
		$template_settings = (array) ( $input['template_settings'] ?? [] );

		if ( ! empty( $template_settings['layout'] ) && in_array( $template_settings['layout'], [ 'boxed', 'full_width', 'full_width_contained' ], true ) ) {
			$meta_updates['template'] = $template_settings['layout'];
		}

		if ( isset( $template_settings['width'] ) ) {
			$meta_updates['width'] = absint( $template_settings['width'] );
		}

		if ( ! empty( $template_settings['alignment'] ) && in_array( $template_settings['alignment'], [ 'left', 'center' ], true ) ) {
			$meta_updates['alignment'] = $template_settings['alignment'];
		}

		if ( ! empty( $template_settings['direction'] ) && in_array( $template_settings['direction'], [ 'ltr', 'rtl' ], true ) ) {
			$meta_updates['direction'] = $template_settings['direction'];
		}

		if ( isset( $template_settings['background_color'] ) ) {
			$meta_updates['backgroundColor'] = sanitize_text_field( $template_settings['background_color'] );
		}

		if ( isset( $template_settings['background_image'] ) ) {
			$meta_updates['backgroundImage'] = esc_url_raw( $template_settings['background_image'] );
		}

		if ( isset( $template_settings['background_position'] ) ) {
			$meta_updates['backgroundPosition'] = sanitize_text_field( $template_settings['background_position'] );
		}

		if ( isset( $template_settings['background_size'] ) ) {
			$meta_updates['backgroundSize'] = sanitize_text_field( $template_settings['background_size'] );
		}

		if ( isset( $template_settings['background_repeat'] ) ) {
			$meta_updates['backgroundRepeat'] = sanitize_text_field( $template_settings['background_repeat'] );
		}

		$email->update_meta( $meta_updates );

		if ( ! empty( $input['campaigns'] ) ) {
			foreach ( wp_parse_id_list( $input['campaigns'] ) as $campaign_id ) {
				$email->create_relationship( new Campaign( $campaign_id ) );
			}
		}

		// Mirrors Emails_Api::do_object_created_action() so anything hooked to the
		// REST create path (cache busting, integrations, etc.) also fires here.
		do_action( 'groundhogg/api/email/created', $email );

		return Email_Schema::transform( $email, [ 'content', 'plain_text' ] );
	}

	/**
	 * email_kses() (wp_kses() underneath) strips HTML comments unconditionally -
	 * there's no allowed-tags entry or filter that keeps them, since wp_kses
	 * treats "<!--" as opaque and always discards it. Block editor content
	 * carries its block boundaries and JSON props entirely in such comments
	 * (`<!--text:uuid {...}-->...<!--/text:uuid-->`), so sanitizing it directly
	 * would silently destroy the block structure while leaving the visible HTML
	 * looking fine - a caller would only notice when the email fails to parse
	 * back into blocks in wp-admin.
	 *
	 * Swap each comment for a plain-text token first (kses only acts on tag
	 * syntax, not text nodes, so a bare token survives untouched), sanitize the
	 * rest as normal, then restore the original comments. Same trick
	 * email_kses() already applies internally for rgb() colors and quoted font
	 * families.
	 *
	 * Public so groundhogg/update-email-template can reuse it without duplicating it.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public static function kses_preserving_block_comments( string $content ): string {

		$comments = [];

		$content = preg_replace_callback(
			'/<!--.*?-->/s',
			function ( $match ) use ( &$comments ) {
				$token             = '%%GH_BLOCK_COMMENT_' . count( $comments ) . '%%';
				$comments[ $token ] = $match[0];

				return $token;
			},
			$content
		);

		$content = email_kses( $content );

		return strtr( $content, $comments );
	}
}
