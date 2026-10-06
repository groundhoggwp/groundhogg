<?php
/**
 * Seeds a test IMAP mailbox with delivery failure reports, to try the bounce checker (Settings > Email > Email
 * Bounces > Handle Bounces Now) against a real mailbox. Not part of the plugin, it's for development, bin/ isn't
 * shipped.
 *
 * Needs PHP with the imap and openssl extensions. With Local's PHP:
 *
 *   php -d extension_dir="<local php>/bin/win64/ext" -d extension=openssl -d extension=imap bin/seed-bounce-inbox.php
 *
 * The mailbox is read from the environment so that it's never written down:
 *
 *   GH_BOUNCE_IMAP_USER   the address, like you@gmail.com
 *   GH_BOUNCE_IMAP_PASS   its password, for Gmail an app password
 *   GH_BOUNCE_IMAP_HOST   default imap.gmail.com
 *   GH_BOUNCE_IMAP_PORT   default 993
 *
 * Options:
 *   --prefix=bt1   what the recipient addresses start with, use a different one for each run so that contacts don't
 *                  clash with an earlier run
 *   --clean        delete what this script put in the mailbox, and stop
 *
 * It prints the recipient addresses, which of them need to be contacts, and what the bounce checker is expected to
 * decide for each.
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Run this from the command line.' );
}

foreach ( [ 'imap_open', 'imap_append' ] as $function ) {
	if ( ! function_exists( $function ) ) {
		exit( "The PHP imap extension isn't loaded, see the top of this file.\n" );
	}
}

$user = getenv( 'GH_BOUNCE_IMAP_USER' );
$pass = getenv( 'GH_BOUNCE_IMAP_PASS' );
$host = getenv( 'GH_BOUNCE_IMAP_HOST' ) ?: 'imap.gmail.com';
$port = (int) ( getenv( 'GH_BOUNCE_IMAP_PORT' ) ?: 993 );

if ( ! $user || ! $pass ) {
	exit( "Set GH_BOUNCE_IMAP_USER and GH_BOUNCE_IMAP_PASS first.\n" );
}

$options = getopt( '', [ 'prefix::', 'clean' ] );
$prefix  = preg_replace( '/[^a-z0-9]/i', '', $options['prefix'] ?? '' ) ?: 'bt' . date( 'md' );
$mailbox = sprintf( '{%s:%d/imap/ssl}INBOX', $host, $port );

$inbox = @imap_open( $mailbox, $user, $pass );

if ( ! $inbox ) {
	exit( 'Could not connect: ' . imap_last_error() . "\n" );
}

const SEED_HEADER = 'X-GH-Bounce-Seed';

if ( isset( $options['clean'] ) ) {
	$found = imap_search( $inbox, 'HEADER ' . SEED_HEADER . ' 1' );

	foreach ( (array) $found as $number ) {
		imap_delete( $inbox, (string) $number );
	}

	imap_expunge( $inbox );
	imap_close( $inbox );

	exit( 'Deleted ' . count( (array) $found ) . " messages.\n" );
}

/**
 * A delivery status notification, what a mail server sends back when it can't deliver
 */
function dsn( string $to, string $recipient, string $action, string $status, string $diagnostic, int $arrived, string $prefix, string $case ): string {

	$lines = [
		'From: MAILER-DAEMON@mx.example.net',
		'To: ' . $to,
		'Subject: Undelivered Mail Returned to Sender',
		'Date: ' . date( 'r', $arrived ),
		'Message-ID: <' . $prefix . '-' . $case . '-' . uniqid() . '@mx.example.net>',
		SEED_HEADER . ': 1',
		'MIME-Version: 1.0',
		'Content-Type: multipart/report; report-type=delivery-status; boundary="BOUNDARY"',
		'',
		'--BOUNDARY',
		'Content-Type: text/plain; charset=us-ascii',
		'',
		'This is the mail system at host mx.example.net.',
		'',
		'--BOUNDARY',
		'Content-Type: message/delivery-status',
		'',
		'Reporting-MTA: dns; mx.example.net',
		'',
		'Final-Recipient: rfc822; ' . $recipient,
		'Original-Recipient: rfc822;' . $recipient,
		'Action: ' . $action,
		'Status: ' . $status,
		'Diagnostic-Code: smtp; ' . $diagnostic,
		'',
		'--BOUNDARY',
		'Content-Type: message/rfc822',
		'',
		'From: ' . $to,
		'To: ' . $recipient,
		'Subject: Hello',
		'',
		'The email that bounced',
		'',
		'--BOUNDARY--',
		'',
	];

	return implode( "\r\n", $lines );
}

/**
 * An automatic reply, which isn't a bounce
 */
function auto_reply( string $to, int $arrived, string $prefix ): string {

	return implode( "\r\n", [
		'From: Someone <someone-' . $prefix . '@example.net>',
		'To: ' . $to,
		'Subject: Out of Office: Hello',
		'Date: ' . date( 'r', $arrived ),
		'Message-ID: <' . $prefix . '-auto-' . uniqid() . '@example.net>',
		SEED_HEADER . ': 1',
		'Auto-Submitted: auto-replied',
		'X-Auto-Response-Suppress: All',
		'',
		'I am away from the office until next week.',
		'',
	] );
}

$hours = 3600;

// case => [ recipient, action, status, diagnostic, age in seconds, read, expected decision ]
$cases = [
	'hard_511'      => [ "$prefix-hard511@example.com", 'failed', '5.1.1', '550 5.1.1 User unknown', 3 * 24 * $hours, true, 'hard' ],
	'hard_570'      => [ "$prefix-hard570@example.com", 'failed', '5.7.0', '550 5.7.0 Message rejected', 2 * 24 * $hours, false, 'hard' ],
	'hard_capital'  => [ "$prefix-hardcaps@example.com", 'Failed', '5.1.2', '550 5.1.2 Bad destination host', 30 * $hours, true, 'hard' ],
	'hard_rewrite'  => [ "$prefix-hardrewrite@example.com", 'failed', '5.1.1', '451 4.3.0 try again later', 4 * 24 * $hours, true, 'hard' ],
	'soft_full'     => [ "$prefix-softfull@example.com", 'failed', '4.2.2', '452 4.2.2 Mailbox full', 5 * $hours, false, 'soft' ],
	'soft_delayed'  => [ "$prefix-delayed@example.com", 'delayed', '4.4.1', '451 4.4.1 Connection timed out', 2 * $hours, false, 'soft' ],
	'not_a_contact' => [ "$prefix-nobody@example.com", 'failed', '5.1.1', '550 5.1.1 User unknown', 6 * $hours, false, 'not_found' ],
	'too_old'       => [ "$prefix-tooold@example.com", 'failed', '5.1.1', '550 5.1.1 User unknown', 10 * 24 * $hours, false, 'not looked at, older than the 7 days of Handle Bounces Now' ],
];

$now      = time();
$expected = [];

foreach ( $cases as $case => [ $recipient, $action, $status, $diagnostic, $age, $read, $decision ] ) {

	$arrived = $now - $age;
	$message = dsn( $user, $recipient, $action, $status, $diagnostic, $arrived, $prefix, $case );

	$ok = imap_append( $inbox, $mailbox, $message, $read ? '\\Seen' : '', date( 'd-M-Y H:i:s O', $arrived ) );

	printf( "%-14s %-3s %-34s %s\n", $case, $ok ? 'ok' : 'ERR', $recipient, $ok ? '' : imap_last_error() );

	$expected[ $case ] = [ $recipient, $decision, $read ? 'read' : 'unread', gmdate( 'Y-m-d H:i', $arrived ) . ' UTC' ];
}

$ok = imap_append( $inbox, $mailbox, auto_reply( $user, $now - 3 * $hours, $prefix ), '', date( 'd-M-Y H:i:s O', $now - 3 * $hours ) );
printf( "%-14s %-3s\n", 'auto_reply', $ok ? 'ok' : 'ERR' );

// what the mailbox says about the dates, Gmail may not keep the one that was given
echo "\nWhat the mailbox kept (it uses these for the search):\n";

$found = imap_search( $inbox, 'HEADER ' . SEED_HEADER . ' 1' );

foreach ( (array) $found as $number ) {
	$overview = imap_fetch_overview( $inbox, (string) $number, 0 )[0] ?? null;

	if ( $overview ) {
		printf( "  #%-4d date header %s   seen=%d\n", $number, gmdate( 'Y-m-d H:i', (int) $overview->udate ), (int) $overview->seen );
	}
}

imap_close( $inbox );

echo "\nCreate these as contacts (all but the nobody one), then run Handle Bounces Now and compare:\n\n";

foreach ( $expected as $case => [ $recipient, $decision, $state, $when ] ) {
	printf( "  %-34s expect %-9s (%s, %s)\n", $recipient, $decision, $state, $when );
}

echo "\n  the Out of Office message: counted as not understood\n";
echo "  the 10 day old message: not looked at by Handle Bounces Now\n";
