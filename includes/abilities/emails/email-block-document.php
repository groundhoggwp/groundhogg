<?php

namespace Groundhogg\Abilities\Emails;

use WP_Error;
use function Groundhogg\html2markdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The content of an email built with the block editor, as blocks that can be read and changed one at a time.
 *
 * Block content is a <table> with a <tr> per block, and each block is wrapped in a pair of comments that carry its
 * type, id and settings (props):
 *
 *     <tr><!--text:intro {"p":{...}}--><td id="b-intro" ...>...</td><!--/text:intro--></tr>
 *
 * Blocks in columns sit in the same kind of rows, inside each column's <table class="column">. The editor parses this
 * back into blocks in parseBlocksFromContent() (email-block-editor.js), and Block_Registry::parse_blocks() renders
 * the dynamic ones when the email is sent.
 *
 * This exists so an agent can change an email a block at a time (groundhogg/edit-email) and read just an outline of
 * it (groundhogg/get-email-template), instead of reading and rewriting the whole body for every change. It works on
 * the markup as it is, by position, so everything it doesn't touch is kept byte for byte.
 *
 * Parsing also finds the mistakes that the editor or the sender silently get wrong, so they can be refused with a
 * clear error instead:
 * - settings that aren't valid JSON, which make the block show up as text
 * - a missing or mismatched closing comment, or blocks that overlap
 * - a duplicate id
 * - a comment not followed by <td id="b-{id}">, which makes the editor reset the block to its default content
 * - a block that isn't in its own <tr>, or the content not being a <table>, which makes the editor skip blocks
 *
 * normalize() fixes the harmless differences the editor's parser is strict about: whitespace between <tr> and the
 * comment (the editor reads the row's first node as the comment), and spacing inside the comments.
 */
class Email_Block_Document {

	/**
	 * Block types the editor has without add-ons
	 */
	const CORE_TYPES = [
		'text',
		'heading',
		'image',
		'button',
		'spacer',
		'divider',
		'columns',
		'menu',
		'social',
		'html',
		'footer',
		'global',
		'posts',
		'queryloop',
		'shortcode',
	];

	/**
	 * The most errors listed in one WP_Error
	 */
	const MAX_ERRORS = 10;

	/**
	 * @var string
	 */
	protected $html = '';

	/**
	 * Whether this is a fragment, blocks that aren't (yet) in an email's table
	 *
	 * @var bool
	 */
	protected $fragment = false;

	/**
	 * The blocks by id, see parse() for what each one has
	 *
	 * @var array[]
	 */
	protected $blocks = [];

	/**
	 * Block ids in the order they're in the content
	 *
	 * @var string[]
	 */
	protected $order = [];

	/**
	 * @var string[]
	 */
	protected $errors = [];

	/**
	 * @var string[]
	 */
	protected $warnings = [];

	/**
	 * @param string $html
	 * @param bool   $fragment true for markup with blocks to add to an email, which doesn't need the outer table or rows
	 */
	public function __construct( string $html, bool $fragment = false ) {
		$this->html     = $html;
		$this->fragment = $fragment;
		$this->parse();
	}

	/**
	 * Parse and check the content of an email that's saved with the block editor, and normalize it
	 *
	 * @param string $content
	 *
	 * @return Email_Block_Document|WP_Error
	 */
	public static function prepare( string $content ) {

		$doc = new self( $content );

		if ( empty( $doc->order ) && empty( $doc->errors ) ) {
			return new WP_Error(
				'groundhogg_no_blocks',
				__( 'No blocks were found in the content. For editor "blocks", the content is a <table> with a <tr> per block, and each block is wrapped in <!--type:id {...}--> and <!--/type:id--> comments. Get the format with groundhogg/get-authoring-guide (topic "email"), or save it with editor "html" instead.', 'groundhogg' )
			);
		}

		if ( ! $doc->is_valid() ) {
			return self::error_from( $doc->errors() );
		}

		$doc->normalize();

		return $doc;
	}

	/**
	 * A WP_Error listing parse errors
	 *
	 * @param string[] $errors
	 * @param string   $intro
	 *
	 * @return WP_Error
	 */
	public static function error_from( array $errors, string $intro = '' ): WP_Error {

		if ( ! $intro ) {
			$intro = __( 'The block content has problems, so nothing was saved:', 'groundhogg' );
		}

		$more = count( $errors ) - self::MAX_ERRORS;

		$lines = array_map( function ( $error ) {
			return '- ' . $error;
		}, array_slice( $errors, 0, self::MAX_ERRORS ) );

		if ( $more > 0 ) {
			/* translators: %d: how many more problems there are */
			$lines[] = sprintf( _n( '- and %d more problem.', '- and %d more problems.', $more, 'groundhogg' ), $more );
		}

		return new WP_Error( 'groundhogg_invalid_blocks', $intro . "\n" . implode( "\n", $lines ), [ 'problems' => $errors ] );
	}

	/**
	 * Find every block, check it, and work out how they're nested
	 *
	 * Each block has:
	 * - type, id, json (its settings as written, '' when there were none), props (decoded)
	 * - start, open_end: the opening comment
	 * - close_start, end: the closing comment
	 * - td_end, cell_end: where the inside of its <td id="b-{id}"> starts and ends, null when there's no such <td>
	 * - row_start, row_end: its <tr>, null when it isn't in one
	 * - parent, column, children: the block it's inside of, which of its columns, and the blocks directly inside it
	 * - columns_at: for a block with columns, where each column's <table class="column"> tag is, [start, end] pairs
	 *
	 * @return void
	 */
	protected function parse() {

		$this->blocks   = [];
		$this->order    = [];
		$this->errors   = [];
		$this->warnings = [];

		$html   = $this->html;
		$offset = 0;

		while ( preg_match( '/<!--\s*([a-z]+):([A-Za-z0-9-]+)(?=[\s{]|-->)/', $html, $match, PREG_OFFSET_CAPTURE, $offset ) ) {

			$start    = $match[0][1];
			$type     = $match[1][0];
			$id       = $match[2][0];
			$after_id = $start + strlen( $match[0][0] );

			// The comment ends at the first --> after which what follows the id is valid JSON, or nothing at all
			$props      = null;
			$json       = '';
			$open_end   = false;
			$first_end  = false;
			$json_error = '';
			$search     = $after_id;

			for ( $i = 0; $i < 50; $i ++ ) {

				$close = strpos( $html, '-->', $search );

				if ( $close === false ) {
					break;
				}

				if ( $first_end === false ) {
					$first_end = $close;
				}

				$middle = trim( substr( $html, $after_id, $close - $after_id ) );

				if ( $middle === '' ) {
					$props    = [];
					$open_end = $close + 3;
					break;
				}

				if ( $middle[0] !== '{' ) {
					break;
				}

				$decoded = json_decode( $middle, true );

				if ( is_array( $decoded ) ) {
					$props    = $decoded;
					$json     = $middle;
					$open_end = $close + 3;
					break;
				}

				if ( $json_error === '' ) {
					$json_error = json_last_error_msg();
				}

				$search = $close + 3;
			}

			if ( $open_end === false ) {

				$this->errors[] = $json_error
					/* translators: 1: block id, 2: block type, 3: the JSON error */
					? sprintf( __( 'Block "%1$s" (%2$s): its settings aren\'t valid JSON (%3$s). A block with invalid settings shows up in the email as text.', 'groundhogg' ), $id, $type, $json_error )
					/* translators: 1: block id, 2: the comment it should look like */
					: sprintf( __( 'Block "%1$s": its opening comment should look like %2$s.', 'groundhogg' ), $id, "<!--$type:$id {...}-->" );

				$offset = $first_end !== false ? $first_end + 3 : $after_id;
				continue;
			}

			if ( ! preg_match( '/<!--\s*\/\s*' . preg_quote( "$type:$id", '/' ) . '\s*-->/', $html, $closing, PREG_OFFSET_CAPTURE, $open_end ) ) {
				/* translators: 1: block id, 2: the closing comment */
				$this->errors[] = sprintf( __( 'Block "%1$s" has no closing comment %2$s.', 'groundhogg' ), $id, "<!--/$type:$id-->" );
				$offset         = $open_end;
				continue;
			}

			if ( isset( $this->blocks[ $id ] ) ) {
				/* translators: %s: block id */
				$this->errors[] = sprintf( __( 'The id "%s" is used by more than one block. Every block needs its own id.', 'groundhogg' ), $id );
				$offset         = $open_end;
				continue;
			}

			$close_start = $closing[0][1];
			$end         = $close_start + strlen( $closing[0][0] );

			$block = [
				'type'        => $type,
				'id'          => $id,
				'json'        => $json,
				'props'       => $props,
				'start'       => $start,
				'open_end'    => $open_end,
				'close_start' => $close_start,
				'end'         => $end,
				'td_end'      => null,
				'cell_end'    => null,
				'row_start'   => null,
				'row_end'     => null,
				'parent'      => null,
				'column'      => null,
				'children'    => [],
				'columns_at'  => [],
			];

			// The editor reads the block from <td id="b-{id}">, without it the block is reset to its defaults
			if ( preg_match( '/\G\s*<td\b[^>]*>/i', $html, $td, 0, $open_end ) ) {

				if ( ! preg_match( '/\bid\s*=\s*(["\'])b-' . preg_quote( $id, '/' ) . '\1/i', $td[0] ) ) {
					/* translators: 1: block id, 2: the attribute */
					$this->errors[] = sprintf( __( 'Block "%1$s": its <td> needs %2$s. Without it the editor can\'t read the block and resets it to its default content.', 'groundhogg' ), $id, 'id="b-' . $id . '"' );
				}

				$cell_end = strrpos( substr( $html, 0, $close_start ), '</td>' );

				if ( $cell_end === false || $cell_end < $open_end + strlen( $td[0] ) ) {
					/* translators: %s: block id */
					$this->errors[] = sprintf( __( 'Block "%s": its <td> isn\'t closed before its closing comment.', 'groundhogg' ), $id );
				} else {
					$block['td_end']   = $open_end + strlen( $td[0] );
					$block['cell_end'] = $cell_end;
				}

			} else {
				/* translators: 1: block id, 2: the <td> it needs */
				$this->errors[] = sprintf( __( 'Block "%1$s": its opening comment must be followed directly by %2$s, which holds the block\'s HTML.', 'groundhogg' ), $id, '<td id="b-' . $id . '">' );
			}

			$look_back = min( 500, $start );
			$before    = substr( $html, $start - $look_back, $look_back );

			if ( preg_match( '/<tr\b[^>]*>\s*$/i', $before, $row, PREG_OFFSET_CAPTURE ) && preg_match( '/\G\s*<\/tr>/i', $html, $row_close, 0, $end ) ) {
				$block['row_start'] = $start - $look_back + $row[0][1];
				$block['row_end']   = $end + strlen( $row_close[0] );
			} else if ( ! $this->fragment ) {
				/* translators: 1: block id, 2: what the row looks like */
				$this->errors[] = sprintf( __( 'Block "%1$s" must be in a table row of its own, with nothing else in it: %2$s.', 'groundhogg' ), $id, "<tr><!--$type:$id {...}--><td id=\"b-$id\">...</td><!--/$type:$id--></tr>" );
			}

			$this->blocks[ $id ] = $block;
			$this->order[]       = $id;

			// keep going inside the block, for the blocks in its columns
			$offset = $open_end;
		}

		$this->nest();
		$this->find_columns();
		$this->find_invalid_comments();

		if ( ! $this->fragment && ! empty( $this->order ) && ! preg_match( '/^\s*<table\b/i', $html ) ) {
			$this->errors[] = __( 'The content must be a single <table> with a <tr> per block, with nothing before it.', 'groundhogg' );
		}

		/**
		 * The block types an email can have, for add-ons that add their own
		 *
		 * @param string[] $types
		 */
		$types = apply_filters( 'groundhogg/email/block_types', self::CORE_TYPES );

		foreach ( $this->order as $id ) {
			if ( ! in_array( $this->blocks[ $id ]['type'], $types, true ) ) {
				/* translators: 1: block id, 2: block type */
				$this->warnings[] = sprintf( __( 'Block "%1$s" has the type "%2$s", which this site doesn\'t know. The editor won\'t be able to open it.', 'groundhogg' ), $id, $this->blocks[ $id ]['type'] );
			}
		}
	}

	/**
	 * Work out which block each block is inside of, from where they start and end
	 *
	 * @return void
	 */
	protected function nest() {

		$stack = [];

		foreach ( $this->order as $id ) {

			while ( ! empty( $stack ) && $this->blocks[ end( $stack ) ]['end'] <= $this->blocks[ $id ]['start'] ) {
				array_pop( $stack );
			}

			if ( ! empty( $stack ) ) {

				$parent = end( $stack );

				if ( $this->blocks[ $id ]['end'] > $this->blocks[ $parent ]['close_start'] ) {
					/* translators: 1: block id, 2: block id */
					$this->errors[] = sprintf( __( 'Blocks "%1$s" and "%2$s" overlap. A block inside another one has to close first.', 'groundhogg' ), $parent, $id );
				}

				$this->blocks[ $id ]['parent']       = $parent;
				$this->blocks[ $parent ]['children'][] = $id;
			}

			$stack[] = $id;
		}
	}

	/**
	 * Find each column of the blocks that have blocks inside, and which column each of those is in
	 *
	 * A column is a <table class="column"> directly in the block, not one in a block inside it.
	 *
	 * @return void
	 */
	protected function find_columns() {

		foreach ( $this->order as $id ) {

			$block = $this->blocks[ $id ];

			if ( empty( $block['children'] ) && $block['type'] !== 'columns' ) {
				continue;
			}

			$from   = $block['open_end'];
			$length = $block['close_start'] - $from;

			preg_match_all( '/<table\b[^>]*>/i', substr( $this->html, $from, $length ), $tables, PREG_OFFSET_CAPTURE );

			$columns_at = [];

			foreach ( $tables[0] as $table ) {

				if ( ! preg_match( '/\bclass\s*=\s*(["\'])([^"\']*)\1/i', $table[0], $class ) || ! in_array( 'column', preg_split( '/\s+/', $class[2] ), true ) ) {
					continue;
				}

				$at = $from + $table[1];

				foreach ( $block['children'] as $child ) {
					if ( $at > $this->blocks[ $child ]['start'] && $at < $this->blocks[ $child ]['end'] ) {
						continue 2;
					}
				}

				$columns_at[] = [ $at, $at + strlen( $table[0] ) ];
			}

			$this->blocks[ $id ]['columns_at'] = $columns_at;

			if ( empty( $columns_at ) ) {
				continue;
			}

			foreach ( $block['children'] as $child ) {

				$column = - 1;

				foreach ( $columns_at as $table ) {
					if ( $table[0] < $this->blocks[ $child ]['start'] ) {
						$column ++;
					}
				}

				$this->blocks[ $child ]['column'] = max( 0, $column );
			}
		}
	}

	/**
	 * Comments that look like they're meant to be block comments but that the editor wouldn't read as one, like an
	 * uppercase type or an underscore in the id. Without this they'd be skipped without a word.
	 *
	 * Other comments with a colon are fine, like the <!-- START:COLUMN --> markers the editor puts in columns, so
	 * only a lowercase type with a bad id, or a block type written in the wrong case, counts.
	 *
	 * @return void
	 */
	protected function find_invalid_comments() {

		preg_match_all( '/<!--\s*\/?\s*([A-Za-z0-9_]+):([^\s{]*?)(?=[\s{]|-->)/', $this->html, $comments, PREG_SET_ORDER );

		/** This filter is documented in parse() */
		$types = apply_filters( 'groundhogg/email/block_types', self::CORE_TYPES );

		foreach ( $comments as $comment ) {

			$type = $comment[1];
			$id   = $comment[2];

			$lowercase_type = (bool) preg_match( '/^[a-z]+$/', $type );
			$valid_id       = (bool) preg_match( '/^[A-Za-z0-9-]+$/', $id );

			if ( $lowercase_type ? $valid_id : ! in_array( strtolower( $type ), $types, true ) ) {
				continue;
			}

			$this->errors[] = sprintf(
			/* translators: %s: the comment */
				__( '"%s" isn\'t a valid block comment: the type must be lowercase letters (a-z), and the id letters, digits and hyphens.', 'groundhogg' ),
				"<!--$type:$id"
			);
		}
	}

	/**
	 * @return bool
	 */
	public function is_valid(): bool {
		return empty( $this->errors );
	}

	/**
	 * @return string[]
	 */
	public function errors(): array {
		return $this->errors;
	}

	/**
	 * @return string[]
	 */
	public function warnings(): array {
		return $this->warnings;
	}

	/**
	 * @return string
	 */
	public function html(): string {
		return $this->html;
	}

	/**
	 * @param string $id
	 *
	 * @return bool
	 */
	public function has( string $id ): bool {
		return isset( $this->blocks[ $id ] );
	}

	/**
	 * Block ids in the order they're in the content
	 *
	 * @return string[]
	 */
	public function ids(): array {
		return $this->order;
	}

	/**
	 * The blocks that aren't in another block
	 *
	 * @return string[]
	 */
	public function top_level(): array {
		return array_values( array_filter( $this->order, function ( $id ) {
			return $this->blocks[ $id ]['parent'] === null;
		} ) );
	}

	/**
	 * A block, see parse() for what it has
	 *
	 * @param string $id
	 *
	 * @return array|null
	 */
	public function get( string $id ) {
		return $this->blocks[ $id ] ?? null;
	}

	/**
	 * A block's markup, from its opening comment to its closing comment
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function markup( string $id ): string {
		$block = $this->blocks[ $id ];

		return substr( $this->html, $block['start'], $block['end'] - $block['start'] );
	}

	/**
	 * Every block inside a block, at any depth
	 *
	 * @param string $id
	 *
	 * @return string[]
	 */
	public function descendants( string $id ): array {

		$ids = [];

		foreach ( $this->blocks[ $id ]['children'] as $child ) {
			$ids[] = $child;
			$ids   = array_merge( $ids, $this->descendants( $child ) );
		}

		return $ids;
	}

	/**
	 * Where a block's row starts and ends, or the block itself when it isn't in a row
	 *
	 * @param string $id
	 *
	 * @return int[]
	 */
	protected function row_range( string $id ): array {
		$block = $this->blocks[ $id ];

		return [ $block['row_start'] ?? $block['start'], $block['row_end'] ?? $block['end'] ];
	}

	/**
	 * Replace part of the content and parse it again
	 *
	 * A change that leaves blocks the editor couldn't read, like text replaced in a block's <td id>, or HTML with a
	 * stray </td>, is undone.
	 *
	 * @param int    $start
	 * @param int    $length
	 * @param string $replacement
	 *
	 * @return true|WP_Error
	 */
	protected function splice( int $start, int $length, string $replacement ) {

		$before = $this->html;
		$valid  = $this->is_valid();

		$this->html = substr_replace( $this->html, $replacement, $start, $length );
		$this->parse();

		if ( $valid && ! $this->is_valid() ) {

			$errors     = $this->errors;
			$this->html = $before;
			$this->parse();

			return self::error_from( $errors, __( 'That change would break the email\'s blocks, so it wasn\'t made:', 'groundhogg' ) );
		}

		return true;
	}

	/**
	 * Fix what the editor's parser is strict about, see the class description
	 *
	 * @return void
	 */
	public function normalize() {

		$edits = [];

		foreach ( $this->order as $id ) {

			$block = $this->blocks[ $id ];
			$name  = $block['type'] . ':' . $id;

			$edits[] = [ $block['start'], $block['open_end'] - $block['start'], '<!--' . $name . ' ' . ( $block['json'] !== '' ? $block['json'] : '{}' ) . '-->' ];
			$edits[] = [ $block['close_start'], $block['end'] - $block['close_start'], '<!--/' . $name . '-->' ];

			if ( $block['row_start'] !== null ) {

				// the editor takes the first node in the row to be the comment, whitespace before it would be that node
				$tag_end = strpos( $this->html, '>', $block['row_start'] ) + 1;
				$edits[] = [ $tag_end, $block['start'] - $tag_end, '' ];

				$close_tr = strpos( $this->html, '<', $block['end'] );
				$edits[]  = [ $block['end'], $close_tr - $block['end'], '' ];
			}
		}

		// from the end, so the positions of the ones still to do don't move
		usort( $edits, function ( $a, $b ) {
			return $b[0] - $a[0];
		} );

		$html = $this->html;

		foreach ( $edits as $edit ) {
			$html = substr_replace( $html, $edit[2], $edit[0], $edit[1] );
		}

		$this->html = $html;
		$this->parse();
	}

	/**
	 * Sanitize the content like the email editor's content is, keeping the block comments
	 *
	 * @return true|WP_Error
	 */
	public function sanitize() {

		$this->html = Create_Email_Template::kses_preserving_block_comments( $this->html );
		$this->parse();

		if ( ! $this->is_valid() ) {
			return self::error_from( $this->errors, __( 'Sanitizing the content broke some blocks, so nothing was saved. Check the HTML is allowed in emails:', 'groundhogg' ) );
		}

		return true;
	}

	/**
	 * Check that a block exists
	 *
	 * @param mixed $id
	 *
	 * @return true|WP_Error
	 */
	protected function check_block( $id ) {

		if ( ! is_string( $id ) || $id === '' || ! isset( $this->blocks[ $id ] ) ) {
			return new WP_Error(
				'groundhogg_block_not_found',
				/* translators: %s: block id */
				sprintf( __( 'There\'s no block "%s" in this email. Get the block ids from groundhogg/get-email-template.', 'groundhogg' ), is_scalar( $id ) ? $id : '' )
			);
		}

		return true;
	}

	/**
	 * Where a table's own contents start and end, skipping a <tbody>, given where its opening tag ends
	 *
	 * @param int $tag_end
	 *
	 * @return int[] start, end
	 */
	protected function table_inside( int $tag_end ): array {

		$start = $tag_end;

		if ( preg_match( '/\G\s*<tbody\b[^>]*>/i', $this->html, $tbody, 0, $start ) ) {
			$start += strlen( $tbody[0] );
		}

		$depth  = 1;
		$offset = $tag_end;
		$end    = strlen( $this->html );

		while ( preg_match( '/<(\/?)table\b[^>]*>/i', $this->html, $tag, PREG_OFFSET_CAPTURE, $offset ) ) {

			$depth += $tag[1][0] === '/' ? - 1 : 1;

			if ( $depth === 0 ) {
				$end = $tag[0][1];
				break;
			}

			$offset = $tag[0][1] + strlen( $tag[0][0] );
		}

		if ( preg_match( '/<\/tbody>\s*$/i', substr( $this->html, $start, $end - $start ), $close_tbody, PREG_OFFSET_CAPTURE ) ) {
			$end = $start + $close_tbody[0][1];
		}

		return [ $start, $end ];
	}

	/**
	 * Where to put blocks, from a position like groundhogg/edit-email's `at`
	 *
	 * @param array    $at      one of {after: id}, {before: id}, {in: id, column: n, position: start|end}, {position: start|end}
	 * @param string[] $exclude blocks that can't be the reference, like the one being moved
	 *
	 * @return int|WP_Error
	 */
	protected function insertion_point( array $at, array $exclude = [] ) {

		foreach ( [ 'after', 'before', 'in' ] as $key ) {

			if ( ! isset( $at[ $key ] ) ) {
				continue;
			}

			$ref = $at[ $key ];
			$ok  = $this->check_block( $ref );

			if ( is_wp_error( $ok ) ) {
				return $ok;
			}

			if ( in_array( $ref, $exclude, true ) ) {
				/* translators: %s: block id */
				return new WP_Error( 'groundhogg_invalid_position', sprintf( __( 'A block can\'t be put next to or inside itself ("%s").', 'groundhogg' ), $ref ) );
			}

			if ( $key === 'after' ) {
				return $this->row_range( $ref )[1];
			}

			if ( $key === 'before' ) {
				return $this->row_range( $ref )[0];
			}

			$columns = $this->blocks[ $ref ]['columns_at'];

			if ( empty( $columns ) ) {
				/* translators: 1: block id, 2: block type */
				return new WP_Error( 'groundhogg_invalid_position', sprintf( __( 'Block "%1$s" (%2$s) has no columns to put blocks in.', 'groundhogg' ), $ref, $this->blocks[ $ref ]['type'] ) );
			}

			$column = (int) ( $at['column'] ?? 0 );

			if ( $column < 0 || $column >= count( $columns ) ) {
				/* translators: 1: block id, 2: how many columns it has, 3: the last column number */
				return new WP_Error( 'groundhogg_invalid_position', sprintf( __( 'Block "%1$s" has %2$d columns, numbered from 0 to %3$d.', 'groundhogg' ), $ref, count( $columns ), count( $columns ) - 1 ) );
			}

			$inside = $this->table_inside( $columns[ $column ][1] );

			return ( $at['position'] ?? 'end' ) === 'start' ? $inside[0] : $inside[1];
		}

		$top = array_values( array_diff( $this->top_level(), $exclude ) );

		if ( ! empty( $top ) ) {
			return ( $at['position'] ?? 'end' ) === 'start' ? $this->row_range( $top[0] )[0] : $this->row_range( end( $top ) )[1];
		}

		// no blocks yet, so into the outer table, which is added when there isn't one
		if ( ! preg_match( '/<table\b[^>]*>/i', $this->html, $table, PREG_OFFSET_CAPTURE ) ) {
			$this->html = '<table cellpadding="0" cellspacing="0" width="100%" role="presentation"></table>';
			$this->parse();

			return $this->insertion_point( $at, $exclude );
		}

		$inside = $this->table_inside( $table[0][1] + strlen( $table[0][0] ) );

		return ( $at['position'] ?? 'end' ) === 'start' ? $inside[0] : $inside[1];
	}

	/**
	 * Turn markup with one or more blocks into rows to put in this email
	 *
	 * The blocks can come as rows, as a whole table of rows, or without rows. Anything outside the blocks, like the
	 * table around them, is left out.
	 *
	 * @param string   $markup
	 * @param string[] $reusable ids that may be used again, those of the blocks being replaced
	 *
	 * @return array|WP_Error rows: the markup to insert, ids: every block in it
	 */
	protected function rows_from( $markup, array $reusable = [] ) {

		if ( ! is_string( $markup ) || trim( $markup ) === '' ) {
			return new WP_Error( 'groundhogg_no_blocks', __( 'No block markup was given.', 'groundhogg' ) );
		}

		$fragment = new self( $markup, true );

		if ( ! $fragment->is_valid() ) {
			return self::error_from( $fragment->errors(), __( 'The new blocks have problems, so nothing was saved:', 'groundhogg' ) );
		}

		if ( empty( $fragment->order ) ) {
			return new WP_Error( 'groundhogg_no_blocks', __( 'No blocks were found in the markup. Each block is wrapped in <!--type:id {...}--> and <!--/type:id--> comments, around a <td id="b-{id}">.', 'groundhogg' ) );
		}

		$taken = array_values( array_diff( array_intersect( $fragment->order, $this->order ), $reusable ) );

		if ( ! empty( $taken ) ) {
			return new WP_Error(
				'groundhogg_block_id_taken',
				/* translators: %s: block ids */
				sprintf( __( 'These ids are already used by other blocks in the email: %s. Give the new blocks ids of their own.', 'groundhogg' ), implode( ', ', $taken ) )
			);
		}

		$rows = '';

		foreach ( $fragment->top_level() as $id ) {

			$block = $fragment->blocks[ $id ];

			if ( $block['row_start'] !== null ) {
				$rows .= substr( $fragment->html, $block['row_start'], $block['row_end'] - $block['row_start'] );
			} else {
				$rows .= '<tr>' . $fragment->markup( $id ) . '</tr>';
			}
		}

		return [
			'rows'     => $rows,
			'ids'      => $fragment->order,
			'warnings' => $fragment->warnings(),
		];
	}

	/**
	 * Add blocks
	 *
	 * @param string $markup
	 * @param array  $at see insertion_point()
	 *
	 * @return string[]|WP_Error the ids of the blocks added, including those inside them
	 */
	public function insert( $markup, array $at ) {

		$rows = $this->rows_from( $markup );

		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		$point = $this->insertion_point( $at );

		if ( is_wp_error( $point ) ) {
			return $point;
		}

		$spliced = $this->splice( $point, 0, $rows['rows'] );

		return is_wp_error( $spliced ) ? $spliced : $rows['ids'];
	}

	/**
	 * Delete a block, and the blocks inside it
	 *
	 * @param string $id
	 *
	 * @return string[]|WP_Error the ids of the blocks deleted
	 */
	public function delete( $id ) {

		$ok = $this->check_block( $id );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		$deleted = array_merge( [ $id ], $this->descendants( $id ) );

		[ $start, $end ] = $this->row_range( $id );

		$spliced = $this->splice( $start, $end - $start, '' );

		return is_wp_error( $spliced ) ? $spliced : $deleted;
	}

	/**
	 * Move a block, with the blocks inside it
	 *
	 * @param string $id
	 * @param array  $at see insertion_point()
	 *
	 * @return true|WP_Error
	 */
	public function move( $id, array $at ) {

		$ok = $this->check_block( $id );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		// check where it's going before anything changes
		$check = $this->insertion_point( $at, array_merge( [ $id ], $this->descendants( $id ) ) );

		if ( is_wp_error( $check ) ) {
			return $check;
		}

		[ $start, $end ] = $this->row_range( $id );

		$row = substr( $this->html, $start, $end - $start );

		if ( $this->blocks[ $id ]['row_start'] === null ) {
			$row = '<tr>' . $row . '</tr>';
		}

		$before = $this->html;

		$result = $this->splice( $start, $end - $start, '' );

		if ( ! is_wp_error( $result ) ) {
			$point  = $this->insertion_point( $at );
			$result = is_wp_error( $point ) ? $point : $this->splice( $point, 0, $row );
		}

		if ( is_wp_error( $result ) ) {
			$this->html = $before;
			$this->parse();
		}

		return $result;
	}

	/**
	 * Replace a block, and the blocks inside it, with other blocks
	 *
	 * @param string $id
	 * @param string $markup
	 *
	 * @return array|WP_Error added: ids of the new blocks, deleted: ids of the old ones that aren't in the new markup
	 */
	public function replace( $id, $markup ) {

		$ok = $this->check_block( $id );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		$old  = array_merge( [ $id ], $this->descendants( $id ) );
		$rows = $this->rows_from( $markup, $old );

		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		[ $start, $end ] = $this->row_range( $id );

		$spliced = $this->splice( $start, $end - $start, $rows['rows'] );

		if ( is_wp_error( $spliced ) ) {
			return $spliced;
		}

		return [
			'added'   => $rows['ids'],
			'deleted' => array_values( array_diff( $old, $rows['ids'] ) ),
		];
	}

	/**
	 * Replace the HTML inside a block's <td>, keeping the <td> itself (its padding and background) and the settings
	 *
	 * @param string $id
	 * @param string $html
	 *
	 * @return true|WP_Error
	 */
	public function set_content( $id, $html ) {

		$ok = $this->check_block( $id );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		$block = $this->blocks[ $id ];

		if ( ! empty( $block['children'] ) || $block['type'] === 'columns' ) {
			/* translators: %s: block id */
			return new WP_Error( 'groundhogg_block_has_blocks', sprintf( __( 'Block "%s" has blocks inside it. Change those blocks instead, or replace the whole block.', 'groundhogg' ), $id ) );
		}

		if ( ! is_string( $html ) ) {
			return new WP_Error( 'groundhogg_invalid_content', __( '`html` must be a string.', 'groundhogg' ) );
		}

		if ( preg_match( '/<!--\s*\/?\s*[A-Za-z0-9_]+:/', $html ) ) {
			return new WP_Error( 'groundhogg_invalid_content', __( '`html` is the HTML inside the block\'s <td>, without block comments. To change a block into other blocks, use "replace".', 'groundhogg' ) );
		}

		if ( ! self::tables_are_balanced( $html ) ) {
			return new WP_Error( 'groundhogg_invalid_content', __( '`html` closes a <table>, <tr> or <td> it didn\'t open, or leaves one open. It\'s the HTML inside the block\'s <td>, so it can\'t close the <td> itself.', 'groundhogg' ) );
		}

		return $this->splice( $block['td_end'], $block['cell_end'] - $block['td_end'], $html );
	}

	/**
	 * Whether every <table>, <tr> and <td> in some HTML is closed, and nothing is closed that wasn't opened in it
	 *
	 * @param string $html
	 *
	 * @return bool
	 */
	protected static function tables_are_balanced( string $html ): bool {

		preg_match_all( '/<(\/?)(table|tr|td)\b[^>]*>/i', $html, $tags, PREG_SET_ORDER );

		$depth = [ 'table' => 0, 'tr' => 0, 'td' => 0 ];

		foreach ( $tags as $tag ) {

			$name = strtolower( $tag[2] );

			$depth[ $name ] += $tag[1] === '/' ? - 1 : 1;

			if ( $depth[ $name ] < 0 ) {
				return false;
			}
		}

		return array_sum( $depth ) === 0;
	}

	/**
	 * Change some of a block's settings, the JSON in its opening comment
	 *
	 * @param string $id
	 * @param array  $props the settings to change, null removes one
	 *
	 * @return true|WP_Error
	 */
	public function set_props( $id, $props ) {

		$ok = $this->check_block( $id );

		if ( is_wp_error( $ok ) ) {
			return $ok;
		}

		if ( ! is_array( $props ) ) {
			return new WP_Error( 'groundhogg_invalid_props', __( '`props` must be an object.', 'groundhogg' ) );
		}

		$block   = $this->blocks[ $id ];
		$current = $block['props'];

		foreach ( $props as $key => $value ) {
			if ( $value === null ) {
				unset( $current[ $key ] );
			} else {
				$current[ $key ] = $value;
			}
		}

		return $this->splice( $block['start'], $block['open_end'] - $block['start'], self::opening_comment( $block['type'], $id, $current ) );
	}

	/**
	 * A block's opening comment
	 *
	 * @param string $type
	 * @param string $id
	 * @param array  $props
	 *
	 * @return string
	 */
	protected static function opening_comment( string $type, string $id, array $props ): string {
		$json = empty( $props ) ? '{}' : wp_json_encode( $props, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		return '<!--' . $type . ':' . $id . ' ' . $json . '-->';
	}

	/**
	 * Find and replace text in the email's HTML, or in one block, and in the matching values of their settings
	 *
	 * It matches the HTML as it's written, so text split up by tags (like a <strong> in the middle) won't be found.
	 * Text that's written with HTML entities is found too, like "Tom & Jerry" for "Tom &amp; Jerry".
	 *
	 * @param string      $find
	 * @param string      $replace
	 * @param string|null $id  only in this block, and those inside it
	 * @param bool        $all replace every match, rather than needing there to be exactly one
	 *
	 * @return array|WP_Error count: how many were replaced, blocks: the ids of the blocks they were in
	 */
	public function replace_text( $find, $replace, $id = null, bool $all = false ) {

		if ( ! is_string( $find ) || $find === '' || ! is_string( $replace ) ) {
			return new WP_Error( 'groundhogg_invalid_find', __( '`find` must be a string that isn\'t empty, and `replace` a string.', 'groundhogg' ) );
		}

		if ( $id !== null ) {

			$ok = $this->check_block( $id );

			if ( is_wp_error( $ok ) ) {
				return $ok;
			}

			$from = $this->blocks[ $id ]['start'];
			$to   = $this->blocks[ $id ]['end'];
		} else {
			$from = 0;
			$to   = strlen( $this->html );
		}

		$segment = substr( $this->html, $from, $to - $from );

		// odd parts are comments, the block comments among them
		$parts = preg_split( '/(<!--.*?-->)/s', $segment, - 1, PREG_SPLIT_DELIM_CAPTURE );

		$candidates = array_unique( [
			[ $find, $replace ],
			[ htmlspecialchars( $find, ENT_NOQUOTES, 'UTF-8', false ), htmlspecialchars( $replace, ENT_NOQUOTES, 'UTF-8', false ) ],
			[ htmlspecialchars( $find, ENT_QUOTES, 'UTF-8', false ), htmlspecialchars( $replace, ENT_QUOTES, 'UTF-8', false ) ],
		], SORT_REGULAR );

		$search    = null;
		$with      = null;
		$positions = [];

		foreach ( $candidates as $candidate ) {

			$positions = [];
			$at        = $from;

			foreach ( $parts as $i => $part ) {

				if ( $i % 2 === 0 ) {
					$offset = 0;
					while ( ( $found = strpos( $part, $candidate[0], $offset ) ) !== false ) {
						$positions[] = $at + $found;
						$offset      = $found + strlen( $candidate[0] );
					}
				}

				$at += strlen( $part );
			}

			if ( ! empty( $positions ) ) {
				[ $search, $with ] = $candidate;
				break;
			}
		}

		$where = $id !== null
			/* translators: %s: block id */
			? sprintf( __( ' in block "%s"', 'groundhogg' ), $id )
			: '';

		if ( empty( $positions ) ) {
			return new WP_Error(
				'groundhogg_text_not_found',
				/* translators: 1: the text, 2: where it was looked for */
				sprintf( __( '"%1$s" wasn\'t found%2$s. It\'s matched against the HTML as written, so text broken up by tags (like <strong>) or written differently (like a curly apostrophe) won\'t match. Read the block with groundhogg/get-email-template and use "set_content" instead.', 'groundhogg' ), $find, $where )
			);
		}

		$in_blocks = array_values( array_unique( array_filter( array_map( [ $this, 'innermost_block_at' ], $positions ) ) ) );

		if ( count( $positions ) > 1 && ! $all ) {
			return new WP_Error(
				'groundhogg_text_not_unique',
				/* translators: 1: the text, 2: how many times, 3: where, 4: block ids */
				sprintf( __( '"%1$s" was found %2$d times%3$s (in blocks %4$s). Make `find` longer so it\'s unique, give the `block` to look in, or set `all` to replace every one.', 'groundhogg' ), $find, count( $positions ), $where, implode( ', ', $in_blocks ) )
			);
		}

		foreach ( $parts as $i => $part ) {

			if ( $i % 2 === 0 ) {
				$parts[ $i ] = str_replace( $search, $with, $part );
				continue;
			}

			// a block's settings often have the same text, like a button's, so keep them the same
			if ( preg_match( '/^<!--\s*([a-z]+):([A-Za-z0-9-]+)\s+(\{.*\})\s*-->$/s', $part, $comment ) ) {

				$props = json_decode( $comment[3], true );

				if ( ! is_array( $props ) ) {
					continue;
				}

				$changed = false;

				array_walk_recursive( $props, function ( &$value ) use ( $find, $replace, &$changed ) {
					if ( is_string( $value ) && strpos( $value, $find ) !== false ) {
						$value   = str_replace( $find, $replace, $value );
						$changed = true;
					}
				} );

				if ( $changed ) {
					$parts[ $i ] = self::opening_comment( $comment[1], $comment[2], $props );
				}
			}
		}

		$spliced = $this->splice( $from, $to - $from, implode( '', $parts ) );

		if ( is_wp_error( $spliced ) ) {
			return new WP_Error(
				'groundhogg_text_in_block_markup',
				/* translators: %s: the text */
				sprintf( __( '"%s" is (also) part of a block\'s own markup, like its id, and replacing it there would break the block. Give the `block` to look in, or use "set_content".', 'groundhogg' ), $find ),
				$spliced->get_error_data()
			);
		}

		return [
			'count'  => count( $positions ),
			'blocks' => $in_blocks,
		];
	}

	/**
	 * The innermost block a position in the content is in
	 *
	 * @param int $position
	 *
	 * @return string|null
	 */
	public function innermost_block_at( int $position ) {

		$found = null;

		foreach ( $this->order as $id ) {

			$block = $this->blocks[ $id ];

			if ( $block['start'] > $position ) {
				break;
			}

			if ( $position < $block['end'] ) {
				$found = $id;
			}
		}

		return $found;
	}

	/**
	 * A short description of each block, in order, to find the one to change without reading the whole email
	 *
	 * @return array[] id, type, parent and column (when in another block), summary
	 */
	public function outline(): array {

		$outline = [];

		foreach ( $this->order as $id ) {

			$block = $this->blocks[ $id ];
			$entry = [
				'id'   => $id,
				'type' => $block['type'],
			];

			if ( $block['parent'] !== null ) {
				$entry['parent'] = $block['parent'];
			}

			if ( $block['column'] !== null ) {
				$entry['column'] = $block['column'];
			}

			$summary = $this->summarize( $id );

			if ( $summary !== '' ) {
				$entry['summary'] = $summary;
			}

			$outline[] = $entry;
		}

		return $outline;
	}

	/**
	 * The HTML inside a block's <td>
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function inner_html( string $id ): string {

		$block = $this->blocks[ $id ];

		if ( $block['td_end'] === null ) {
			return substr( $this->html, $block['open_end'], $block['close_start'] - $block['open_end'] );
		}

		return substr( $this->html, $block['td_end'], $block['cell_end'] - $block['td_end'] );
	}

	/**
	 * A line about what's in a block
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function summarize( string $id ): string {

		$block = $this->blocks[ $id ];
		$props = $block['props'];
		$inner = $this->inner_html( $id );

		switch ( $block['type'] ) {

			case 'image':
				// the file name rather than the whole URL, which is usually long and doesn't say much more
				$src     = preg_match( '/<img\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1/i', $inner, $img ) ? $img[2] : (string) ( $props['src'] ?? '' );
				$file    = wp_basename( (string) wp_parse_url( $src, PHP_URL_PATH ) ) ?: $src;
				$alt     = preg_match( '/<img\b[^>]*\balt\s*=\s*(["\'])(.*?)\1/i', $inner, $img_alt ) ? self::decode( $img_alt[2] ) : '';
				$summary = $alt !== '' ? $alt . ' (' . $file . ')' : $file;
				break;

			case 'button':
				$href    = preg_match( '/<a\b[^>]*\bhref\s*=\s*(["\'])(.*?)\1/i', $inner, $link ) ? $link[2] : '';
				$summary = self::text_of( $inner ) . ( $href ? ' -> ' . $href : '' );
				break;

			case 'spacer':
				$summary = isset( $props['height'] ) ? $props['height'] . 'px' : '';
				break;

			case 'global':
				/* translators: %s: the id of the global block */
				$summary = isset( $props['templateId'] ) ? sprintf( __( 'global block #%s', 'groundhogg' ), $props['templateId'] ) : '';
				break;

			default:

				if ( ! empty( $block['children'] ) || ! empty( $block['columns_at'] ) ) {
					$summary = sprintf(
					/* translators: 1: how many columns, 2: how many blocks */
						__( '%1$d columns, %2$d blocks', 'groundhogg' ),
						count( $block['columns_at'] ),
						count( $block['children'] )
					);

					if ( ! empty( $props['layout'] ) ) {
						$summary = $props['layout'] . ', ' . $summary;
					}

					break;
				}

				$summary = self::text_of( $inner );
				break;
		}

		return self::truncate( $summary, 100 );
	}

	/**
	 * The text in some HTML, on one line
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function text_of( string $html ): string {
		$html = preg_replace( '/<(br|\/p|\/h[1-6]|\/li)\b[^>]*>/i', '$0 ', $html );

		return trim( preg_replace( '/\s+/u', ' ', self::decode( wp_strip_all_tags( $html ) ) ) );
	}

	/**
	 * Decode HTML entities, including HTML5 ones like &apos;
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	protected static function decode( string $text ): string {
		return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * @param string $text
	 * @param int    $length
	 *
	 * @return string
	 */
	protected static function truncate( string $text, int $length ): string {

		if ( mb_strlen( $text ) <= $length ) {
			return $text;
		}

		return rtrim( mb_substr( $text, 0, $length - 1 ) ) . '…';
	}

	/**
	 * The plain-text version of the email, like the editor saves it: each block's text, wrapped in its comments so
	 * the dynamic blocks are still rendered when it's sent (see Email::get_merged_alt_body()).
	 *
	 * @return string
	 */
	public function to_plain_text(): string {
		return trim( preg_replace( '/(\R\s*){3,}/', "\n\n", $this->plain_text_of( $this->top_level() ) ) );
	}

	/**
	 * @param string[] $ids
	 *
	 * @return string
	 */
	protected function plain_text_of( array $ids ): string {

		$texts = [];

		foreach ( $ids as $id ) {

			$block = $this->blocks[ $id ];
			$props = $block['props'];

			if ( ! empty( $props['hide_in_plain'] ) || ! empty( $props['hide_on_desktop'] ) ) {
				continue;
			}

			$text = ! empty( $block['children'] )
				? $this->plain_text_of( $block['children'] )
				: trim( html2markdown( $this->inner_html( $id ) ) );

			$name = $block['type'] . ':' . $id;

			$texts[] = '<!--' . $name . ' ' . ( $block['json'] !== '' ? $block['json'] : '{}' ) . '-->' . $text . '<!--/' . $name . '-->';
		}

		return implode( "\n\n", $texts );
	}
}
