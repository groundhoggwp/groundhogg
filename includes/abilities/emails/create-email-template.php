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
 * The body can be given as `blocks`, compact JSON block specs that
 * Email_Block_Renderer writes as the block editor's markup, its block CSS and
 * its plain text. Otherwise it's `content`, and `editor` controls which editor
 * it's written for (see Email_Schema's `editor_type`):
 * - "html" (default): `content` is plain HTML, no particular structure
 *   required. Opens in wp-admin's HTML editor.
 * - "blocks": `content` must already be in the block editor's own format -
 *   ordinary HTML with each block wrapped in a pair of matching comments
 *   carrying its type and JSON props, e.g.
 *   `<!--text:018f... {"p":{"fontSize":"16"}}-->...<!--/text:018f...-->`.
 *   There's no separate "block tree" to submit - that annotated HTML *is*
 *   the block data; wp-admin parses it back into editable blocks on open
 *   (see email-block-editor.js's parseBlocksFromContent()). The format, block
 *   type by block type, is in the authoring guide (groundhogg/get-authoring-guide,
 *   topic "email"). Block content is checked with Email_Block_Document before
 *   anything is saved, so content the editor would silently get wrong (invalid
 *   JSON settings, a missing <td id="b-{id}">, a block outside its own row...)
 *   is refused with what's wrong, and its plain-text version is made the way
 *   the editor makes it - see prepare_content().
 *
 * wp_kses() (the sanitizer behind Groundhogg's email_kses()) sanitizes what's
 * inside HTML comments too, which can mangle the JSON settings in block
 * comments, so for "blocks" content the block comments are swapped for
 * plain-text tokens before sanitizing and restored after - the same
 * swap-sanitize-restore trick email_kses() already uses internally for rgb()
 * colors and quoted font families.
 *
 * The content isn't returned: the caller just wrote it, and it's often tens of
 * thousands of characters. The block count and the email's version come back
 * instead - see output().
 *
 * `css`, `custom_headers` and `utm` are the email's custom CSS (`template_css`), its extra
 * headers and its UTM parameters - see extra_meta_from_input().
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
			'description' => __( 'Create a new saved Groundhogg email (subject + body). Always creates a brand-new email, never returns an existing one. Use the returned id with groundhogg/send-email-template, or attach it to a broadcast/funnel step. For an email people can keep editing in the drag-and-drop editor, describe it as `blocks` (JSON block specs, like [{"type": "heading", "content": "Hi {first}"}, {"type": "button", "text": "Shop", "link": "https://..."}]) and the markup is written for you; get the props of each block type from groundhogg/get-authoring-guide (topic "email") first. Otherwise give `content` as HTML. The content isn\'t returned; to change it afterwards, use groundhogg/edit-email to change blocks one at a time instead of rewriting it all.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'subject' ],
				'properties'           => [
					'subject' => [
						'type'        => 'string',
						'description' => __( 'The subject line. May contain merge tags like {first_name}, resolved per-recipient at send time. See groundhogg/list-replacement-codes for every code available on this site.', 'groundhogg' ),
					],
					'title' => [
						'type'        => 'string',
						'description' => __( 'The internal admin title, shown in the emails list. Defaults to the subject line when omitted.', 'groundhogg' ),
					],
					'blocks' => self::blocks_input(),
					'content' => [
						'type'        => 'string',
						'description' => __( 'The email body as HTML, instead of `blocks`. Sanitized with the same allowed-HTML rules as the email editor. Supports merge-field replacements, resolved per-recipient at send time - see groundhogg/list-replacement-codes for every code available. Shape depends on `editor`: plain HTML for "html", or the block editor\'s comment-annotated HTML for "blocks" - see the class description for the exact format.', 'groundhogg' ),
					],
					'editor' => [
						'type'        => 'string',
						'enum'        => [ 'html', 'blocks' ],
						'default'     => 'html',
						'description' => __( 'Which editor `content` is written for, and which one wp-admin opens the email in. "html": plain HTML, no structure required. "blocks": `content` must be in the block editor\'s comment-annotated format; it\'s checked before saving, and refused with what\'s wrong if the editor couldn\'t open it. Not needed with `blocks`, which are always for the block editor.', 'groundhogg' ),
					],
					'plain_text' => [
						'type'        => 'string',
						'description' => __( 'Plain-text alternative body. Omit it to have one made from the content, like the email editor does.', 'groundhogg' ),
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
					'css' => [
						'type'        => 'string',
						'description' => __( 'Custom CSS for the email, added to its stylesheet - e.g. media queries or classes that your content uses. Only for emails built with the block editor (editor: "blocks"); for "html" emails put a <style> element in the content instead. Sanitized like the email editor\'s custom CSS.', 'groundhogg' ),
					],
					'custom_headers' => Email_Schema::custom_headers_input(),
					'utm' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'description'          => __( 'UTM parameters to add to the links in this email when it is sent. Empty values are ignored. Same as the Advanced > UTM Parameters panel in the email editor. Replacement codes are not supported.', 'groundhogg' ),
						'properties'           => Email_Schema::utm_properties(),
					],
				],
			],

			'output_schema' => self::output_schema(),
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

		$editor = self::editor_from_input( $input );

		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		if ( ! $editor ) {
			return new WP_Error( 'groundhogg_missing_content', __( 'Give the email\'s body as `blocks` or as `content`.', 'groundhogg' ) );
		}

		// Checked before anything is created, so a bad input doesn't leave a half-made email behind
		$extra_meta = self::extra_meta_from_input( $input, $editor );

		if ( is_wp_error( $extra_meta ) ) {
			return $extra_meta;
		}

		$template_settings = (array) ( $input['template_settings'] ?? [] );

		$renderer = new Email_Block_Renderer( [
			'template'     => $template_settings['layout'] ?? 'boxed',
			'width'        => $template_settings['width'] ?? 640,
			'message_type' => $message_type,
		] );

		$body = self::prepare_body( $input, $editor, $renderer );

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		$data = [
			'subject'      => sanitize_text_field( $input['subject'] ),
			'content'      => $body['content'],
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
		} else if ( $body['plain_text'] !== '' ) {
			$data['plain_text'] = $body['plain_text'];
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
			? [ 'type' => 'blocks', 'blocks' => true, 'css' => $body['css'] ]
			: [ 'type' => 'html', 'blocks' => false ];

		// Page-level layout/background - stored as its own set of meta keys,
		// read back via templates/email/{layout}.php and Email_Schema's
		// `template_settings`. Same key names the block editor itself writes
		// (camelCase for the background_* properties) so this stays consistent
		// whether the email is later opened in wp-admin or read back here.
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

		$email->update_meta( array_merge( $meta_updates, $extra_meta ) );

		if ( ! empty( $input['campaigns'] ) ) {
			foreach ( wp_parse_id_list( $input['campaigns'] ) as $campaign_id ) {
				$email->create_relationship( new Campaign( $campaign_id ) );
			}
		}

		// Mirrors Emails_Api::do_object_created_action() so anything hooked to the
		// REST create path (cache busting, integrations, etc.) also fires here.
		do_action( 'groundhogg/api/email/created', $email );

		return self::output( $email, $body );
	}

	/**
	 * The output of groundhogg/create-email-template and groundhogg/update-email-template
	 *
	 * The content isn't sent back: the caller just wrote it, and it can be tens of thousands of characters. What
	 * comes back is enough to check it worked, and to read or change blocks next.
	 *
	 * @return array
	 */
	public static function output_schema(): array {

		$schema = Email_Schema::get_schema();

		$schema['properties']['block_count'] = [
			'type'        => 'integer',
			'description' => __( 'For an email built with the block editor, how many blocks were found in the content. Read them with groundhogg/get-email-template, change them with groundhogg/edit-email.', 'groundhogg' ),
		];

		$schema['properties']['warnings'] = [
			'type'  => 'array',
			'items' => [ 'type' => 'string' ],
		];

		return $schema;
	}

	/**
	 * @param Email      $email
	 * @param array|null $body what prepare_content() returned, when the content was changed
	 *
	 * @return array
	 */
	public static function output( Email $email, $body = null ): array {

		// the custom CSS is small, and set alongside the content
		$data = Email_Schema::transform( $email, [ 'css', 'version' ] );

		if ( $email->get_editor_type() === 'blocks' ) {
			$data['block_count'] = $body ? $body['block_count'] : count( ( new Email_Block_Document( (string) $email->get_content() ) )->ids() );
		}

		if ( ! empty( $body['warnings'] ) ) {
			$data['warnings'] = $body['warnings'];
		}

		return $data;
	}

	/**
	 * The `blocks` input of groundhogg/create-email-template and groundhogg/update-email-template
	 *
	 * @return array
	 */
	public static function blocks_input(): array {
		return [
			'type'        => 'array',
			'items'       => [
				'type'                 => 'object',
				'required'             => [ 'type' ],
				'additionalProperties' => true,
				'properties'           => [
					'type' => [
						'type'        => 'string',
						'description' => __( 'text, heading, image, button, spacer, divider, columns, menu, social, html, footer or global.', 'groundhogg' ),
					],
					'id'   => [
						'type'        => 'string',
						'description' => __( 'Optional, letters, digits and hyphens. One is made when it\'s left out.', 'groundhogg' ),
					],
				],
			],
			'description' => __( 'The email body as block specs, instead of `content`: each block\'s type and the settings that differ from the defaults, like {"type": "text", "content": "<p>Hi {first},</p>"}. A columns block has its blocks in `columns`, an array of arrays of specs. The markup is written the way the block editor writes it, with the site\'s block defaults, global fonts and palette ("palette:0" is the first palette color). The props of each block type are in groundhogg/get-authoring-guide (topic "email"), and groundhogg/get-email-template with view "specs" returns an email as specs.', 'groundhogg' ),
		];
	}

	/**
	 * Which editor the body in the input is for, false when there's no body, or an error when it's given both ways
	 *
	 * @param array $input
	 *
	 * @return string|false|WP_Error
	 */
	public static function editor_from_input( array $input ) {

		if ( isset( $input['blocks'] ) ) {

			if ( isset( $input['content'] ) ) {
				return new WP_Error( 'groundhogg_blocks_and_content', __( 'Give the body as `blocks` or as `content`, not both.', 'groundhogg' ) );
			}

			if ( isset( $input['editor'] ) && $input['editor'] !== 'blocks' ) {
				return new WP_Error( 'groundhogg_blocks_need_block_editor', __( '`blocks` are for the block editor, so `editor` can only be "blocks" with them.', 'groundhogg' ) );
			}

			return 'blocks';
		}

		if ( isset( $input['content'] ) ) {
			return ( $input['editor'] ?? 'html' ) === 'blocks' ? 'blocks' : 'html';
		}

		return false;
	}

	/**
	 * The body from the input, rendered from `blocks` or from `content`, see prepare_content()
	 *
	 * @param array                $input
	 * @param string               $editor
	 * @param Email_Block_Renderer $renderer
	 *
	 * @return array|WP_Error
	 */
	public static function prepare_body( array $input, string $editor, Email_Block_Renderer $renderer ) {

		if ( ! isset( $input['blocks'] ) ) {
			return self::prepare_content( (string) $input['content'], $editor, $renderer );
		}

		if ( ! is_array( $input['blocks'] ) ) {
			return new WP_Error( 'groundhogg_invalid_blocks', __( '`blocks` must be an array of block specs.', 'groundhogg' ) );
		}

		$rendered = $renderer->render( $input['blocks'] );

		if ( is_wp_error( $rendered ) ) {
			return $rendered;
		}

		return self::prepare_content( $rendered['content'], 'blocks', $renderer );
	}

	/**
	 * Check and sanitize the content for an email, and make its plain-text version and block CSS like the editor does
	 * when it saves
	 *
	 * Block content is checked with Email_Block_Document, so content the editor couldn't open properly is refused
	 * with what's wrong, instead of saved. For HTML content the plain text is left empty, so it's made from the
	 * content when the email is sent and can't be out of date.
	 *
	 * @param string                    $content
	 * @param string                    $editor   'blocks' or 'html'
	 * @param Email_Block_Renderer|null $renderer for the email's template settings
	 *
	 * @return array|WP_Error content, plain_text, css (the block CSS), block_count, warnings
	 */
	public static function prepare_content( string $content, string $editor, $renderer = null ) {

		if ( $editor !== 'blocks' ) {
			return [
				'content'     => email_kses( $content ),
				'plain_text'  => '',
				'css'         => '',
				'block_count' => 0,
				'warnings'    => [],
			];
		}

		$doc = Email_Block_Document::prepare( $content );

		if ( is_wp_error( $doc ) ) {
			return $doc;
		}

		$sanitized = $doc->sanitize();

		if ( is_wp_error( $sanitized ) ) {
			return $sanitized;
		}

		$renderer = $renderer ?: new Email_Block_Renderer();
		$made     = $renderer->css_and_plain_text( $doc->html() );

		return [
			'content'     => $doc->html(),
			'plain_text'  => $made['plain_text'],
			'css'         => $made['css'],
			'block_count' => count( $doc->ids() ),
			'warnings'    => $doc->warnings(),
		];
	}

	/**
	 * Header names that can't be set through `custom_headers`. They would change who an email is
	 * delivered to, and nothing here needs them.
	 */
	protected const BLOCKED_HEADERS = [ 'to', 'cc', 'bcc' ];

	/**
	 * The email meta for the `css`, `custom_headers` and `utm` inputs, which
	 * groundhogg/create-email-template and groundhogg/update-email-template share. Only the
	 * inputs that are present are returned, so an update leaves the others as they are.
	 *
	 * - `css` is stored in `template_css`, which Email::get_css() adds to the email's
	 *   stylesheet. The block editor never overwrites it - it keeps its own block CSS in `css`.
	 *   Emails built with the HTML editor don't use a template at all (Email::build() sends
	 *   their content as it is), so there is nowhere to put it - that's an error rather than
	 *   something silently ignored.
	 * - `custom_headers` is the same meta as the editor's Advanced > Custom Headers, applied in
	 *   Email::get_headers().
	 * - `utm` is the same meta as the editor's Advanced > UTM Parameters, applied in
	 *   Email::maybe_add_utm_to_links().
	 *
	 * Everything is sanitized again by Email::sanitize_meta() when it's saved.
	 *
	 * @param array  $input
	 * @param string $editor_type the editor type of the email once this input is applied
	 *
	 * @return array|WP_Error
	 */
	public static function extra_meta_from_input( array $input, string $editor_type ) {

		$meta = [];

		if ( isset( $input['css'] ) ) {

			if ( $editor_type === 'html' ) {
				return new WP_Error(
					'groundhogg_css_requires_blocks',
					__( '`css` only applies to emails built with the block editor. HTML emails are sent as they are, so put a <style> element in the content instead.', 'groundhogg' )
				);
			}

			$meta['template_css'] = (string) $input['css'];
		}

		if ( isset( $input['custom_headers'] ) ) {

			$headers = [];

			foreach ( (array) $input['custom_headers'] as $name => $value ) {

				$name = (string) $name;

				if ( ! preg_match( '/^[A-Za-z0-9-]+$/', $name ) ) {
					return new WP_Error(
						'groundhogg_invalid_header_name',
						/* translators: %s: the header name */
						sprintf( __( '"%s" is not a valid header name. Use only letters, numbers and hyphens.', 'groundhogg' ), $name )
					);
				}

				if ( in_array( strtolower( $name ), self::BLOCKED_HEADERS, true ) ) {
					return new WP_Error(
						'groundhogg_blocked_header',
						/* translators: %s: the header name */
						sprintf( __( 'The "%s" header can\'t be set on an email.', 'groundhogg' ), $name )
					);
				}

				if ( ! is_scalar( $value ) ) {
					return new WP_Error(
						'groundhogg_invalid_header_value',
						/* translators: %s: the header name */
						sprintf( __( 'The value of the "%s" header must be a string.', 'groundhogg' ), $name )
					);
				}

				$headers[ $name ] = (string) $value;
			}

			$meta['custom_headers'] = $headers;
		}

		if ( isset( $input['utm'] ) ) {

			$utm = (array) $input['utm'];

			foreach ( [ 'source', 'medium', 'campaign', 'term', 'content' ] as $param ) {
				if ( isset( $utm[ $param ] ) ) {
					$meta[ "utm_$param" ] = sanitize_text_field( $utm[ $param ] );
				}
			}

			if ( isset( $utm['url_allowlist'] ) ) {
				// Email::maybe_add_utm_to_links() splits this on PHP_EOL
				$meta['utm_url_allowlist'] = implode( PHP_EOL, array_filter( array_map( 'esc_url_raw', array_map( 'trim', (array) $utm['url_allowlist'] ) ) ) );
			}
		}

		return $meta;
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
