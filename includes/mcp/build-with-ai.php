<?php

namespace Groundhogg\Mcp;

use function Groundhogg\is_white_labeled;

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
 * Nothing here names the product, so it reads right on a white labeled site. The assistant's name, PhilBot, is left
 * out there too, see assistant_name().
 *
 * The popup is assets/js/admin/build-with-ai.js.
 */
class Build_With_AI {

	/**
	 * email, flow and setup build something new. edit-email and edit-flow change the one open in the editor. strategy
	 * looks at what the site has and how it performs, finds what to improve, and fixes it: on the AI agent tab and the
	 * Reports page. troubleshoot finds out why something isn't working: in the troubleshooter and on the AI agent tab.
	 */
	const TYPES = [ 'email', 'flow', 'setup', 'edit-email', 'edit-flow', 'strategy', 'troubleshoot' ];

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
	 * What the assistant calls itself. There's no name on a white labeled site, since PhilBot would give the brand
	 * away, unless the filter gives it one.
	 *
	 * @return string
	 */
	public static function assistant_name(): string {

		/**
		 * Filter the name the assistant introduces itself with, in the prompts and the "assistant" authoring guide.
		 * Return an empty string for no name.
		 *
		 * @param string $name PhilBot, or nothing on a white labeled site
		 */
		return (string) apply_filters( 'groundhogg/build_with_ai/assistant_name', is_white_labeled() ? '' : 'PhilBot' );
	}

	/**
	 * The first lines of every prompt, like a system prompt: who the assistant is, and to read the "assistant" guide
	 * before anything else. The persona and ground rules themselves are in that guide, so they stay in one place.
	 * Assistants follow the person's own message more reliably than a tool's output, so the persona is set here.
	 *
	 * @return string[]
	 */
	protected static function preamble(): array {

		$name = self::assistant_name();

		return [
			$name
				/* translators: %s: the name the assistant introduces itself with */
				? sprintf( __( 'You are %s, an AI assistant for the CRM on my website.', 'groundhogg' ), $name )
				: __( 'You are an AI assistant for the CRM on my website.', 'groundhogg' ),
			__( 'Before anything else, call the get-authoring-guide ability with topic "assistant" and follow it for this whole conversation. Then introduce yourself in a sentence or two, and start on the task below.', 'groundhogg' ),
			'',
		];
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
		} else if ( $type === 'strategy' ) {
			$lines = [
				/* translators: %s: the site's name and address */
				sprintf( __( 'Help me get more out of the CRM on my website %s, using the tools from the MCP connection to that site. Find what\'s working, what isn\'t and what\'s missing in my marketing and sales, and help me fix it.', 'groundhogg' ), $site ),
				'',
				__( 'What I want you to look at:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "strategy" and follow it. It explains what to look at, which reports answer which question, and how to tell me what you found.', 'groundhogg' ),
				__( '2. Look at the site before you ask me anything: my flows, forms, emails, broadcasts, tags and saved searches, and the reports that answer the question.', 'groundhogg' ),
				__( '3. Then ask me what you can\'t find out yourself, all at once: what I sell, who to, and what I want more of.', 'groundhogg' ),
				__( '4. Give me a short list of findings, most important first. For each one, the numbers behind it, why it matters and what you\'d do about it.', 'groundhogg' ),
				__( '5. Offer to fix them one at a time. Build anything new as a draft or an inactive flow, and change what I already have only after I say yes.', 'groundhogg' ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- Until I agree to a change, only look. Don\'t email contacts, change contacts or tags, activate or deactivate flows, or delete anything.', 'groundhogg' ),
				__( '- Base every finding on my site\'s own numbers, and say when there isn\'t enough data to tell.', 'groundhogg' ),
			];
		} else if ( $type === 'troubleshoot' ) {
			$lines = [
				/* translators: %s: the site's name and address */
				sprintf( __( 'Help me fix a problem with the CRM on my website %s, using the tools from the MCP connection to that site.', 'groundhogg' ), $site ),
				'',
				__( 'What\'s wrong:', 'groundhogg' ),
				'{{goal}}',
				'',
				__( 'How to do it:', 'groundhogg' ),
				__( '1. Call the get-authoring-guide ability with topic "troubleshoot" and follow it. It explains where to look for each kind of problem and what the errors mean.', 'groundhogg' ),
				__( '2. Call get-setup-status to rule out the usual causes, then look into the problem itself: the flows, events, emails and settings it involves. Look before you ask me anything.', 'groundhogg' ),
				__( '3. Ask me only what you can\'t find out yourself, like what I expected to happen and when it last worked.', 'groundhogg' ),
				__( '4. Tell me the cause, the evidence for it, and how to fix it. Fix what you can after I say yes, and give me exact steps for what only I or my host can do.', 'groundhogg' ),
				__( '5. If you can\'t find the cause, write a short summary of what you checked and found that I can send to support.', 'groundhogg' ),
				'',
				__( 'Rules:', 'groundhogg' ),
				__( '- Until I agree to a change, only look. Don\'t email contacts, change settings, contacts or tags, activate or deactivate flows, or delete anything.', 'groundhogg' ),
				__( '- Never ask me for a password, API key or other secret in chat. Tell me where to enter it myself.', 'groundhogg' ),
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
		$lines = array_merge( self::preamble(), $lines );

		return apply_filters( 'groundhogg/build_with_ai/prompt', implode( "\n", $lines ), $type, $subject );
	}

	/**
	 * Examples someone can start from instead of a blank box. Each fills in the description, which they can then change.
	 *
	 * For strategy and troubleshoot they're the missions on the AI agent tab, so each also has a summary for its card,
	 * and the capabilities the work needs, like view_reports to read the reports. Only the ones the person viewing the
	 * page has every capability for are returned, so nobody is offered something their assistant would be refused.
	 *
	 * @param string $type one of TYPES
	 *
	 * @return array[] [ 'label' => string, 'text' => string, 'summary' => string, 'caps' => string[], 'id' => string ]
	 *                 summary and caps for strategy and troubleshoot, and id for troubleshoot
	 */
	public static function examples( string $type ): array {

		if ( $type === 'strategy' ) {
			$examples = [
				[
					'caps'    => [ 'view_funnels', 'view_emails' ],
					'label'   => __( 'Find gaps in my marketing', 'groundhogg' ),
					'summary' => __( 'The lead magnets, welcome emails, follow-ups and win-backs you don\'t have yet.', 'groundhogg' ),
					'text'    => __( 'Audit my marketing from first visit to repeat customer. Tell me which stages I\'m missing, like a lead magnet, a welcome series, follow-up after a form or a purchase, or winning back people who went quiet, and which to build first.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'view_funnels', 'view_reports' ],
					'label'   => __( 'Find where contacts drop out', 'groundhogg' ),
					'summary' => __( 'The flow steps that lose the most people, and why.', 'groundhogg' ),
					'text'    => __( 'Look at my active flows and find where contacts drop out or get stuck: the steps that lose the most people, goals few reach, and emails nobody clicks. Tell me why you think it happens and how to fix it.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'view_reports' ],
					'label'   => __( 'Review the last 30 days', 'groundhogg' ),
					'summary' => __( 'What changed, what\'s working and what needs attention.', 'groundhogg' ),
					'text'    => __( 'Review the last 30 days compared to the 30 before: new contacts and where they came from, email performance, flow conversions and unsubscribes. Tell me what changed, what\'s working and what needs attention.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'view_reports', 'edit_emails' ],
					'label'   => __( 'Improve my weakest emails', 'groundhogg' ),
					'summary' => __( 'Why your lowest performing emails fall flat, and better versions.', 'groundhogg' ),
					'text'    => __( 'Find my worst performing emails, in flows and broadcasts. Tell me why each one underperforms, and suggest better subject lines and changes. Make the changes only after I agree.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'view_reports' ],
					'label'   => __( 'Check my list health', 'groundhogg' ),
					'summary' => __( 'Bounces, complaints, unsubscribes and contacts who stopped reading.', 'groundhogg' ),
					'text'    => __( 'Check the health of my list: bounces, spam complaints, unsubscribes and why people leave, contacts who haven\'t opened or clicked anything in months, and how my email is sent. Tell me the risks to my deliverability and what to do about them.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'view_funnels' ],
					'label'   => __( 'Review my sales follow-up', 'groundhogg' ),
					'summary' => __( 'How fast leads hear back, and who falls through the cracks.', 'groundhogg' ),
					'text'    => __( 'Review how I follow up with leads and customers: what happens after someone fills out a form or books a call, whether new leads get an owner, tasks and notes, and who never hears back. Suggest a follow-up process and the flows to run it.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'manage_tags' ],
					'label'   => __( 'Tidy up my tags and segments', 'groundhogg' ),
					'summary' => __( 'Duplicate, unused and confusing tags and saved searches.', 'groundhogg' ),
					'text'    => __( 'Look at my tags and saved searches. Find duplicates, ones nobody uses and ones that are hard to understand, and suggest a simpler way to organize my contacts. Don\'t change or delete anything until I agree.', 'groundhogg' ),
				],
				[
					'caps'    => [ 'view_reports', 'view_funnels' ],
					'label'   => __( 'Plan my next 90 days', 'groundhogg' ),
					'summary' => __( 'A marketing and sales plan built on what you already have.', 'groundhogg' ),
					'text'    => __( 'Based on what\'s on my site and how it\'s performing, write me a marketing and sales plan for the next 90 days: what to fix, what to build and in what order, and what to measure.', 'groundhogg' ),
				],
			];
		} else if ( $type === 'troubleshoot' ) {
			// the id is the troubleshooter step for the same problem, which offers it, see features/troubleshooter.js.
			// Most of the checks need an administrator: get-setup-status, get-cron-status and query-table
			$examples = [
				[
					'id'      => 'emails-not-sending',
					'caps'    => [ 'manage_options' ],
					'label'   => __( 'Emails aren\'t sending', 'groundhogg' ),
					'summary' => __( 'Why emails or broadcasts aren\'t going out, or aren\'t arriving.', 'groundhogg' ),
					'text'    => __( 'My emails aren\'t sending. Find out why: whether the queue and cron job are running, whether emails are failing and with what errors, how email is sent from this site, and whether the emails are ready to send.', 'groundhogg' ),
				],
				[
					'id'      => 'failed-events',
					'caps'    => [ 'manage_options' ],
					'label'   => __( 'Fix failed events', 'groundhogg' ),
					'summary' => __( 'What the errors in the event queue mean, and how to stop them.', 'groundhogg' ),
					'text'    => __( 'Look at the failed events from the last 30 days. Group them by error, tell me what each one means and which flows, emails and steps they come from, and how to fix the cause.', 'groundhogg' ),
				],
				[
					'id'      => 'cron',
					'caps'    => [ 'manage_options' ],
					'label'   => __( 'Get the cron job working', 'groundhogg' ),
					'summary' => __( 'Set up or fix the cron job that runs flows and sends broadcasts.', 'groundhogg' ),
					'text'    => __( 'Check whether the cron job that runs my flows and broadcasts is working. If it isn\'t, tell me exactly how to set it up with my host, and check again once I have.', 'groundhogg' ),
				],
				[
					'id'      => 'funnel-not-running',
					'caps'    => [ 'manage_options', 'view_funnels' ],
					'label'   => __( 'A flow isn\'t running', 'groundhogg' ),
					'summary' => __( 'Contacts don\'t start the flow, or get stuck partway.', 'groundhogg' ),
					'text'    => __( 'One of my flows isn\'t running. Find out why: whether it\'s active, whether what starts it is happening, and whether contacts are stuck waiting at a step or failing. Ask me which flow if you can\'t tell.', 'groundhogg' ),
				],
				[
					'id'      => 'funnel-not-working',
					'caps'    => [ 'view_funnels' ],
					'label'   => __( 'A flow does the wrong thing', 'groundhogg' ),
					'summary' => __( 'Contacts take the wrong path, or get the wrong emails.', 'groundhogg' ),
					'text'    => __( 'One of my flows isn\'t doing what I expect. Ask me which flow and what should happen, then find out where and why it does something else, and how to fix it.', 'groundhogg' ),
				],
				[
					'id'      => 'cant-unsubscribe',
					'caps'    => [ 'manage_options' ],
					'label'   => __( 'Contacts can\'t unsubscribe', 'groundhogg' ),
					'summary' => __( 'The unsubscribe link or preferences page doesn\'t work.', 'groundhogg' ),
					'text'    => __( 'Contacts tell me they can\'t unsubscribe. Find out why: my permalinks, caching on my site, host or CDN, and the unsubscribe settings. Send me a test email so I can try the link.', 'groundhogg' ),
				],
				[
					'id'      => 'tracking-links-not-working',
					'caps'    => [ 'manage_options' ],
					'label'   => __( 'Links in emails don\'t work', 'groundhogg' ),
					'summary' => __( 'Links that go to an error, or clicks that aren\'t counted.', 'groundhogg' ),
					'text'    => __( 'The links in my emails don\'t work, or clicks aren\'t being tracked. Find out why: the tracking settings, my permalinks, caching on my site, host or CDN, and whether my email service rewrites links.', 'groundhogg' ),
				],
				[
					'id'      => 'integration-not-working',
					'caps'    => [ 'manage_options' ],
					'label'   => __( 'An integration isn\'t working', 'groundhogg' ),
					'summary' => __( 'A form, store, course or other plugin that isn\'t doing its part.', 'groundhogg' ),
					'text'    => __( 'One of my integrations isn\'t working the way it should. Ask me which one and what I expected, then check that it\'s active and licensed, the flows that use it, and recent errors from it.', 'groundhogg' ),
				],
			];
		} else if ( $type === 'edit-flow' ) {
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
		$examples = apply_filters( 'groundhogg/build_with_ai/examples', $examples, $type );

		return array_values( array_filter( $examples, function ( $example ) {
			foreach ( $example['caps'] ?? [] as $cap ) {
				if ( ! current_user_can( $cap ) ) {
					return false;
				}
			}

			return true;
		} ) );
	}

	/**
	 * Load the popup on an add screen, in the guided setup, or in an editor.
	 *
	 * In an editor it's a nudge for people who haven't tried AI yet, so it's left out for anyone whose assistant has
	 * used the site lately: they know where to find it.
	 *
	 * @param string   $type    one of TYPES
	 * @param array    $subject for edit-email and edit-flow, what's being edited: id and title
	 * @param string[] $also    other types the page can open the popup for, like the AI agent tab's build cards. Only
	 *                          the ones offered to this person are included, see can_offer()
	 *
	 * @return void
	 */
	public static function enqueue( string $type, array $subject = [], array $also = [] ) {

		if ( ! in_array( $type, self::TYPES, true ) || ! self::is_available() ) {
			return;
		}

		if ( strpos( $type, 'edit-' ) === 0 && Connection::is_in_use() ) {
			return;
		}

		$others = [];

		foreach ( array_intersect( $also, [ 'email', 'flow', 'setup', 'strategy', 'troubleshoot' ] ) as $other ) {
			if ( $other !== $type && self::can_offer( $other ) ) {
				$others[ $other ] = [
					'prompt'   => self::prompt( $other ),
					'examples' => array_values( self::examples( $other ) ),
				];
			}
		}

		wp_enqueue_script( 'groundhogg-admin-build-with-ai' );

		wp_add_inline_script( 'groundhogg-admin-build-with-ai', 'var GroundhoggBuildWithAI = ' . wp_json_encode( [
				'type'      => $type,
				'prompt'    => self::prompt( $type, $subject ),
				'examples'  => array_values( self::examples( $type ) ),
				'also'      => (object) $others,
				'connected' => Connection::is_connected(),
				'setupUrl'  => Connection::page_url(),
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'admin_ajax' ),
			] ), 'before' );
	}

	/**
	 * Whether the person viewing the page can build this, so it's only offered to people who can: the same capability
	 * as the add screen, or the guided setup, and the authoring guide the prompt sends the assistant to.
	 *
	 * @param string $type email, flow or setup
	 *
	 * @return bool
	 */
	public static function can_build( string $type ): bool {

		$caps = [
			'email' => 'add_emails',
			'flow'  => 'add_funnels',
			'setup' => 'manage_options',
		];

		return isset( $caps[ $type ] ) && current_user_can( $caps[ $type ] );
	}

	/**
	 * Whether to offer this to the person viewing the page: what they can build, and for strategy and troubleshoot, if
	 * there's at least one mission they have the capabilities for.
	 *
	 * @param string $type one of TYPES, other than edit-email and edit-flow
	 *
	 * @return bool
	 */
	public static function can_offer( string $type ): bool {

		if ( in_array( $type, [ 'strategy', 'troubleshoot' ], true ) ) {
			return ! empty( self::examples( $type ) );
		}

		return self::can_build( $type );
	}
}
