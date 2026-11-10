<?php

namespace Groundhogg\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Build with AI" on the add screens of emails and flows: a card among the templates that opens a popup, where someone
 * describes what they want and copies a prompt for their AI assistant.
 *
 * The prompt is short on purpose. It says which abilities to call in which order and sets a few ground rules, and
 * sends the assistant to groundhogg/get-authoring-guide for the detail, so that detail stays in one place, current
 * with the abilities, for every client and not only for people who come through this popup.
 *
 * Nothing here names the product, so it reads right on a white labeled site.
 *
 * The popup is assets/js/admin/build-with-ai.js.
 */
class Build_With_AI {

	const TYPES = [ 'email', 'flow' ];

	/**
	 * Whether there's anything for an assistant to connect to: without the Abilities API (WordPress 6.9+) the prompt
	 * would point at tools that don't exist.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return Connection::is_supported();
	}

	/**
	 * The prompt for the assistant. The person's own description of what they want replaces {{goal}}.
	 *
	 * @param string $type email or flow
	 *
	 * @return string
	 */
	public static function prompt( string $type ): string {

		$site = sprintf(
		/* translators: 1: the site's name, 2: the site's address */
			__( '%1$s (%2$s)', 'groundhogg' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			home_url()
		);

		$email = wp_get_current_user()->user_email;

		if ( $type === 'flow' ) {
			$lines = [
				/* translators: %s: the site's name and address */
				sprintf( __( 'Build an automation flow for my website %s, using the tools from the MCP connection to that site.', 'groundhogg' ), $site ),
				'',
				__( 'What I want:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "flow" and follow it. It explains how flows run and how to design one where every step can be reached.', 'groundhogg' ),
				__( '2. Call list-step-types with buildable_only true to see which steps this site can build, and read the settings_schema of each one you use.', 'groundhogg' ),
				__( '3. Use list-tags, list-custom-fields and list-email-templates to find what already exists. If the flow needs new emails, read the guide with topic "email" and create them with create-email-template, with status "ready". The flow is created inactive, so nothing is sent.', 'groundhogg' ),
				__( '4. Build it with create-flow and check the steps it returns. Then give me the link to edit it and a short summary of what each step does.', 'groundhogg' ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- If you need something I haven\'t told you, like what starts the flow, the timing or the links, ask me before you start.', 'groundhogg' ),
				__( '- If a step I need can\'t be built, don\'t replace it with something else. Build the rest and tell me what to add in the flow editor, and where.', 'groundhogg' ),
				__( '- Don\'t activate the flow or add contacts to it. I\'ll review it first.', 'groundhogg' ),
			];
		} else {
			$lines = [
				/* translators: %s: the site's name and address */
				sprintf( __( 'Build an email for my website %s, using the tools from the MCP connection to that site.', 'groundhogg' ), $site ),
				'',
				__( 'What I want:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "email" and follow it. It explains the block format the email editor uses, so I can keep editing the email afterwards.', 'groundhogg' ),
				__( '2. Look at what the site already has: list-email-templates with expand ["content"] to match the look of my existing emails, list-replacement-codes for merge tags, and list-sender-profiles if it should come from someone other than the default sender.', 'groundhogg' ),
				__( '3. Save it with create-email-template, using editor "blocks" and status "draft".', 'groundhogg' ),
				/* translators: %s: the email address of the person copying the prompt */
				sprintf( __( '4. Send a test to me at %s with send-test-email, then give me the link to edit the email.', 'groundhogg' ), $email ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- If you need something I haven\'t told you, like who it\'s for, the offer or the links, ask me before you start.', 'groundhogg' ),
				__( '- Don\'t make up facts, prices, dates or links. Leave a clear placeholder and tell me where it is.', 'groundhogg' ),
				__( '- Don\'t send it to any contacts. I\'ll review it and send it myself.', 'groundhogg' ),
			];
		}

		/**
		 * Filter the prompt the "Build with AI" popup gives someone to copy into their AI assistant.
		 *
		 * @param string $prompt the prompt, {{goal}} is replaced with what the person describes
		 * @param string $type   email or flow
		 */
		return apply_filters( 'groundhogg/build_with_ai/prompt', implode( "\n", $lines ), $type );
	}

	/**
	 * Examples someone can start from instead of a blank box. Each fills in the description, which they can then change.
	 *
	 * @param string $type email or flow
	 *
	 * @return array[] [ 'label' => string, 'text' => string ]
	 */
	public static function examples( string $type ): array {

		if ( $type === 'flow' ) {
			$examples = [
				[
					'label' => __( 'Welcome series', 'groundhogg' ),
					'text'  => __( 'When someone joins my newsletter, send a welcome email right away, then two more emails three days apart that introduce our most popular content.', 'groundhogg' ),
				],
				[
					'label' => __( 'Lead magnet', 'groundhogg' ),
					'text'  => __( 'When someone fills out the form to download our free guide, email them the guide, wait three days, then ask if they found it useful and invite them to book a call.', 'groundhogg' ),
				],
				[
					'label' => __( 'Contact form follow-up', 'groundhogg' ),
					'text'  => __( 'When someone submits the contact form, send them a thank you email, let me know about it, and if they haven\'t booked a call two days later, send a friendly follow-up.', 'groundhogg' ),
				],
				[
					'label' => __( 'Webinar reminders', 'groundhogg' ),
					'text'  => __( 'When someone registers for our webinar, send a confirmation right away, a reminder the day before, and another reminder an hour before it starts.', 'groundhogg' ),
				],
				[
					'label' => __( 'After a purchase', 'groundhogg' ),
					'text'  => __( 'When someone buys from us, send a thank you email, a week later send tips for getting the most out of their purchase, and two weeks after that ask for a review.', 'groundhogg' ),
				],
				[
					'label' => __( 'Re-engagement', 'groundhogg' ),
					'text'  => __( 'For contacts who haven\'t opened our emails in a while, send a check-in email, wait a week, and if they still haven\'t clicked anything, send a last chance email and tag them as unengaged.', 'groundhogg' ),
				],
			];
		} else {
			$examples = [
				[
					'label' => __( 'Welcome email', 'groundhogg' ),
					'text'  => __( 'A welcome email for new newsletter subscribers. Thank them for joining, tell them what to expect and how often, and link to our three most popular blog posts.', 'groundhogg' ),
				],
				[
					'label' => __( 'Newsletter', 'groundhogg' ),
					'text'  => __( 'A monthly newsletter with a short intro from me, three sections for recent articles, each with a heading, one sentence and a link, and a short sign-off.', 'groundhogg' ),
				],
				[
					'label' => __( 'Product launch', 'groundhogg' ),
					'text'  => __( 'An announcement for a new product: a short story about why we made it, its three main benefits, and a button to learn more. Leave placeholders for the name, image and link.', 'groundhogg' ),
				],
				[
					'label' => __( 'Limited-time offer', 'groundhogg' ),
					'text'  => __( 'A sale email with the discount front and center, the date it ends, and one clear button to shop. Short and to the point.', 'groundhogg' ),
				],
				[
					'label' => __( 'Event invitation', 'groundhogg' ),
					'text'  => __( 'An invitation to our upcoming webinar with the date and time, three things attendees will learn, and a button to register.', 'groundhogg' ),
				],
				[
					'label' => __( 'Check-in', 'groundhogg' ),
					'text'  => __( 'A friendly, plain-looking email to contacts who haven\'t opened our emails in a while, asking if they still want to hear from us, with a button to stay subscribed.', 'groundhogg' ),
				],
			];
		}

		/**
		 * Filter the examples the "Build with AI" popup offers to start from.
		 *
		 * @param array[] $examples [ 'label' => string, 'text' => string ]
		 * @param string  $type     email or flow
		 */
		return apply_filters( 'groundhogg/build_with_ai/examples', $examples, $type );
	}

	/**
	 * Load the popup on an add screen.
	 *
	 * @param string $type email or flow
	 *
	 * @return void
	 */
	public static function enqueue( string $type ) {

		if ( ! self::is_available() || ! in_array( $type, self::TYPES, true ) ) {
			return;
		}

		wp_enqueue_script( 'groundhogg-admin-build-with-ai' );

		wp_add_inline_script( 'groundhogg-admin-build-with-ai', 'var GroundhoggBuildWithAI = ' . wp_json_encode( [
				'type'      => $type,
				'prompt'    => self::prompt( $type ),
				'examples'  => array_values( self::examples( $type ) ),
				'connected' => Connection::is_connected(),
				'setupUrl'  => Connection::page_url(),
			] ), 'before' );
	}
}
