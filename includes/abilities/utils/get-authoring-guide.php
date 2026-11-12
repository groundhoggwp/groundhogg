<?php

namespace Groundhogg\Abilities\Utils;

use Groundhogg\Abilities\Ability;
use WP_Error;

/**
 * Returns a how-to guide for building something on this site, e.g. the block format the email editor saves, or how to
 * design a flow where every step can be reached. An agent reads one before it writes, so the guidance travels with the
 * abilities it describes and every client connected over MCP gets it, instead of living in a document the agent may
 * never see.
 *
 * The guides are Markdown files in includes/abilities/guides. They never name the product, so they suit a white
 * labeled site. Add-ons add their own topics with the groundhogg/abilities/authoring_guides filter.
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

		return str_replace( '{{assets_url}}', GROUNDHOGG_ASSETS_URL, $content );
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
			'description' => sprintf( __( 'Get the guide for building something on this site. Read it before you create or change that kind of thing, it covers the formats, rules and good practice the other abilities expect. Topics: %s', 'groundhogg' ), $list ),

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
