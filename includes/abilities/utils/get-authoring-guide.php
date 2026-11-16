<?php

namespace Groundhogg\Abilities\Utils;

use Groundhogg\Abilities\Ability;
use Groundhogg\Abilities\Emails\Email_Block_Renderer;
use Groundhogg\Mcp\Build_With_AI;
use WP_Error;
use function Groundhogg\admin_page_url;
use function Groundhogg\is_white_labeled;

/**
 * Returns a how-to guide for building something on this site, e.g. the block format the email editor saves, or how to
 * design a flow where every step can be reached. An agent reads one before it writes, so the guidance travels with the
 * abilities it describes and every client connected over MCP gets it, instead of living in a document the agent may
 * never see.
 *
 * The guides are Markdown files in includes/abilities/guides. They don't name the product, so they suit a white
 * labeled site, except inside {{#branded}}...{{/branded}}, which is left out on a white labeled site, the way
 * {{^branded}}...{{/branded}} is left out everywhere else. Add-ons add their own topics with the
 * groundhogg/abilities/authoring_guides filter.
 *
 * The "assistant" guide is the odd one out: it isn't about building anything, it's the persona and ground rules for
 * a whole session, what a system prompt would be. The prompts from the "Build with AI" popups send the assistant to it
 * first, see Groundhogg\Mcp\Build_With_AI::prompt(), and the other guides point to it for sessions that start elsewhere.
 */
class Get_Authoring_Guide extends Ability {

	protected const NAME     = 'groundhogg/get-authoring-guide';
	protected const CATEGORY = 'groundhogg-utils';

	protected const READONLY   = true;
	protected const IDEMPOTENT = true;

	/**
	 * The guides this ability serves, by topic.
	 *
	 * @return array[] topic => [ 'description' => string, 'file' => string, 'capability' => string ]
	 */
	public static function guides(): array {

		$guides = [
			'assistant' => [
				'description' => __( 'Read this first in any session: who you are, how to introduce yourself, the ground rules, and how to answer common questions.', 'groundhogg' ),
				'file'        => dirname( __DIR__ ) . '/guides/assistant.md',
				'capability'  => 'view_contacts',
			],
			'email' => [
				'description' => __( 'Writing an email in the block format, so it stays editable in the email editor: the block types, merge tags and good practice.', 'groundhogg' ),
				'file'        => dirname( __DIR__ ) . '/guides/email.md',
				'capability'  => 'add_emails',
			],
			'flow'  => [
				'description' => __( 'Designing a flow: finding the step types this site can build, placing triggers and delays, branching, and making sure every step can be reached.', 'groundhogg' ),
				'file'        => dirname( __DIR__ ) . '/guides/flow.md',
				'capability'  => 'add_funnels',
			],
			'setup' => [
				'description' => __( 'Setting up the site for someone: what to ask them, business details, privacy and consent, sending email, cron, owners, and what only a person can do.', 'groundhogg' ),
				'file'        => dirname( __DIR__ ) . '/guides/setup.md',
				'capability'  => 'manage_options',
			],
		];

		/**
		 * Filter the guides groundhogg/get-authoring-guide serves.
		 *
		 * Add a topic with a description, the path of a Markdown file, and the capability needed to read it. In the
		 * file, {{assets_url}} is replaced with the URL of this plugin's assets folder.
		 *
		 * @param array[] $guides topic => [ 'description' => string, 'file' => string, 'capability' => string ]
		 */
		return apply_filters( 'groundhogg/abilities/authoring_guides', $guides );
	}

	/**
	 * The guide's Markdown, or an empty string when there's no such topic.
	 *
	 * @param string $topic
	 *
	 * @return string
	 */
	public static function content( string $topic ): string {

		$guide = self::guides()[ $topic ] ?? null;

		if ( ! $guide || ! is_readable( $guide['file'] ) ) {
			return '';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file
		$content = (string) file_get_contents( $guide['file'] );

		$content = self::apply_branding( $content, ! is_white_labeled() );

		$content = str_replace( [
			'{{assets_url}}',
			'{{assistant_name}}',
			'{{help_url}}',
		], [
			GROUNDHOGG_ASSETS_URL,
			Build_With_AI::assistant_name() ?: __( 'the CRM assistant', 'groundhogg' ),
			admin_page_url( 'gh_help', [ 'tab' => 'troubleshooting' ] ),
		], $content );

		// this site's palette, fonts and block defaults, for the email guide
		if ( strpos( $content, '{{email_design}}' ) !== false ) {
			$content = str_replace( '{{email_design}}', ( new Email_Block_Renderer() )->design_summary(), $content );
		}

		return $content;
	}

	/**
	 * Keep what's in {{#branded}}...{{/branded}} and drop what's in {{^branded}}...{{/branded}}, or the other way round
	 * on a white labeled site. The blocks can't be nested.
	 *
	 * @param string $content
	 * @param bool   $branded
	 *
	 * @return string
	 */
	public static function apply_branding( string $content, bool $branded ): string {

		$content = preg_replace( '/\{\{#branded\}\}(.*?)\{\{\/branded\}\}/s', $branded ? '$1' : '', $content );
		$content = preg_replace( '/\{\{\^branded\}\}(.*?)\{\{\/branded\}\}/s', $branded ? '' : '$1', $content );

		// a block that's left out leaves its blank lines behind
		return (string) preg_replace( '/\n{3,}/', "\n\n", $content );
	}

	protected function get_args(): array {

		$topics = array_map( function ( $guide ) {
			return $guide['description'];
		}, self::guides() );

		$list = implode( ' ', array_map( function ( $topic, $description ) {
			return sprintf( '"%s": %s', $topic, $description );
		}, array_keys( $topics ), $topics ) );

		return [
			'label'       => __( 'Get Authoring Guide', 'groundhogg' ),
			/* translators: %s: the topics, each with a description */
			'description' => sprintf( __( 'Get a guide for working on this site. Read "assistant" first in any session. Read the others before you create or change that kind of thing, they cover the formats, rules and good practice the other abilities expect. Topics: %s', 'groundhogg' ), $list ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'topic' ],
				'properties'           => [
					'topic' => [
						'type'        => 'string',
						'enum'        => array_keys( $topics ),
						'description' => __( 'Which guide to get.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'topic'   => [ 'type' => 'string' ],
					'content' => [
						'type'        => 'string',
						'description' => __( 'The guide, in Markdown.', 'groundhogg' ),
					],
				],
			],
		];
	}

	/**
	 * Anyone who can build what the guide is about can read it.
	 *
	 * @param mixed $input
	 *
	 * @return bool
	 */
	public function can_execute( $input = null ) {

		$guides = self::guides();
		$topic  = $input['topic'] ?? '';

		if ( isset( $guides[ $topic ] ) ) {
			return current_user_can( $guides[ $topic ]['capability'] );
		}

		foreach ( $guides as $guide ) {
			if ( current_user_can( $guide['capability'] ) ) {
				return true;
			}
		}

		return false;
	}

	public function __invoke( $input ) {

		$topic   = sanitize_key( $input['topic'] ?? '' );
		$content = self::content( $topic );

		if ( ! $content ) {
			return new WP_Error( 'not_found', __( 'There is no guide for that topic.', 'groundhogg' ) );
		}

		return [
			'topic'   => $topic,
			'content' => $content,
		];
	}
}
