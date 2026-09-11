<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Schemas\Email_Schema;
use Groundhogg\Campaign;
use Groundhogg\Email;
use WP_Error;
use function Groundhogg\email_kses;
use function Groundhogg\get_object_ids;
use function Groundhogg\get_sender_profiles;

/**
 * Updates an existing saved Groundhogg email, mirroring the PUT
 * gh/v4/emails/<id> route (Emails_Api::update_single()). Every field is
 * optional and only touched when actually present in the input - omitting a
 * field leaves it as-is, matching the REST route's own "update()" semantics
 * (it passes whatever `data`/`meta` it's given straight through).
 *
 * `content`/`editor` and `template_settings` follow the exact same rules as
 * groundhogg/create-email (see that class's docblock) - this exists because
 * that ability has no update path of its own: it always inserts a new row,
 * so fixing a mistake (wrong template_settings, a typo in content, ...) on an
 * email it just created has no other way back short of creating a fresh one.
 *
 * `campaigns`, when present, replaces the full set of campaigns on the email
 * (added/removed by diffing against what's already there) - same as the REST
 * route, not an additive merge.
 */
class Update_Email extends Ability {

	protected const string NAME       = 'groundhogg/update-email';
	protected const string CATEGORY   = 'groundhogg-email';
	protected const string CAPABILITY = 'edit_emails';

	protected const bool READONLY    = false;
	protected const bool DESTRUCTIVE = false;
	protected const bool IDEMPOTENT  = true;

	protected function get_args(): array {

		return [
			'label'       => __( 'Update Email', 'groundhogg' ),
			'description' => __( 'Update an existing saved Groundhogg email. Every field is optional - only fields actually provided are changed. Find the id with groundhogg/list-email-templates.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'email_id' ],
				'properties'           => [
					'email_id' => [
						'type'        => 'integer',
						'minimum'     => 1,
						'description' => __( 'The email to update.', 'groundhogg' ),
					],
					'subject' => [
						'type'        => 'string',
						'description' => __( 'The subject line. May contain merge tags like {first_name}, resolved per-recipient at send time.', 'groundhogg' ),
					],
					'title' => [
						'type'        => 'string',
						'description' => __( 'The internal admin title, shown in the emails list.', 'groundhogg' ),
					],
					'content' => [
						'type'        => 'string',
						'description' => __( 'The email body. Sanitized with the same allowed-HTML rules as the email editor. Shape depends on `editor`: plain HTML for "html", or the block editor\'s comment-annotated HTML for "blocks" - see groundhogg/create-email\'s description for the exact format. Required if `editor` is given (and vice versa) - changing one without the other would leave the content and the editor-type flag out of sync.', 'groundhogg' ),
					],
					'editor' => [
						'type'        => 'string',
						'enum'        => [ 'html', 'blocks' ],
						'description' => __( 'Which editor `content` is written for. Only meaningful (and required) when `content` is also given - see `content`.', 'groundhogg' ),
					],
					'plain_text' => [
						'type'        => 'string',
						'description' => __( 'Plain-text alternative body.', 'groundhogg' ),
					],
					'pre_header' => [
						'type'        => 'string',
						'description' => __( 'Preview text shown next to the subject line in most inboxes.', 'groundhogg' ),
					],
					'message_type' => [
						'type'        => 'string',
						'enum'        => [ 'marketing', 'transactional' ],
						'description' => __( 'Which sending rules and compliance footer apply. "marketing" respects unsubscribes/consent; "transactional" bypasses them.', 'groundhogg' ),
					],
					'status' => [
						'type'        => 'string',
						'enum'        => [ 'draft', 'ready' ],
						'description' => __( 'Only a "ready" email sends cleanly through groundhogg/send-email-template.', 'groundhogg' ),
					],
					'is_template' => [
						'type'        => 'boolean',
						'description' => __( 'True to save this as a reusable template (Emails > Templates) rather than a regular one-off email.', 'groundhogg' ),
					],
					'from_profile' => [
						'type'        => 'string',
						'description' => __( 'Which configured sender to send as - see groundhogg/list-sender-profiles for valid values.', 'groundhogg' ),
					],
					'campaigns' => [
						'type'        => 'array',
						'items'       => [ 'type' => 'integer' ],
						'description' => __( 'Replaces the full set of campaign IDs tagged on this email (added/removed by diffing against the current set) - not merged with the existing ones.', 'groundhogg' ),
					],
					'template_settings' => [
						'type'                 => 'object',
						'additionalProperties' => false,
						'description'          => __( 'The outer page layout/background this email renders inside of. Only the properties actually given are changed - see groundhogg/create-email\'s `template_settings` for what each one does.', 'groundhogg' ),
						'properties'           => Email_Schema::template_settings_properties(),
					],
				],
			],

			'output_schema' => Email_Schema::get_schema(),
		];
	}

	public function __invoke( $input ) {

		$email = new Email( absint( $input['email_id'] ) );

		if ( ! $email->exists() ) {
			return new WP_Error( 'groundhogg_email_not_found', __( 'Email not found.', 'groundhogg' ) );
		}

		if ( isset( $input['content'] ) !== isset( $input['editor'] ) ) {
			return new WP_Error(
				'groundhogg_content_editor_mismatch',
				__( '`content` and `editor` must be given together, so the content and the editor-type flag stay in sync.', 'groundhogg' )
			);
		}

		if ( isset( $input['from_profile'] ) ) {

			$from_profile = sanitize_text_field( $input['from_profile'] );

			if ( ! in_array( $from_profile, array_keys( get_sender_profiles() ), true ) ) {
				return new WP_Error(
					'groundhogg_invalid_from_profile',
					__( 'Not a valid sender profile. See groundhogg/list-sender-profiles.', 'groundhogg' )
				);
			}
		}

		$data         = [];
		$meta_updates = [];

		if ( isset( $input['subject'] ) ) {
			$data['subject'] = sanitize_text_field( $input['subject'] );
		}

		if ( isset( $input['title'] ) ) {
			$data['title'] = sanitize_text_field( $input['title'] );
		}

		if ( isset( $input['plain_text'] ) ) {
			$data['plain_text'] = sanitize_textarea_field( $input['plain_text'] );
		}

		if ( isset( $input['pre_header'] ) ) {
			$data['pre_header'] = sanitize_text_field( $input['pre_header'] );
		}

		if ( isset( $input['message_type'] ) ) {
			$data['message_type'] = $input['message_type'];
		}

		if ( isset( $input['status'] ) ) {
			$data['status'] = $input['status'];
		}

		if ( isset( $input['is_template'] ) ) {
			$data['is_template'] = $input['is_template'] ? 1 : 0;
		}

		if ( isset( $input['from_profile'] ) ) {
			$data['from_profile'] = sanitize_text_field( $input['from_profile'] );
		}

		if ( isset( $input['content'] ) ) {

			$editor = $input['editor'] === 'blocks' ? 'blocks' : 'html';

			$data['content'] = $editor === 'blocks'
				? Create_Email::kses_preserving_block_comments( $input['content'] )
				: email_kses( $input['content'] );

			// Same meta flags groundhogg/create-email writes - see that class's
			// docblock for why these specific keys.
			$meta_updates = $editor === 'blocks'
				? [ 'type' => 'blocks', 'blocks' => true ]
				: [ 'type' => 'html', 'blocks' => false ];
		}

		$template_settings = (array) ( $input['template_settings'] ?? [] );

		if ( isset( $template_settings['layout'] ) ) {
			$meta_updates['template'] = $template_settings['layout'];
		}

		if ( isset( $template_settings['width'] ) ) {
			$meta_updates['width'] = absint( $template_settings['width'] );
		}

		if ( isset( $template_settings['alignment'] ) ) {
			$meta_updates['alignment'] = $template_settings['alignment'];
		}

		if ( isset( $template_settings['direction'] ) ) {
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

		if ( ! empty( $data ) ) {
			$email->update( $data );
		}

		if ( ! empty( $meta_updates ) ) {
			$email->update_meta( $meta_updates );
		}

		if ( isset( $input['campaigns'] ) ) {

			$campaigns        = wp_parse_id_list( $input['campaigns'] );
			$has_campaigns    = get_object_ids( $email->get_related_objects( 'campaign' ) );
			$add_campaigns    = array_diff( $campaigns, $has_campaigns );
			$remove_campaigns = array_diff( $has_campaigns, $campaigns );

			foreach ( $add_campaigns as $campaign_id ) {
				$email->create_relationship( new Campaign( $campaign_id ) );
			}

			foreach ( $remove_campaigns as $campaign_id ) {
				$email->delete_relationship( new Campaign( $campaign_id ) );
			}
		}

		// Mirrors Emails_Api::do_object_updated_action() so anything hooked to
		// the REST update path also fires here.
		do_action( 'groundhogg/api/email/updated', $email );

		return Email_Schema::transform( $email, [ 'content', 'plain_text' ] );
	}
}
