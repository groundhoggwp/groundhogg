<?php

namespace Groundhogg\Mcp;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Build with AI" on the add screens of emails and flows, "Set up with AI" in the guided setup, and "Edit with AI" in the
 * flow and email editors: a card that opens a popup, where someone describes what they want and copies a prompt for
 * their AI assistant. If they haven't connected one yet, the popup walks them through it first.
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

	/**
	 * email, flow and setup build something new. edit-email and edit-flow change the one open in the editor.
	 */
	const TYPES = [ 'email', 'flow', 'setup', 'edit-email', 'edit-flow' ];

	/**
	 * Whether to offer AI to the person viewing the page at all. It's decided for each person, not for the site:
	 *
	 *  - the site needs the Abilities API (WordPress 6.9+), or the prompt points at tools that don't exist
	 *  - they need to be able to connect an assistant of their own, see Connection::can_connect()
	 *  - and an MCP server has to be answering already, unless they can get the adapter running themselves. Someone
	 *    who can't install or activate plugins would get stuck at the first step.
	 *
	 * @return bool
	 */
	public static function is_available(): bool {
		return Connection::can_connect() && ( Connection::can_install() || Connection::is_serving() );
	}

	/**
	 * The prompt for the assistant. The person's own description of what they want replaces {{goal}}.
	 *
	 * @param string $type    one of TYPES
	 * @param array  $subject for edit-email and edit-flow, what's being edited: id and title
	 *
	 * @return string
	 */
	public static function prompt( string $type, array $subject = [] ): string {

		$site = sprintf(
		/* translators: 1: the site's name, 2: the site's address */
			__( '%1$s (%2$s)', 'groundhogg' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			home_url()
		);

		$email = wp_get_current_user()->user_email;

		$id    = absint( $subject['id'] ?? 0 );
		$title = wp_specialchars_decode( (string) ( $subject['title'] ?? '' ), ENT_QUOTES );

		if ( $type === 'edit-flow' ) {
			$lines = [
				/* translators: 1: the flow's title, 2: the flow's id, 3: the site's name and address */
				sprintf( __( 'Change the automation flow "%1$s" (ID %2$d) on my website %3$s, using the tools from the MCP connection to that site.', 'groundhogg' ), $title, $id, $site ),
				'',
				__( 'What I want changed:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "flow" and follow it. It explains how flows run and how to design one where every step can be reached.', 'groundhogg' ),
				/* translators: %d: the flow's id */
				sprintf( __( '2. Read the flow with get-flow (ID %d) so you know what\'s there before you change anything.', 'groundhogg' ), $id ),
				__( '3. If you need new steps, call list-step-types with buildable_only true and read the settings_schema of each one you use. Use list-tags, list-custom-fields and list-email-templates to find what already exists.', 'groundhogg' ),
				__( '4. Make the changes with edit-flow. Try them with dry_run true first, then save. Then tell me in a few lines what changed.', 'groundhogg' ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- If you need something I haven\'t told you, ask me before you change anything.', 'groundhogg' ),
				__( '- Only change what I asked for. Don\'t delete or rewrite other steps.', 'groundhogg' ),
				__( '- If the flow is active, your changes are staged. Don\'t publish them, activate or deactivate the flow, or add contacts to it. I\'ll review the changes in the flow editor first.', 'groundhogg' ),
			];
		} else if ( $type === 'edit-email' ) {
			$lines = [
				/* translators: 1: the email's title, 2: the email's id, 3: the site's name and address */
				sprintf( __( 'Change the email "%1$s" (ID %2$d) on my website %3$s, using the tools from the MCP connection to that site.', 'groundhogg' ), $title, $id, $site ),
				'',
				__( 'What I want changed:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "email" and follow it. It explains the block format the email editor uses, so I can keep editing the email afterwards.', 'groundhogg' ),
				/* translators: %d: the email's id */
				sprintf( __( '2. Read the email with get-email-template (ID %d). Its outline lists the blocks, read only the ones you need to change.', 'groundhogg' ), $id ),
				__( '3. Make the changes with edit-email, a block or a piece of text at a time, rather than rewriting the whole email. Use update-email-template only for the subject, preview text or other settings.', 'groundhogg' ),
				/* translators: %s: the email address of the person copying the prompt */
				sprintf( __( '4. Send a test to me at %s with send-test-email, then tell me in a few lines what changed.', 'groundhogg' ), $email ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- If you need something I haven\'t told you, ask me before you change anything.', 'groundhogg' ),
				__( '- Only change what I asked for, and keep the rest of the email as it is.', 'groundhogg' ),
				__( '- Don\'t make up facts, prices, dates or links. Leave a clear placeholder and tell me where it is.', 'groundhogg' ),
				__( '- Don\'t send it to any contacts or change its status. I\'ll review it in the editor first.', 'groundhogg' ),
			];
		} else if ( $type === 'setup' ) {
			$lines = [
				/* translators: %s: the site's name and address */
				sprintf( __( 'Set up the CRM on my website %s, using the tools from the MCP connection to that site.', 'groundhogg' ), $site ),
				'',
				__( 'About my business and what I want:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "setup" and follow it. It explains what to set up, what to ask me, and what only I can do.', 'groundhogg' ),
				__( '2. Call get-setup-status to see what\'s already done and what\'s left.', 'groundhogg' ),
				__( '3. Ask me everything you need in one go before you change anything: my business details, who I email, the address email should come from, and anything else you\'re missing.', 'groundhogg' ),
				__( '4. Set it up, building any emails and flows as drafts I can review, then call get-setup-status again.', 'groundhogg' ),
				__( '5. Finish with a short list of what\'s left for me to do, and where to do it.', 'groundhogg' ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- Never ask me for a password, API key or other secret in chat. Tell me where to enter it myself.', 'groundhogg' ),
				__( '- Don\'t email my contacts, activate flows or import contacts without asking me first.', 'groundhogg' ),
			];
		} else if ( $type === 'flow' ) {
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
		 * @param string $prompt  the prompt, {{goal}} is replaced with what the person describes
		 * @param string $type    one of Build_With_AI::TYPES
		 * @param array  $subject for edit-email and edit-flow, what's being edited: id and title
		 */
		return apply_filters( 'groundhogg/build_with_ai/prompt', implode( "\n", $lines ), $type, $subject );
	}

	/**
	 * Examples someone can start from instead of a blank box. Each fills in the description, which they can then change.
	 *
	 * @param string $type one of TYPES
	 *
	 * @return array[] [ 'label' => string, 'text' => string ]
	 */
	public static function examples( string $type ): array {

		if ( $type === 'edit-flow' ) {
			$examples = [
				[
					'label' => __( 'Add a reminder', 'groundhogg' ),
					'text'  => __( 'Before the last email, wait two days and send a short reminder to anyone who hasn\'t clicked yet.', 'groundhogg' ),
				],
				[
					'label' => __( 'Follow up', 'groundhogg' ),
					'text'  => __( 'At the end of the flow, wait a week and send a follow-up email asking how it went, with a button to reply.', 'groundhogg' ),
				],
				[
					'label' => __( 'Tag who clicks', 'groundhogg' ),
					'text'  => __( 'Tag contacts as "engaged" when they click a link in any email in this flow.', 'groundhogg' ),
				],
				[
					'label' => __( 'Split by a tag', 'groundhogg' ),
					'text'  => __( 'After the first email, send customers down one path and everyone else down another, based on their tags.', 'groundhogg' ),
				],
				[
					'label' => __( 'Change the timing', 'groundhogg' ),
					'text'  => __( 'Space the emails out so there are three days between each one, and only send during business hours.', 'groundhogg' ),
				],
				[
					'label' => __( 'Notify me', 'groundhogg' ),
					'text'  => __( 'Send me a notification email when a contact reaches the end of the flow.', 'groundhogg' ),
				],
			];
		} else if ( $type === 'edit-email' ) {
			$examples = [
				[
					'label' => __( 'Make it shorter', 'groundhogg' ),
					'text'  => __( 'Make this email about half as long. Keep the main point and the button.', 'groundhogg' ),
				],
				[
					'label' => __( 'Better subject line', 'groundhogg' ),
					'text'  => __( 'Write a clearer, more inviting subject line and preview text.', 'groundhogg' ),
				],
				[
					'label' => __( 'Friendlier tone', 'groundhogg' ),
					'text'  => __( 'Rewrite the text so it sounds warmer and more personal, like a note from me.', 'groundhogg' ),
				],
				[
					'label' => __( 'Add a button', 'groundhogg' ),
					'text'  => __( 'Add a button after the main paragraph that links to our website.', 'groundhogg' ),
				],
				[
					'label' => __( 'Add an image', 'groundhogg' ),
					'text'  => __( 'Add an image near the top that fits the topic of the email.', 'groundhogg' ),
				],
				[
					'label' => __( 'Fix the writing', 'groundhogg' ),
					'text'  => __( 'Fix any spelling, grammar and punctuation mistakes without changing what it says.', 'groundhogg' ),
				],
			];
		} else if ( $type === 'setup' ) {
			$examples = [
				[
					'label' => __( 'Online store', 'groundhogg' ),
					'text'  => __( 'I sell handmade candles online. I want a thank you email after someone buys, a monthly newsletter, and a way to win back customers who haven\'t bought in a while.', 'groundhogg' ),
				],
				[
					'label' => __( 'Coach or consultant', 'groundhogg' ),
					'text'  => __( 'I\'m a business coach. I want a free guide people can sign up for on my website, a short series of emails that follows up, and an invitation to book a call.', 'groundhogg' ),
				],
				[
					'label' => __( 'Local business', 'groundhogg' ),
					'text'  => __( 'I run a dental clinic. I want a contact form on my website, a thank you email when someone fills it in, and a newsletter with seasonal tips for our patients.', 'groundhogg' ),
				],
				[
					'label' => __( 'Courses', 'groundhogg' ),
					'text'  => __( 'I sell online courses. I want my existing WordPress users added as contacts, a welcome series for new students, and a check-in for students who haven\'t logged in for a while.', 'groundhogg' ),
				],
				[
					'label' => __( 'Nonprofit', 'groundhogg' ),
					'text'  => __( 'We\'re a small animal shelter. We want a newsletter signup form, a welcome email for new subscribers, and a monthly update about the animals looking for homes.', 'groundhogg' ),
				],
				[
					'label' => __( 'Blog', 'groundhogg' ),
					'text'  => __( 'I write a blog about gardening. I want a signup form on my posts and a weekly email with my latest articles.', 'groundhogg' ),
				],
			];
		} else if ( $type === 'flow' ) {
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
		 * @param string  $type     email, flow or setup
		 */
		return apply_filters( 'groundhogg/build_with_ai/examples', $examples, $type );
	}

	/**
	 * Load the popup on an add screen, in the guided setup, or in an editor.
	 *
	 * In an editor it's a nudge for people who haven't tried AI yet, so it's left out for anyone whose assistant has
	 * used the site lately: they know where to find it.
	 *
	 * @param string $type    one of TYPES
	 * @param array  $subject for edit-email and edit-flow, what's being edited: id and title
	 *
	 * @return void
	 */
	public static function enqueue( string $type, array $subject = [] ) {

		if ( ! in_array( $type, self::TYPES, true ) || ! self::is_available() ) {
			return;
		}

		if ( strpos( $type, 'edit-' ) === 0 && Connection::is_in_use() ) {
			return;
		}

		wp_enqueue_script( 'groundhogg-admin-build-with-ai' );

		wp_add_inline_script( 'groundhogg-admin-build-with-ai', 'var GroundhoggBuildWithAI = ' . wp_json_encode( [
				'type'      => $type,
				'prompt'    => self::prompt( $type, $subject ),
				'examples'  => array_values( self::examples( $type ) ),
				'connected' => Connection::is_connected(),
				'setupUrl'  => Connection::page_url(),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'admin_ajax' ),
			] ), 'before' );
	}
}
