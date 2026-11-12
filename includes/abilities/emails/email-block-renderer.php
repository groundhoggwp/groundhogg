<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Email;
use WP_Error;
use WP_HTML_Processor;
use WP_HTML_Tag_Processor;
use function Groundhogg\get_email_editor_footer;
use function Groundhogg\html2markdown;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Writes block editor content from compact block specs, and reads specs back out of block content.
 *
 * Only the block editor in the browser (email-block-editor.js) can otherwise render blocks, so an agent that builds an
 * email has to write every block's settings and its fully styled HTML by hand. With this, it describes blocks as JSON
 * instead, and the markup is written the way the editor writes it:
 *
 *     [ { "type": "heading", "content": "Hello {first}", "tag": "h1" },
 *       { "type": "text", "content": "<p>...</p>" },
 *       { "type": "button", "text": "Read more", "link": "https://..." } ]
 *
 * A spec is a block's type, optionally its id, and its settings (props), the same props the editor keeps for the
 * block. Props that are left out get the site's block defaults (gh_email_editor_block_defaults) and then the editor's
 * own. A columns block has its blocks in `columns`, an array of arrays of specs.
 *
 * The output is meant to be what the editor itself saves after opening the email, so that opening and saving it
 * in wp-admin doesn't change it. Each block is written, read back the way the editor reads blocks out of saved
 * content (the `attributes` getters in registerBlock(), parseBlockFromRow() and AdvancedStyleControls.parse()), and
 * written again, until it doesn't change. Style attributes are written by Email_Block_Style, which changes styles
 * like the browser does.
 *
 * Add-ons can render their own block types with the `groundhogg/email/block_renderers` filter, see types().
 */
class Email_Block_Renderer {

	/**
	 * Props that aren't kept in the block's comment, see blockCommentProps() in the editor
	 */
	const NOT_IN_COMMENT = [ 'advancedStyle', 'type', 'id', 'hide_on_mobile', 'hide_on_desktop', 'hide_in_plain', 'hide_in_browser' ];

	/**
	 * Visibility props, which are classes on the block's <td>
	 */
	const VISIBILITY = [
		'hide_on_mobile'  => 'hide-on-mobile',
		'hide_on_desktop' => 'hide-on-desktop',
		'hide_in_plain'   => 'hide-in-plain',
		'hide_in_browser' => 'hide-in-browser',
	];

	/**
	 * Props that are objects, so an empty one is written as {} and not []
	 */
	const OBJECT_PROPS = [ 'style', 'linkStyle', 'fontStyle', 'p', 'a', 'h1', 'h2', 'h3', 'borderStyle', 'columnStyle', 'advancedStyle' ];

	/**
	 * The column layouts, and the class and width of each of their columns, see columnLayouts in the editor
	 */
	const LAYOUTS = [
		'one_column'           => [ [ 'full', '100%' ] ],
		'two_columns'          => [ [ 'one-half', '50%' ], [ 'one-half', '50%' ] ],
		'three_columns'        => [ [ 'one-third', '33%' ], [ 'one-third', '33%' ], [ 'one-third', '33%' ] ],
		'four_columns'         => [ [ 'one-fourth', '25%' ], [ 'one-fourth', '25%' ], [ 'one-fourth', '25%' ], [ 'one-fourth', '25%' ] ],
		'three_columns_center' => [ [ 'one-fourth', '25%' ], [ 'one-half', '50%' ], [ 'one-fourth', '25%' ] ],
		'three_columns_left'   => [ [ 'one-half', '50%' ], [ 'one-fourth', '25%' ], [ 'one-fourth', '25%' ] ],
		'three_columns_right'  => [ [ 'one-fourth', '25%' ], [ 'one-fourth', '25%' ], [ 'one-half', '50%' ] ],
		'two_columns_right'    => [ [ 'one-third', '33%' ], [ 'two-third', '66%' ] ],
		'two_columns_left'     => [ [ 'two-third', '66%' ], [ 'one-third', '33%' ] ],
		'custom_columns'       => [],
	];

	/**
	 * Social networks and their names, see socialIcons in the editor
	 */
	const SOCIALS = [
		'facebook'    => 'Facebook',
		'instagram'   => 'Instagram',
		'linkedin'    => 'LinkedIn',
		'pinterest'   => 'Pinterest',
		'reddit'      => 'Reddit',
		'threads'     => 'Threads',
		'tiktok'      => 'TikTok',
		'tumblr'      => 'Tumblr',
		'twitch'      => 'Twitch',
		'twitter'     => '𝕏',
		'vimeo'       => 'Vimeo',
		'whatsapp'    => 'WhatsApp',
		'wordpress'   => 'WordPress',
		'youtube'     => 'YouTube',
		'github'      => 'GitHub',
		'truthsocial' => 'Truth Social',
		'odysee'      => 'Odysee',
		'discord'     => 'Discord',
		'rumble'      => 'Rumble',
		'bluesky'     => 'Bluesky',
	];

	/**
	 * Social icon themes, see socialIconThemes in the editor
	 */
	const SOCIAL_THEMES = [
		'brand-boxed',
		'brand-circle',
		'brand-icons',
		'black-boxed',
		'black-circle',
		'black-icons',
		'dark-grey-boxed',
		'dark-grey-circle',
		'dark-grey-icons',
		'grey-boxed',
		'grey-circle',
		'grey-icons',
		'white-boxed',
		'white-circle',
		'white-icons',
	];

	/**
	 * Tags the editor removes from HTML blocks, see cleanHTML()
	 */
	const UNSUPPORTED_TAGS = [ 'script', 'form', 'button', 'input', 'textarea', 'menu', 'iframe', 'audio', 'video', 'embed' ];

	/**
	 * The deepest columns can be nested
	 */
	const MAX_DEPTH = 3;

	/**
	 * The email's template settings: template, width, message_type
	 *
	 * @var array
	 */
	protected $context;

	/**
	 * Block types, see types()
	 *
	 * @var array[]
	 */
	protected $types;

	/**
	 * Ids that are already used, in the email or by blocks rendered so far
	 *
	 * @var bool[]
	 */
	protected $used_ids = [];

	/**
	 * Problems with the specs, collected while normalizing
	 *
	 * @var string[]
	 */
	protected $errors = [];

	/**
	 * Site settings, loaded when first needed
	 *
	 * @var array|null
	 */
	protected $site;

	/**
	 * @param array    $context      template (boxed, full_width or full_width_contained), width, message_type
	 * @param string[] $existing_ids ids already in the email, which new blocks can't have
	 */
	public function __construct( array $context = [], array $existing_ids = [] ) {

		$this->context = wp_parse_args( $context, [
			'template'     => 'boxed',
			'width'        => 640,
			'message_type' => 'marketing',
		] );

		$this->context['width'] = absint( $this->context['width'] ) ?: 640;

		foreach ( $existing_ids as $id ) {
			$this->used_ids[ $id ] = true;
		}
	}

	/**
	 * An email's template settings, which change how its blocks are written
	 *
	 * @param Email $email
	 *
	 * @return array template, width, message_type
	 */
	public static function context_of( Email $email ): array {
		return [
			'template'     => $email->get_meta( 'template' ) ?: 'boxed',
			'width'        => $email->get_meta( 'width' ) ?: 640,
			'message_type' => $email->get_message_type() ?: 'marketing',
		];
	}

	/**
	 * The block types that can be rendered from specs
	 *
	 * Each type has:
	 * - defaults: its default props, like `defaults` in registerBlock()
	 * - attributes: the props that are read from the block's HTML rather than kept in its comment
	 * - html: callable( array $props, Email_Block_Renderer $renderer ): string, the HTML inside the block's <td>
	 * - parse: callable( string $inner_html, array $props, Email_Block_Renderer $renderer ): array, the attributes
	 *   read back from that HTML (optional, without it they're kept in the comment)
	 * - css: callable( array $props, string $selector, Email_Block_Renderer $renderer ): string (optional)
	 *
	 * @return array[]
	 */
	public function types(): array {

		if ( isset( $this->types ) ) {
			return $this->types;
		}

		$types = [
			'text'    => [
				'defaults'   => [
					'content' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin egestas dolor non nulla varius, id fermentum ante euismod. Ut a sodales nisl, at maximus felis. Suspendisse potenti. Etiam fermentum magna nec diam lacinia, ut volutpat mauris accumsan. Nunc id convallis magna. Ut eleifend sem aliquet, volutpat sapien quis, condimentum leo.</p>',
					'p'       => self::font_defaults( [ 'fontSize' => 14 ] ),
					'a'       => [ 'color' => '#488aff' ],
					'h1'      => self::font_defaults( [ 'fontSize' => 42, 'fontWeight' => '500' ] ),
					'h2'      => self::font_defaults( [ 'fontSize' => 24, 'fontWeight' => '500' ] ),
					'h3'      => self::font_defaults( [ 'fontSize' => 20, 'fontWeight' => '500' ] ),
				],
				'attributes' => [ 'p', 'a', 'h1', 'h2', 'h3', 'content' ],
				'html'       => [ $this, 'text_html' ],
				'parse'      => [ $this, 'text_parse' ],
				'css'        => [ $this, 'text_css' ],
			],
			'heading' => [
				'defaults'   => [
					'content'   => 'Lorem ipsum dolor sit amet',
					'tag'       => 'h2',
					'fontStyle' => self::font_defaults( [ 'fontSize' => 24, 'fontWeight' => '500' ] ),
					'alignment' => 'left',
				],
				'attributes' => [ 'tag', 'fontStyle', 'alignment', 'content' ],
				'html'       => [ $this, 'heading_html' ],
				'parse'      => [ $this, 'heading_parse' ],
			],
			'button'  => [
				'defaults'   => [
					'link'            => '',
					'align'           => 'center',
					'text'            => 'Click me!',
					'size'            => 'md',
					'backgroundColor' => '#dd3333',
					'style'           => [
						'color'      => '#ffffff',
						'fontSize'   => 20,
						'fontWeight' => '600',
						'fontFamily' => 'Arial, sans-serif',
					],
				],
				'attributes' => [ 'text', 'link', 'align', 'borderStyle', 'backgroundColor', 'style' ],
				'html'       => [ $this, 'button_html' ],
				'parse'      => [ $this, 'button_parse' ],
			],
			'menu'    => [
				'defaults'   => [
					'links'     => [ [ 'Link 1', '' ], [ 'Link 2', '' ], [ 'Link 3', '' ] ],
					'align'     => 'center',
					'fontStyle' => [
						'fontSize'   => 13,
						'fontFamily' => 'system-ui, sans-serif',
						'fontWeight' => 400,
					],
					'gap'       => 20,
					'separator' => '',
				],
				'attributes' => [ 'links', 'fontStyle', 'gap', 'align' ],
				'html'       => [ $this, 'menu_html' ],
				'parse'      => [ $this, 'menu_parse' ],
			],
			'image'   => [
				'defaults'   => [
					'src'    => 'https://placehold.co/600x338?text=Image',
					'alt'    => 'placeholder image',
					'title'  => 'placeholder image',
					'width'  => 600,
					'height' => 338,
					'align'  => 'center',
				],
				'attributes' => [ 'src', 'height', 'width', 'alt', 'link', 'borderStyle', 'align' ],
				'html'       => [ $this, 'image_html' ],
				'parse'      => [ $this, 'image_parse' ],
			],
			'spacer'  => [
				'defaults'   => [ 'height' => 20 ],
				'attributes' => [ 'height' ],
				'html'       => [ $this, 'spacer_html' ],
				'parse'      => [ $this, 'spacer_parse' ],
			],
			'divider' => [
				'defaults'   => [
					'height'    => 3,
					'color'     => '#ccc',
					'width'     => 100,
					'lineStyle' => 'solid',
				],
				'attributes' => [ 'height', 'width', 'color', 'lineStyle' ],
				'html'       => [ $this, 'divider_html' ],
				'parse'      => [ $this, 'divider_parse' ],
			],
			'html'    => [
				'defaults'   => [ 'content' => '<p>HTML CODE</p>' ],
				'attributes' => [ 'content' ],
				'html'       => [ $this, 'html_html' ],
				'parse'      => [ $this, 'html_parse' ],
			],
			'social'  => [
				'defaults'   => [
					'align'   => 'center',
					'theme'   => 'brand-circle',
					'socials' => [],
					'gap'     => 10,
					'size'    => 24,
					'use'     => 'global',
				],
				'attributes' => [ 'size', 'gap', 'theme', 'socials' ],
				'html'       => [ $this, 'social_html' ],
				'parse'      => [ $this, 'social_parse' ],
			],
			'footer'  => [
				'defaults'   => [
					'style'         => self::font_defaults( [ 'fontSize' => 13, 'color' => '#999', 'lineHeight' => 1 ] ),
					'linkStyle'     => [ 'color' => '#488aff' ],
					'tel'           => true,
					'terms'         => true,
					'privacyPolicy' => true,
				],
				'attributes' => [],
				'html'       => [ $this, 'footer_html' ],
			],
			'columns' => [
				'defaults'   => [
					'layout'        => 'two_columns',
					'columns'       => [ [], [], [], [] ],
					'customColumns' => [ '', '', '' ],
					'gap'           => 10,
					'responsive'    => true,
				],
				'attributes' => [ 'layout', 'verticalAlign', 'responsive', 'columnStyle', 'columns', 'customColumns' ],
				'html'       => [ $this, 'columns_html' ],
				'parse'      => [ $this, 'columns_parse' ],
				'css'        => [ $this, 'columns_css' ],
			],
			'global'  => [
				'defaults'   => [
					'templateId'   => '',
					'templateName' => 'Global Block',
					'children'     => [],
				],
				'attributes' => [ 'children', 'templateName' ],
				'html'       => '__return_empty_string',
				'parse'      => function () {
					return [];
				},
				'css'        => function ( $props ) {
					return '.global-block.t-' . $props['templateId'] . '{visibility: hidden;}';
				},
			],
		];

		/**
		 * Block types that can be written from block specs by the email abilities
		 *
		 * To add a type, give it what the block's registerBlock() has in the editor:
		 * - defaults: array, its default props
		 * - attributes: string[], the props that the editor reads from the block's HTML (its `attributes` getters),
		 *   which aren't kept in the block's comment
		 * - html: callable( array $props, Email_Block_Renderer $renderer ): string, the HTML inside the block's <td>,
		 *   the same as the editor's html() writes when it saves
		 * - parse: callable( string $inner_html, array $props, Email_Block_Renderer $renderer ): array, the
		 *   attributes read back from that HTML, like the `attributes` getters (optional)
		 * - css: callable( array $props, string $selector, Email_Block_Renderer $renderer ): string, the block's CSS
		 *   (optional)
		 *
		 * The type must also be one of `groundhogg/email/block_types`.
		 *
		 * @param array[]              $types
		 * @param Email_Block_Renderer $renderer
		 */
		$this->types = apply_filters( 'groundhogg/email/block_renderers', $types, $this );

		return $this->types;
	}

	/**
	 * The site's block editor settings: block defaults, global fonts, palette and socials, and the footer
	 *
	 * @return array
	 */
	public function site(): array {

		if ( isset( $this->site ) ) {
			return $this->site;
		}

		$defaults = get_option( 'gh_email_editor_block_defaults' );
		$fonts    = get_option( 'gh_email_editor_global_fonts' );
		$palette  = get_option( 'gh_email_editor_color_palette' );
		$socials  = get_option( 'gh_email_editor_global_social_accounts' );

		// the same fallbacks the editor uses when they aren't set
		if ( ! is_array( $fonts ) || empty( $fonts ) ) {
			$fonts = [
				[ 'name' => 'Paragraph', 'id' => 'paragraph', 'style' => self::font_defaults( [] ) ],
				[ 'name' => 'Heading 1', 'id' => 'heading-1', 'style' => self::font_defaults( [ 'fontSize' => 42 ] ) ],
				[ 'name' => 'Heading 2', 'id' => 'heading-2', 'style' => self::font_defaults( [ 'fontSize' => 36 ] ) ],
				[ 'name' => 'Heading 3', 'id' => 'heading-3', 'style' => self::font_defaults( [ 'fontSize' => 24 ] ) ],
			];
		}

		if ( ! is_array( $palette ) || empty( $palette ) ) {
			$palette = [ '#000', '#fff', '#dd3333', '#DD9933', '#EEEE22', '#81D742', '#1E73BE', '#8224E3' ];
		}

		$this->site = [
			'block_defaults' => is_array( $defaults ) ? $defaults : [],
			'fonts'          => array_values( array_filter( $fonts, 'is_array' ) ),
			'palette'        => array_values( $palette ),
			'socials'        => is_array( $socials ) ? array_values( $socials ) : [],
			'footer'         => get_email_editor_footer(),
		];

		return $this->site;
	}

	/**
	 * The site's palette, global fonts, social accounts and block defaults as Markdown, for the email authoring guide
	 *
	 * @return string
	 */
	public function design_summary(): string {

		$site  = $this->site();
		$lines = [ '## This site\'s design', '' ];

		$palette = [];

		foreach ( $site['palette'] as $i => $color ) {
			$palette[] = sprintf( '`palette:%d` %s', $i, $color );
		}

		$lines[] = '- Palette: ' . implode( ', ', $palette ) . '.';

		$fonts = [];

		foreach ( $site['fonts'] as $font ) {
			$style   = (array) ( $font['style'] ?? [] );
			$fonts[] = sprintf(
				'"%s" (%s, %spx, weight %s)',
				$font['name'] ?? '',
				Email_Block_Style::normalize_font( (string) ( $style['fontFamily'] ?? '' ) ),
				$style['fontSize'] ?? 16,
				$style['fontWeight'] ?? 'normal'
			);
		}

		$lines[] = '- Global fonts, for `use`: ' . implode( ', ', $fonts ) . '.';

		$lines[] = empty( $site['socials'] )
			? '- No social accounts are set up, so a `social` block needs `use: "custom"` and its own `socials`.'
			: sprintf( '- %d social accounts are set up, which a `social` block shows by default.', count( $site['socials'] ) );

		$defaults = array_diff_key( $site['block_defaults'], [ 'version' => true ] );

		if ( ! empty( $defaults ) ) {
			$lines[] = '- Block defaults set on this site, used for the settings a spec leaves out:';

			foreach ( $defaults as $type => $props ) {
				$lines[] = sprintf( '  - `%s`: `%s`', $type, self::json( (array) $props ) );
			}
		}

		return implode( "\n", $lines );
	}

	/**
	 * Default font settings, like fontDefaults() in the editor
	 *
	 * @param array $style
	 *
	 * @return array
	 */
	public static function font_defaults( array $style ): array {
		return array_merge( [
			'lineHeight'    => '1.4',
			'fontFamily'    => 'system-ui, sans-serif',
			'fontWeight'    => 'normal',
			'fontSize'      => 13,
			'fontStyle'     => 'normal',
			'textTransform' => 'none',
		], $style );
	}

	/**
	 * A type's default props: the editor's defaults, then the site's block defaults
	 *
	 * @param string $type
	 *
	 * @return array
	 */
	public function defaults( string $type ): array {
		$defaults = $this->types()[ $type ]['defaults'] ?? [];
		$site     = $this->site()['block_defaults'][ $type ] ?? [];

		return array_merge( $defaults, is_array( $site ) ? $site : [] );
	}

	/**
	 * Render a whole email's content from specs
	 *
	 * @param array $specs
	 *
	 * @return array|WP_Error content, css (the block CSS, the email's `css` meta), plain_text, ids
	 */
	public function render( array $specs ) {

		$rows = $this->render_rows( $specs );

		if ( is_wp_error( $rows ) ) {
			return $rows;
		}

		return [
			'content'    => '<table cellpadding="0" cellspacing="0" width="100%" role="presentation">' . $rows['html'] . '</table>',
			'css'        => $rows['css'],
			'plain_text' => $this->plain_text( $rows['blocks'] ),
			'ids'        => $rows['ids'],
		];
	}

	/**
	 * The block CSS and plain text of an email's content, as the editor makes them when it saves
	 *
	 * For content that was changed without the editor, like with groundhogg/edit-email.
	 *
	 * @param string $content
	 *
	 * @return array css, plain_text
	 */
	public function css_and_plain_text( string $content ): array {

		$blocks = $this->read( $content );

		return [
			'css'        => $this->blocks_css( $blocks ),
			'plain_text' => $this->plain_text( $blocks ),
		];
	}

	/**
	 * The plain text version of blocks, like renderBlocksPlainText() in the editor: each block's text, wrapped in its
	 * comments so dynamic blocks can still be rendered when the email is sent (see Email::get_merged_alt_body())
	 *
	 * @param array[] $blocks blocks as they're read from content, see read()
	 *
	 * @return string
	 */
	public function plain_text( array $blocks ): string {

		$texts = [];

		foreach ( $blocks as $block ) {

			if ( ! empty( $block['hide_on_desktop'] ) || ! empty( $block['hide_in_plain'] ) ) {
				continue;
			}

			$text = $this->block_plain_text( $block );

			// a block without a plain text version, like a spacer, is left out
			if ( $text === null ) {
				continue;
			}

			$name    = $block['type'] . ':' . $block['id'];
			$texts[] = '<!--' . $name . ' ' . $this->comment_json( $block ) . '-->' . $text . '<!--/' . $name . '-->';
		}

		return trim( preg_replace( '/(\R){3,}/', "\n\n", implode( "\n\n", $texts ) ) );
	}

	/**
	 * A block's plain text, like each block type's plainText() in the editor
	 *
	 * @param array $block
	 *
	 * @return string|null null for a block that has none
	 */
	protected function block_plain_text( array $block ) {

		$props = array_merge( $this->defaults( $block['type'] ), $block );
		$type  = $this->types()[ $block['type'] ] ?? null;

		switch ( $block['type'] ) {

			case 'text':
			case 'html':
				return self::markdown( (string) $props['content'] );

			case 'heading':
				return self::markdown( '<' . $props['tag'] . '>' . $props['content'] . '</' . $props['tag'] . '>' );

			case 'button':
				return '[' . self::js_string( $props['text'] ) . '](' . ( $props['link'] ?? '' ) . ')';

			case 'menu':
				return implode( ' | ', array_map( function ( $link ) {
					return '[' . ( $link[0] ?? '' ) . '](' . ( $link[1] ?? '' ) . ')';
				}, (array) $props['links'] ) );

			case 'image':
				$link  = $props['link'] ?? '';
				$image = '![' . ( $props['alt'] ?: 'image' ) . '](' . ( $props['src'] ?? '' ) . ')';

				return $link ? "[$image]($link)" : $image;

			case 'divider':
				return '---';

			case 'spacer':
				return null;

			case 'social':
				$socials = ( $props['use'] ?? 'global' ) === 'global' ? $this->site()['socials'] : (array) $props['socials'];

				return implode( ' | ', array_map( function ( $social ) {
					$social = array_values( (array) $social );

					return '[' . ( $social[0] ?? '' ) . '](' . ( $social[1] ?? '' ) . ')';
				}, $socials ) );

			case 'footer':
				$footer = $this->site()['footer'];
				$links  = [];

				foreach ( [ 'tel' => 'tel', 'privacy' => 'privacyPolicy', 'terms' => 'terms' ] as $link => $prop ) {
					if ( $footer['links'][ $link ] && ! empty( $props[ $prop ] ) ) {
						$links[] = $footer['links'][ $link ];
					}
				}

				return implode( "  \n", [
					'Copyright ' . $footer['business_name'],
					$footer['address'],
					self::markdown( implode( ' | ', $links ) ),
					self::markdown( $footer['unsubscribe'] ),
				] );

			case 'columns':
				$columns = array_slice( (array) $props['columns'], 0, $this->column_count( $props ) );

				return implode( "\n\n", array_map( [ $this, 'plain_text' ], $columns ) );

			case 'global':
				// the global block's own plain text is put in when the email is sent
				return '';
		}

		if ( $type && is_callable( $type['plain_text'] ?? null ) ) {
			return (string) call_user_func( $type['plain_text'], $props, $this );
		}

		return self::markdown( $this->inner_html( $block ) );
	}

	/**
	 * HTML as markdown-ish plain text, like extractPlainText() in the editor
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function markdown( string $html ): string {
		return $html === '' ? '' : trim( html2markdown( $html ) );
	}

	/**
	 * Render the rows of blocks from specs, without the email's table around them, to add to an email
	 *
	 * @param array $specs
	 * @param bool  $top_level whether they're going at the top of the email, rather than in a column
	 *
	 * @return array|WP_Error html, css, ids (of the top level blocks)
	 */
	public function render_rows( array $specs, bool $top_level = true ) {

		if ( empty( $specs ) || ! wp_is_numeric_array( $specs ) ) {
			return new WP_Error( 'groundhogg_invalid_blocks', __( '`blocks` must be an array of block specs, like [{"type": "text", "content": "<p>Hello</p>"}].', 'groundhogg' ) );
		}

		$this->errors = [];
		$blocks       = [];

		foreach ( array_values( $specs ) as $i => $spec ) {
			$blocks[] = $this->normalize( $spec, (string) $i, 0 );
		}

		if ( ! empty( $this->errors ) ) {
			return Email_Block_Document::error_from( $this->errors, __( 'Some block specs have problems, so nothing was saved:', 'groundhogg' ) );
		}

		$html   = '';
		$parsed = [];

		foreach ( $blocks as $block ) {
			list( $markup, $read ) = $this->render_block( $block, $top_level );
			$html     .= $markup;
			$parsed[] = $read;
		}

		return [
			'html'   => $html,
			'css'    => $this->blocks_css( $parsed ),
			'ids'    => wp_list_pluck( $blocks, 'id' ),
			'blocks' => $parsed,
		];
	}

	/**
	 * Check a spec, fill in its defaults and give it an id
	 *
	 * @param mixed  $spec
	 * @param string $path where it is in the specs, for errors
	 * @param int    $depth
	 *
	 * @return array
	 */
	protected function normalize( $spec, string $path, int $depth ): array {

		if ( ! is_array( $spec ) || wp_is_numeric_array( $spec ) ) {
			/* translators: %s: where the block is in the specs, like 0 or 2.columns.1.0 */
			$this->errors[] = sprintf( __( 'Block %s isn\'t an object.', 'groundhogg' ), $path );

			return [ 'type' => '', 'id' => '' ];
		}

		$type  = isset( $spec['type'] ) && is_string( $spec['type'] ) ? $spec['type'] : '';
		$types = $this->types();

		if ( ! isset( $types[ $type ] ) ) {
			$this->errors[] = sprintf(
			/* translators: 1: where the block is in the specs, 2: its type, 3: the types that can be used */
				__( 'Block %1$s has the type "%2$s", which can\'t be written from a spec. Use one of: %3$s.', 'groundhogg' ),
				$path,
				$type,
				implode( ', ', array_keys( $types ) )
			);

			return [ 'type' => '', 'id' => '' ];
		}

		$id = $this->claim_id( $spec['id'] ?? null, $path, $type );

		$props = $this->resolve_tokens( array_diff_key( $spec, array_flip( self::NOT_IN_COMMENT ) ) );
		$props = $this->merge_props( $this->defaults( $type ), $props );

		$block = array_merge( [ 'type' => $type, 'id' => $id ], $props );

		$block['advancedStyle'] = $this->normalize_advanced_style( $this->resolve_tokens( (array) ( $spec['advancedStyle'] ?? [] ) ) );

		foreach ( array_keys( self::VISIBILITY ) as $flag ) {
			if ( ! empty( $spec[ $flag ] ) ) {
				$block[ $flag ] = true;
			}
		}

		switch ( $type ) {

			case 'text':
				foreach ( [ 'p', 'h1', 'h2', 'h3' ] as $tag ) {
					$block[ $tag ] = $this->normalize_font( (array) $block[ $tag ], true );
				}
				$block['a']       = $this->normalize_font( (array) $block['a'], true );
				$block['content'] = (string) $block['content'];
				break;

			case 'heading':
				$block['fontStyle'] = $this->normalize_font( (array) $block['fontStyle'], false );
				$block['tag']       = in_array( $block['tag'], [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], true ) ? $block['tag'] : 'h2';
				$block['content']   = (string) $block['content'];
				break;

			case 'button':
				$block['style'] = $this->normalize_font( (array) $block['style'], false );
				break;

			case 'menu':
				$block['fontStyle'] = $this->normalize_font( (array) $block['fontStyle'], false );
				$block['links']     = array_values( array_map( function ( $link ) {
					return [ (string) ( $link[0] ?? $link['text'] ?? '' ), (string) ( $link[1] ?? $link['link'] ?? $link['href'] ?? '' ) ];
				}, array_filter( (array) $block['links'], 'is_array' ) ) );
				break;

			case 'footer':
				$block['style'] = $this->normalize_font( (array) $block['style'], true );
				break;

			case 'columns':

				if ( ! isset( self::LAYOUTS[ $block['layout'] ] ) ) {
					/* translators: 1: where the block is, 2: the layouts */
					$this->errors[] = sprintf( __( 'Block %1$s: layout must be one of %2$s.', 'groundhogg' ), $path, implode( ', ', array_keys( self::LAYOUTS ) ) );
				}

				if ( $depth >= self::MAX_DEPTH ) {
					/* translators: %s: where the block is */
					$this->errors[] = sprintf( __( 'Block %s: columns are nested too deep.', 'groundhogg' ), $path );
					$block['columns'] = [ [], [], [], [] ];
					break;
				}

				$columns = [];

				foreach ( array_values( (array) $block['columns'] ) as $c => $column ) {

					$children = [];

					foreach ( array_values( (array) $column ) as $i => $child ) {
						$children[] = $this->normalize( $child, "$path.columns.$c.$i", $depth + 1 );
					}

					$columns[] = $children;
				}

				// like the editor, so blocks aren't lost when the layout changes
				while ( count( $columns ) < 4 ) {
					$columns[] = [];
				}

				$block['columns'] = $columns;
				break;
		}

		return $block;
	}

	/**
	 * A block's id: the one it was given if it's valid and not used, or a new one
	 *
	 * @param mixed  $id
	 * @param string $path
	 * @param string $type
	 *
	 * @return string
	 */
	protected function claim_id( $id, string $path, string $type ): string {

		if ( $id !== null && $id !== '' ) {

			$id = (string) $id;

			if ( ! preg_match( '/^[A-Za-z0-9-]+$/', $id ) ) {
				/* translators: 1: where the block is, 2: its id */
				$this->errors[] = sprintf( __( 'Block %1$s: the id "%2$s" can only have letters, digits and hyphens.', 'groundhogg' ), $path, $id );
			} else if ( isset( $this->used_ids[ $id ] ) ) {
				/* translators: 1: where the block is, 2: its id */
				$this->errors[] = sprintf( __( 'Block %1$s: the id "%2$s" is already used by another block. Leave it out to get a new one.', 'groundhogg' ), $path, $id );
			}

			$this->used_ids[ $id ] = true;

			return $id;
		}

		do {
			$id = $type . '-' . substr( md5( wp_generate_uuid4() ), 0, 6 );
		} while ( isset( $this->used_ids[ $id ] ) );

		$this->used_ids[ $id ] = true;

		return $id;
	}

	/**
	 * Merge props over defaults, and the settings in the object props (like style) over the default ones
	 *
	 * @param array $defaults
	 * @param array $props
	 *
	 * @return array
	 */
	protected function merge_props( array $defaults, array $props ): array {

		foreach ( $props as $key => $value ) {
			if ( isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) && is_array( $value ) && ! wp_is_numeric_array( $defaults[ $key ] ) && ! wp_is_numeric_array( $value ) ) {
				$props[ $key ] = array_merge( $defaults[ $key ], $value );
			}
		}

		return array_merge( $defaults, $props );
	}

	/**
	 * Turn "palette:N" into the site's Nth palette color, anywhere in the props
	 *
	 * @param array $props
	 *
	 * @return array
	 */
	protected function resolve_tokens( array $props ): array {

		$palette = $this->site()['palette'];

		array_walk_recursive( $props, function ( &$value ) use ( $palette ) {
			if ( is_string( $value ) && preg_match( '/^palette:(\d+)$/', trim( $value ), $m ) && isset( $palette[ (int) $m[1] ] ) ) {
				$value = $palette[ (int) $m[1] ];
			}
		} );

		return $props;
	}

	/**
	 * Font settings an agent wrote, as the editor keeps them
	 *
	 * - fontSize as a number of pixels
	 * - fontFamily as one of the editor's fonts
	 * - `use` can name a global font, and gives its id. Blocks that don't support global fonts get the font's
	 *   settings instead.
	 *
	 * @param array $style
	 * @param bool  $global_fonts whether the block supports global fonts
	 *
	 * @return array
	 */
	protected function normalize_font( array $style, bool $global_fonts ): array {

		if ( isset( $style['fontSize'] ) && is_string( $style['fontSize'] ) && preg_match( '/^\s*(\d+(\.\d+)?)\s*(px)?\s*$/', $style['fontSize'], $m ) ) {
			$style['fontSize'] = ! empty( $m[2] ) ? (float) $m[1] : (int) $m[1];
		}

		if ( ! empty( $style['fontFamily'] ) && is_string( $style['fontFamily'] ) ) {
			$style['fontFamily'] = Email_Block_Style::normalize_font( $style['fontFamily'] );
		}

		if ( isset( $style['use'] ) && $style['use'] !== 'custom' ) {

			$font = $this->global_font( (string) $style['use'] );

			if ( ! $font ) {
				$style['use'] = 'custom';
			} else if ( $global_fonts ) {
				$style['use'] = $font['id'];
			} else {
				unset( $style['use'] );
				$style = array_merge( $style, (array) $font['style'] );
			}
		}

		return $style;
	}

	/**
	 * A global font by id or name
	 *
	 * @param string $id_or_name
	 *
	 * @return array|null
	 */
	public function global_font( string $id_or_name ) {

		foreach ( $this->site()['fonts'] as $font ) {
			if ( ( $font['id'] ?? '' ) === $id_or_name ) {
				return $font;
			}
		}

		foreach ( $this->site()['fonts'] as $font ) {
			if ( strcasecmp( (string) ( $font['name'] ?? '' ), $id_or_name ) === 0 ) {
				return $font;
			}
		}

		return null;
	}

	/**
	 * A block's advanced style (padding, border, background) as the editor keeps it
	 *
	 * Padding can be a number for every side, a CSS value like "10px 20px", or top/right/bottom/left
	 *
	 * @param array $style
	 *
	 * @return array
	 */
	protected function normalize_advanced_style( array $style ): array {

		if ( isset( $style['padding'] ) ) {
			$style['padding'] = self::four_sides( $style['padding'] );
		}

		foreach ( [ 'borderWidth', 'borderRadius' ] as $key ) {
			if ( isset( $style[ $key ] ) ) {
				$style[ $key ] = self::four_sides( $style[ $key ] );
			}
		}

		return $style;
	}

	/**
	 * Top, right, bottom and left from a number, a CSS value or an object, with `linked` like the editor's controls
	 *
	 * @param mixed $value
	 *
	 * @return array
	 */
	protected static function four_sides( $value ): array {

		if ( is_numeric( $value ) ) {
			$value = [ $value, $value, $value, $value ];
		} else if ( is_string( $value ) ) {
			$value = Email_Block_Style::expand( $value ) ?: [ 0, 0, 0, 0 ];
		}

		$value = (array) $value;
		$sides = [];

		// a side that's null wasn't set, like NaN in the editor
		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $i => $side ) {
			$sides[ $side ] = array_key_exists( $side, $value ) ? $value[ $side ] : ( $value[ $i ] ?? 0 );
		}

		$sides = array_map( function ( $side ) {
			return is_float( $side ) && is_nan( $side ) ? $side : self::parse_int( $side );
		}, $sides );

		$sides['linked'] = $sides['top'] === $sides['right'] && $sides['top'] === $sides['bottom'] && $sides['top'] === $sides['left'];

		return $sides;
	}

	/**
	 * Render a block, read it back like the editor does and render that, until it doesn't change
	 *
	 * @param array $block
	 * @param bool  $top_level
	 *
	 * @return array the markup and the block as the editor would read it
	 */
	protected function render_block( array $block, bool $top_level ): array {

		$markup = $this->block_markup( $block, $top_level );

		for ( $i = 0; $i < 3; $i ++ ) {

			$read = $this->read_block( $markup );

			if ( ! $read ) {
				break;
			}

			$again = $this->block_markup( $read, $top_level );

			if ( $again === $markup ) {
				return [ $markup, $read ];
			}

			$block  = $read;
			$markup = $again;
		}

		return [ $markup, $block ];
	}

	/**
	 * A block's row, like BlockHTML() in the editor
	 *
	 * @param array $block
	 * @param bool  $top_level
	 *
	 * @return string
	 */
	public function block_markup( array $block, bool $top_level = true ): string {

		$type     = $block['type'];
		$id       = $block['id'];
		$advanced = (array) ( $block['advancedStyle'] ?? [] );
		$width    = ! empty( $advanced['width'] ) ? $advanced['width'] : $this->context['width'];

		$inner = $this->inner_html( $block );

		if ( $top_level && $this->context['template'] === 'full_width_contained' ) {
			$inner = self::el( 'table', [
				'cellspacing' => '0',
				'cellpadding' => '0',
				'role'        => 'presentation',
				'align'       => 'center',
				'class'       => 'email-columns responsive',
			], self::el( 'tr', [ 'class' => 'email-columns-row' ], self::el( 'td', [
				'width' => $width,
				'id'    => "b-$id-inner",
				'class' => 'email-columns-cell',
				'style' => ( new Email_Block_Style() )->set( 'width', "{$width}px" ),
			], $inner ) ) );
		}

		$classes = [ "b-$type" ];

		foreach ( self::VISIBILITY as $flag => $class ) {
			if ( ! empty( $block[ $flag ] ) ) {
				$classes[] = $class;
			}
		}

		$style = $this->advanced_style( $advanced )->set( 'overflow', 'hidden' );

		$td = self::el( 'td', [
			'id'         => "b-$id",
			'class'      => implode( ' ', $classes ),
			'bgcolor'    => $advanced['backgroundColor'] ?? '',
			'background' => $advanced['backgroundImage'] ?? '',
			'valign'     => 'top',
			'style'      => $style,
		], $inner );

		return '<tr><!--' . $type . ':' . $id . ' ' . $this->comment_json( $block ) . '-->' . $td . '<!--/' . $type . ':' . $id . '--></tr>';
	}

	/**
	 * The HTML inside a block's <td>
	 *
	 * @param array $block
	 *
	 * @return string
	 */
	public function inner_html( array $block ): string {

		$type = $this->types()[ $block['type'] ] ?? null;

		if ( ! $type || ! is_callable( $type['html'] ?? null ) ) {
			return '';
		}

		return (string) call_user_func( $type['html'], array_merge( $this->defaults( $block['type'] ), $block ), $this );
	}

	/**
	 * The settings kept in a block's comment, like blockCommentProps() in the editor
	 *
	 * @param array $block
	 *
	 * @return string
	 */
	protected function comment_json( array $block ): string {

		$attributes = $this->types()[ $block['type'] ]['attributes'] ?? [];
		$props      = array_diff_key( $block, array_flip( array_merge( self::NOT_IN_COMMENT, $attributes ) ) );

		return self::json( $props );
	}

	/**
	 * JSON like JSON.stringify(), with objects that are empty written as {}
	 *
	 * @param array $props
	 *
	 * @return string
	 */
	public static function json( array $props ): string {

		if ( empty( $props ) ) {
			return '{}';
		}

		$props = self::objects_for_json( $props );

		return (string) wp_json_encode( $props, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
	}

	/**
	 * @param array $props
	 *
	 * @return array|object
	 */
	protected static function objects_for_json( array $props ) {

		foreach ( $props as $key => $value ) {
			if ( is_array( $value ) ) {
				$props[ $key ] = empty( $value ) && in_array( $key, self::OBJECT_PROPS, true ) ? new \stdClass() : self::objects_for_json( $value );
			} else if ( is_float( $value ) && ( is_nan( $value ) || is_infinite( $value ) ) ) {
				$props[ $key ] = null;
			}
		}

		return $props;
	}

	/**
	 * A block's own style, like AdvancedStyleControls.getInlineStyle()
	 *
	 * @param array $advanced
	 *
	 * @return Email_Block_Style
	 */
	protected function advanced_style( array $advanced ): Email_Block_Style {

		$padding = $advanced['padding'] ?? [ 'top' => 5, 'right' => 5, 'left' => 5, 'bottom' => 5 ];
		$props   = [];

		if ( self::some_set( (array) $padding ) ) {
			$props['padding'] = self::extract4( (array) $padding );
		}

		$props = self::add_border_style( $advanced, $props );

		$background = $advanced['backgroundColor'] ?? 'transparent';

		if ( $background !== 'transparent' ) {
			$props['backgroundColor'] = $background;
		}

		if ( ! empty( $advanced['backgroundImage'] ) ) {
			unset( $props['backgroundColor'] );
			$props['background'] = sprintf( 'url(%s) %s / %s %s %s', $advanced['backgroundImage'], $advanced['backgroundPosition'] ?? '', $advanced['backgroundSize'] ?? '', $advanced['backgroundRepeat'] ?? '', $background );
		}

		return ( new Email_Block_Style() )->assign( $props );
	}

	/**
	 * Whether any of the values is set and not 0, like `Object.values( x ).some( v => v && parseInt( v ) !== 0 )`
	 *
	 * The `linked` flag of the editor's controls counts too, which is why padding that's 0 on every side is still
	 * written when it comes from the editor.
	 *
	 * @param array $values
	 *
	 * @return bool
	 */
	protected static function some_set( array $values ): bool {

		foreach ( $values as $value ) {

			if ( ! self::truthy( $value ) ) {
				continue;
			}

			$int = self::parse_int( $value );

			if ( is_nan( $int ) || $int !== 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Truthiness like JavaScript's
	 *
	 * @param mixed $value
	 *
	 * @return bool
	 */
	protected static function truthy( $value ): bool {

		if ( is_float( $value ) && is_nan( $value ) ) {
			return false;
		}

		if ( is_string( $value ) ) {
			return $value !== '';
		}

		return (bool) $value;
	}

	/**
	 * parseInt() like JavaScript's, NAN when there's no number
	 *
	 * @param mixed $value
	 *
	 * @return int|float
	 */
	public static function parse_int( $value ) {

		if ( is_int( $value ) ) {
			return $value;
		}

		if ( is_float( $value ) ) {
			return is_nan( $value ) || is_infinite( $value ) ? NAN : (int) $value;
		}

		if ( is_bool( $value ) || $value === null || is_array( $value ) ) {
			return NAN;
		}

		if ( preg_match( '/^\s*([+-]?\d+)/', (string) $value, $m ) ) {
			return (int) $m[1];
		}

		return NAN;
	}

	/**
	 * Four sides as a CSS value, like extract4() in the editor
	 *
	 * @param array $sides
	 *
	 * @return string
	 */
	protected static function extract4( array $sides ): string {

		$values = [];

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$int      = self::parse_int( $sides[ $side ] ?? 0 );
			$int      = is_nan( $int ) ? 0 : $int;
			$values[] = $int !== 0 ? $int . 'px' : '0';
		}

		return implode( ' ', $values );
	}

	/**
	 * Border props, like addBorderStyle() in the editor
	 *
	 * @param array $style   borderStyle, borderColor, borderWidth, borderRadius
	 * @param array $initial props to add them to
	 *
	 * @return array
	 */
	protected static function add_border_style( array $style, array $initial = [] ): array {

		$border_style  = $style['borderStyle'] ?? 'none';
		$border_color  = $style['borderColor'] ?? 'transparent';
		$border_width  = (array) ( $style['borderWidth'] ?? [] );
		$border_radius = (array) ( $style['borderRadius'] ?? [] );

		if ( self::some_set( $border_radius ) ) {
			$initial['borderRadius'] = self::extract4( $border_radius );
		}

		if ( $border_style !== 'none' ) {
			$initial['borderStyle'] = $border_style;
			$initial['borderColor'] = $border_color;
			$initial['borderWidth'] = self::extract4( $border_width );
		}

		return $initial;
	}

	/**
	 * Font settings filled in with the defaults, or a global font's, like fillFontStyle() in the editor
	 *
	 * @param array $style
	 *
	 * @return array
	 */
	public function fill_font_style( array $style ): array {

		$use = $style['use'] ?? 'custom';

		if ( is_string( $use ) ) {
			foreach ( $this->site()['fonts'] as $font ) {
				if ( ( $font['id'] ?? null ) === $use && ! empty( $font['style'] ) ) {
					return $this->fill_font_style( array_merge( (array) $font['style'], [ 'color' => $style['color'] ?? '' ] ) );
				}
			}
		}

		$color       = array_key_exists( 'color', $style ) ? $style['color'] : '';
		$font_family = array_key_exists( 'fontFamily', $style ) ? $style['fontFamily'] : '';
		$font_size   = array_key_exists( 'fontSize', $style ) ? $style['fontSize'] : 16;

		unset( $style['use'], $style['color'], $style['fontFamily'], $style['fontSize'] );

		$filled = self::font_defaults( $style );

		$filled['color']      = $color;
		$filled['fontSize']   = self::js_string( $font_size ) . 'px';
		$filled['fontFamily'] = Email_Block_Style::normalize_font( (string) $font_family );

		return $filled;
	}

	/**
	 * A value as JavaScript would put it in a template string
	 *
	 * @param mixed $value
	 *
	 * @return string
	 */
	protected static function js_string( $value ): string {

		if ( is_float( $value ) && is_nan( $value ) ) {
			return 'NaN';
		}

		if ( is_int( $value ) || is_float( $value ) ) {
			return Email_Block_Style::number( $value );
		}

		if ( $value === null ) {
			return 'undefined';
		}

		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}

		return is_scalar( $value ) ? (string) $value : '';
	}

	/**
	 * An element, like makeEl() in the editor. Attributes are written in order, except the style attribute, which
	 * the browser adds last. false skips an attribute.
	 *
	 * @param string $tag
	 * @param array  $attributes
	 * @param string $children HTML
	 *
	 * @return string
	 */
	public static function el( string $tag, array $attributes, string $children = '' ): string {

		$html  = '<' . $tag;
		$style = null;

		foreach ( $attributes as $name => $value ) {

			if ( $value === false ) {
				continue;
			}

			if ( $name === 'style' ) {
				$style = is_string( $value ) ? new Email_Block_Style( $value ) : $value;
				continue;
			}

			$html .= ' ' . $name . '="' . self::esc_attr( self::js_string( $value ) ) . '"';
		}

		if ( $style instanceof Email_Block_Style && ! $style->is_empty() ) {
			$html .= ' style="' . self::esc_attr( (string) $style ) . '"';
		}

		$html .= '>';

		if ( in_array( $tag, [ 'img', 'br', 'hr', 'input', 'meta', 'link', 'col', 'area', 'base', 'wbr', 'source' ], true ) ) {
			return $html;
		}

		return $html . $children . '</' . $tag . '>';
	}

	/**
	 * Escape an attribute value like the browser does when it writes HTML
	 *
	 * @param string $value
	 *
	 * @return string
	 */
	public static function esc_attr( string $value ): string {
		return str_replace( [ '&', '"', "\u{00A0}" ], [ '&amp;', '&quot;', '&nbsp;' ], $value );
	}

	/**
	 * Escape text like the browser does when it writes HTML
	 *
	 * @param string $text
	 *
	 * @return string
	 */
	public static function esc_text( string $text ): string {
		return str_replace( [ '&', '<', '>', "\u{00A0}" ], [ '&amp;', '&lt;', '&gt;', '&nbsp;' ], $text );
	}

	/*
	 * Block types
	 */

	/**
	 * The text block's HTML: its content with each tag styled, like textContent() in the editor
	 *
	 * @param array $props
	 *
	 * @return string
	 */
	public function text_html( array $props ): string {

		$p = (array) $props['p'];

		return $this->style_html( (string) $props['content'], [
			'p'         => $this->fill_font_style( array_merge( $p, [ 'margin' => '1em 0' ] ) ),
			'li'        => $this->fill_font_style( $p ),
			'h1'        => $this->fill_font_style( (array) $props['h1'] ),
			'h2'        => $this->fill_font_style( (array) $props['h2'] ),
			'h3'        => $this->fill_font_style( (array) $props['h3'] ),
			'a'         => (array) $props['a'],
			'b, strong' => [ 'fontWeight' => 'bold' ],
			'ul'        => [ 'listStyle' => 'disc', 'paddingLeft' => '30px' ],
			'ol'        => [ 'paddingLeft' => '30px' ],
		], true );
	}

	/**
	 * Read a text block's settings from its HTML
	 *
	 * @param string $inner
	 *
	 * @return array
	 */
	public function text_parse( string $inner ): array {

		$defaults = $this->defaults( 'text' );

		$wrap = self::find_element( $inner, [ 'div' ], 'text-content-wrap' );

		return [
			'p'       => $this->font_style_from_tag( $inner, [ 'p', 'li' ], $defaults['p'] ),
			'a'       => $this->font_style_from_tag( $inner, [ 'a' ], $defaults['a'] ),
			'h1'      => $this->font_style_from_tag( $inner, [ 'h1' ], $defaults['h1'] ),
			'h2'      => $this->font_style_from_tag( $inner, [ 'h2' ], $defaults['h2'] ),
			'h3'      => $this->font_style_from_tag( $inner, [ 'h3' ], $defaults['h3'] ),
			'content' => $wrap ? $wrap['inner'] : $inner,
		];
	}

	/**
	 * The text block's CSS, for the tags it has
	 *
	 * @param array  $props
	 * @param string $selector
	 *
	 * @return string
	 */
	public function text_css( array $props, string $selector ): string {

		$content          = (string) ( $props['content'] ?? '' );
		$has_replacements = (bool) preg_match( '/\{[a-z_][^{}]*\}/i', $content );
		$rules            = [];

		foreach ( [ 'h1', 'h2', 'h3', 'p', 'a' ] as $tag ) {

			if ( ! $has_replacements && ! preg_match( '/<' . $tag . ' [^>]*>/', $content ) ) {
				continue;
			}

			$style = (array) ( $props[ $tag ] ?? [] );

			if ( $tag === 'p' ) {
				$rules[] = "$selector p, $selector li{" . Email_Block_Style::object_to_style( $this->fill_font_style( $style ) ) . '}';
			} else if ( $tag === 'a' ) {
				$rules[] = "$selector a{ " . Email_Block_Style::object_to_style( $style ) . ' }';
			} else {
				$rules[] = "$selector $tag{" . Email_Block_Style::object_to_style( $this->fill_font_style( $style ) ) . '}';
			}
		}

		return implode( ' ', $rules );
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function heading_html( array $props ): string {

		$font = (array) $props['fontStyle'];

		$style = ( new Email_Block_Style() )->assign( self::css_props( array_merge( $font, [
			'fontSize'  => self::js_string( $font['fontSize'] ?? null ) . 'px',
			'margin'    => 0,
			'textAlign' => $props['alignment'],
		] ) ) );

		return self::el( $props['tag'], [ 'style' => $style ], self::normalize_html( (string) $props['content'] ) );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function heading_parse( string $inner ): array {

		$first = self::find_element( $inner, null );

		if ( ! $first ) {
			return [];
		}

		$style = new Email_Block_Style( (string) ( $first['attributes']['style'] ?? '' ) );

		return [
			'tag'       => $first['tag'],
			'fontStyle' => self::parse_font_style( $style ),
			'alignment' => $style->get( 'text-align' ),
			'content'   => $first['inner'],
		];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function button_html( array $props ): string {

		$sizes   = [ 'sm' => '8px 12px', 'md' => '12px 18px', 'lg' => '16px 24px' ];
		$padding = $sizes[ $props['size'] ] ?? null;
		$width   = $props['align'] === 'justify' ? '100%' : 'auto';
		$font    = (array) $props['style'];
		$bg      = $props['backgroundColor'];

		$cell_style = array_merge( [
			'padding'      => $padding,
			'width'        => $width,
			'borderRadius' => '3px',
		], self::add_border_style( (array) ( $props['borderStyle'] ?? [] ) ), [
			'backgroundColor' => $bg,
		] );

		$link = self::el( 'a', [
			'href'  => $props['link'],
			'style' => ( new Email_Block_Style() )->assign( self::css_props( array_merge( $font, [
				'fontSize'       => self::js_string( $font['fontSize'] ?? null ) . 'px',
				'textDecoration' => 'none',
				'display'        => 'inline-block',
				'verticalAlign'  => 'middle',
				'backgroundColor' => $bg,
			] ) ) ),
		], self::normalize_html( (string) $props['text'] ) );

		$cell = self::el( 'td', [
			'class'   => 'email-button',
			'bgcolor' => $bg,
			'width'   => $width,
			'style'   => ( new Email_Block_Style() )->assign( $cell_style ),
			'align'   => 'center',
		], $link );

		return self::el( 'table', [
			'width'       => '100%',
			'border'      => '0',
			'cellspacing' => '0',
			'cellpadding' => '0',
			'role'        => 'presentation',
		], self::el( 'tr', [], self::el( 'td', [ 'align' => $props['align'] ], self::el( 'table', [
			'border'      => '0',
			'cellspacing' => '0',
			'cellpadding' => '0',
			'role'        => 'presentation',
			'width'       => $width,
		], self::el( 'tr', [], $cell ) ) ) ) );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function button_parse( string $inner ): array {

		$link  = self::find_element( $inner, [ 'a' ] );
		$cell  = self::find_element( $inner, [ 'td' ], 'email-button' );
		$align = self::find_element( $inner, [ 'td' ], null, 'align' );

		return [
			'text'            => $link ? self::inner_text( $link['inner'] ) : null,
			'link'            => $link['attributes']['href'] ?? null,
			'align'           => $align['attributes']['align'] ?? null,
			'borderStyle'     => self::parse_border_style( new Email_Block_Style( (string) ( $cell['attributes']['style'] ?? '' ) ) ),
			'backgroundColor' => $cell['attributes']['bgcolor'] ?? null,
			'style'           => self::parse_font_style( new Email_Block_Style( (string) ( $link['attributes']['style'] ?? '' ) ) ),
		];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function menu_html( array $props ): string {

		$font  = (array) $props['fontStyle'];
		$gap   = $props['gap'];
		$cells = '';

		foreach ( array_values( (array) $props['links'] ) as $i => $link ) {

			if ( $i > 0 ) {
				$cells .= self::column_gap( $gap, 10, (string) $props['separator'], $font['color'] ?? null );
			}

			$cells .= self::el( 'td', [
				'style' => ( new Email_Block_Style() )->set( 'text-align', 'center' ),
			], self::el( 'a', [
				'href'  => $link[1] ?? '',
				'style' => ( new Email_Block_Style() )->assign( self::css_props( array_merge( $font, [
					'fontSize'       => self::js_string( $font['fontSize'] ?? null ) . 'px',
					'textDecoration' => 'none',
				] ) ) ),
			], self::normalize_html( (string) ( $link[0] ?? '' ) ) ) );
		}

		$collapse = ( new Email_Block_Style() )->set( 'border-collapse', 'collapse' );

		return self::el( 'table', [
			'width'       => '100%',
			'border'      => '0',
			'cellspacing' => '0',
			'cellpadding' => '0',
			'role'        => 'presentation',
			'style'       => $collapse,
		], self::el( 'tr', [], self::el( 'td', [ 'align' => $props['align'] ], self::el( 'table', [
			'border'      => '0',
			'cellspacing' => '0',
			'cellpadding' => '0',
			'role'        => 'presentation',
			'style'       => clone $collapse,
			'width'       => $props['align'] === 'justify' ? '100%' : 'auto',
		], self::el( 'tr', [], $cells ) ) ) ) );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function menu_parse( string $inner ): array {

		$links = [];

		foreach ( self::find_elements( $inner, [ 'a' ] ) as $link ) {
			$links[] = [ $link['inner'], $link['attributes']['href'] ?? null ];
		}

		$first = self::find_element( $inner, [ 'a' ] );
		$gap   = self::find_element( $inner, [ 'td' ], 'gap' );
		$align = self::find_element( $inner, [ 'td' ], null, 'align' );

		return [
			'links'     => $links,
			'fontStyle' => self::parse_font_style( new Email_Block_Style( (string) ( $first['attributes']['style'] ?? '' ) ) ),
			'gap'       => self::parse_int( $gap['attributes']['width'] ?? null ),
			'align'     => $align['attributes']['align'] ?? null,
		];
	}

	/**
	 * A gap cell between columns or menu links, like ColumnGap() in the editor
	 *
	 * @param mixed       $gap
	 * @param int         $height
	 * @param string      $content
	 * @param string|null $color
	 *
	 * @return string
	 */
	protected static function column_gap( $gap, int $height = 0, string $content = '', $color = null ): string {

		$gap = is_numeric( $gap ) ? $gap + 0 : $gap;

		if ( ! is_int( $gap ) && ! is_float( $gap ) || is_nan( $gap ) || $gap <= 0 ) {
			return '';
		}

		$style = ( new Email_Block_Style() )->assign( [
			'width'     => self::js_string( $gap ) . 'px',
			'height'    => ( $height ?: self::js_string( $gap ) ) . 'px',
			'textAlign' => 'center',
		] );

		if ( $color !== null ) {
			$style->set( 'color', $color );
		}

		return self::el( 'td', [
			'class'  => 'email-columns-cell gap',
			'height' => $height ?: $gap,
			'width'  => $gap,
			'align'  => 'center',
			'style'  => $style,
		], $content === '' ? str_repeat( '&nbsp;', $gap > 5 ? 3 : 1 ) : self::normalize_html( $content ) );
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function image_html( array $props ): string {

		$img = self::el( 'img', [
			'src'   => $props['src'],
			'alt'   => $props['alt'] ?? '',
			'width' => $props['width'],
			'style' => ( new Email_Block_Style() )->assign( array_merge( [
				'boxSizing'     => 'border-box',
				'verticalAlign' => 'bottom',
				'height'        => 'auto',
				'maxWidth'      => '100%',
				'width'         => $props['width'],
			], self::add_border_style( (array) ( $props['borderStyle'] ?? [] ) ) ) ),
		] );

		if ( ! empty( $props['link'] ) ) {
			$img = self::el( 'a', [ 'href' => $props['link'] ], $img );
		}

		return self::el( 'div', [
			'class' => 'img-container',
			'style' => ( new Email_Block_Style() )->set( 'text-align', $props['align'] ?? 'center' ),
		], $img );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function image_parse( string $inner ): array {

		$img       = self::find_element( $inner, [ 'img' ] );
		$link      = self::find_element( $inner, [ 'a' ] );
		$container = self::find_element( $inner, null, 'img-container' );

		$attributes = $img['attributes'] ?? [];

		// img.width and img.height are their attributes when the image isn't shown, or 0
		$width  = self::parse_int( $attributes['width'] ?? 0 );
		$height = self::parse_int( $attributes['height'] ?? 0 );

		return [
			'src'         => $attributes['src'] ?? null,
			'height'      => is_nan( $height ) ? 0 : $height,
			'width'       => is_nan( $width ) ? 0 : $width,
			'alt'         => $attributes['alt'] ?? '',
			'link'        => $link ? ( $link['attributes']['href'] ?? null ) : null,
			'borderStyle' => self::parse_border_style( new Email_Block_Style( (string) ( $attributes['style'] ?? '' ) ) ),
			'align'       => ( new Email_Block_Style( (string) ( $container['attributes']['style'] ?? '' ) ) )->get( 'text-align' ),
		];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function spacer_html( array $props ): string {

		$height = $props['height'] ?? 20;

		return self::el( 'table', [
			'cellspacing' => '0',
			'cellpadding' => '0',
			'role'        => 'presentation',
		], self::el( 'tr', [], self::el( 'td', [
			'height' => $height,
			'style'  => ( new Email_Block_Style() )->set( 'height', self::js_string( $height ) . 'px' ),
		] ) ) );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function spacer_parse( string $inner ): array {
		$cell = self::find_element( $inner, [ 'td' ], null, 'height' );

		return [ 'height' => self::parse_int( $cell['attributes']['height'] ?? null ) ];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function divider_html( array $props ): string {
		return self::el( 'hr', [
			'class' => 'divider',
			'style' => ( new Email_Block_Style() )->assign( [
				'borderWidth' => self::js_string( $props['height'] ) . 'px 0 0 0',
				'width'       => self::js_string( $props['width'] ) . '%',
				'borderColor' => $props['color'],
				'borderStyle' => $props['lineStyle'] ?? 'solid',
			] ),
		] );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function divider_parse( string $inner ): array {

		$hr    = self::find_element( $inner, [ 'hr' ] );
		$style = new Email_Block_Style( (string) ( $hr['attributes']['style'] ?? '' ) );

		return [
			'height'    => self::parse_int( $style->get( 'border-top-width' ) ),
			'width'     => self::parse_int( $style->get( 'width' ) ),
			'color'     => $style->get( 'border-top-color' ),
			'lineStyle' => $style->get( 'border-style' ),
		];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function html_html( array $props ): string {
		return self::normalize_html( (string) $props['content'], self::UNSUPPORTED_TAGS );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function html_parse( string $inner ): array {
		return [ 'content' => $inner ];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function social_html( array $props ): string {

		$socials = ( $props['use'] ?? 'global' ) === 'global' ? $this->site()['socials'] : (array) $props['socials'];

		if ( empty( $socials ) ) {
			return '';
		}

		$theme = $props['theme'] ?? 'brand-circle';
		$size  = $props['size'] ?? 24;
		$gap   = $props['gap'] ?? 10;
		$cells = '';

		foreach ( array_values( $socials ) as $i => $social ) {

			$social = array_values( (array) $social );
			$icon   = (string) ( $social[0] ?? '' );

			if ( $i > 0 ) {
				$cells .= self::el( 'td', [
					'class' => 'gap',
					'width' => $gap,
					'style' => ( new Email_Block_Style() )->set( 'width', self::js_string( $gap ) . 'px' ),
				] );
			}

			$cells .= self::el( 'td', [], self::el( 'a', [ 'href' => $social[1] ?? '' ], self::el( 'img', [
				'src'    => GROUNDHOGG_ASSETS_URL . 'images//social-icons/' . $theme . '/' . ( $icon ?: 'facebook' ) . '.png',
				'alt'    => self::SOCIALS[ $icon ] ?? null,
				'height' => $size,
				'width'  => $size,
				'style'  => ( new Email_Block_Style() )->set( 'vertical-align', 'bottom' ),
			] ) ) );
		}

		return self::el( 'table', [
			'class'       => 'socials',
			'cellpadding' => 0,
			'cellspacing' => 0,
			'role'        => 'presentation',
			'width'       => '100%',
			'style'       => ( new Email_Block_Style() )->set( 'width', '100%' ),
		], self::el( 'tr', [], self::el( 'td', [ 'align' => $props['align'] ?? 'center' ], self::el( 'table', [
			'class'       => 'socials',
			'cellpadding' => 0,
			'cellspacing' => 0,
			'role'        => 'presentation',
		], self::el( 'tr', [], $cells ) ) ) ) );
	}

	/**
	 * @param string $inner
	 *
	 * @return array
	 */
	public function social_parse( string $inner ): array {

		$img = self::find_element( $inner, [ 'img' ] );
		$gap = self::find_element( $inner, [ 'td' ], 'gap' );

		$socials = [];

		foreach ( self::find_elements( $inner, [ 'a' ] ) as $link ) {
			$icon      = self::find_element( $link['inner'], [ 'img' ] );
			$png       = basename( (string) wp_parse_url( (string) ( $icon['attributes']['src'] ?? '' ), PHP_URL_PATH ) );
			$socials[] = [ substr( $png, 0, (int) strpos( $png, '.png' ) ), (string) ( $link['attributes']['href'] ?? '' ) ];
		}

		$theme = null;

		if ( $img ) {
			$parts = explode( '/', (string) ( $img['attributes']['src'] ?? '' ) );
			$theme = $parts[ count( $parts ) - 2 ] ?? null;
		}

		return [
			'size'    => self::parse_int( $img['attributes']['width'] ?? null ),
			'gap'     => self::parse_int( $gap['attributes']['width'] ?? null ),
			'theme'   => $theme,
			'socials' => $socials,
		];
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function footer_html( array $props ): string {

		$footer = $this->site()['footer'];
		$style  = (array) $props['style'];

		$line_style = array_merge( $this->fill_font_style( $style ), [
			'textAlign' => $props['alignment'] ?? 'left',
			'margin'    => '0.5em 0',
		] );

		$links = [];

		if ( $footer['links']['tel'] && ! empty( $props['tel'] ) ) {
			$links[] = $footer['links']['tel'];
		}

		if ( $footer['links']['privacy'] && ! empty( $props['privacyPolicy'] ) ) {
			$links[] = $footer['links']['privacy'];
		}

		if ( $footer['links']['terms'] && ! empty( $props['terms'] ) ) {
			$links[] = $footer['links']['terms'];
		}

		$lines = [ '&copy; ' . $footer['business_name'], $footer['address'], implode( ' | ', $links ) ];

		if ( $this->context['message_type'] !== 'transactional' ) {
			$lines[] = $footer['unsubscribe'];
		}

		$html = '';

		foreach ( $lines as $line ) {
			$html .= self::el( 'p', [ 'style' => ( new Email_Block_Style() )->assign( self::css_props( $line_style ) ) ], self::normalize_html( (string) $line ) );
		}

		$html = self::el( 'div', [ 'id' => 'footer', 'class' => 'footer' ], $html );

		return $this->style_html( $html, [
			'a' => $this->fill_font_style( array_merge( $style, (array) $props['linkStyle'] ) ),
		] );
	}

	/**
	 * @param array $props
	 *
	 * @return string
	 */
	public function columns_html( array $props ): string {

		$layout         = isset( self::LAYOUTS[ $props['layout'] ] ) ? $props['layout'] : 'two_columns';
		$cells          = self::LAYOUTS[ $layout ];
		$table_layout   = 'fixed';
		$columns        = array_values( (array) $props['columns'] );
		$gap            = $props['gap'] ?? 0;
		$vertical_align = $props['verticalAlign'] ?? 'top';
		$column_style   = (array) ( $props['columnStyle'] ?? [] );

		if ( $layout === 'custom_columns' ) {

			$custom = array_values( (array) $props['customColumns'] );

			if ( ! array_filter( $custom, [ __CLASS__, 'truthy' ] ) ) {
				$table_layout = 'auto';
			}

			$cells = array_map( function ( $width ) {
				return self::truthy( $width ) ? [ 'custom-width-fixed', self::js_string( $width ) . '%' ] : [ 'custom-width-auto', null ];
			}, $custom );
		}

		$html = '';

		foreach ( $cells as $i => $cell ) {

			if ( $i > 0 ) {
				$html .= self::column_gap( $gap );
			}

			$blocks = (array) ( $columns[ $i ] ?? [] );
			$style  = [];

			if ( $cell[1] ) {
				$style['width'] = $cell[1];
			}

			$style['verticalAlign'] = $vertical_align;
			$bgcolor                = '';

			// only columns with blocks get the column style
			if ( ! empty( $blocks ) ) {

				if ( ! empty( $column_style['backgroundColor'] ) ) {
					$style['backgroundColor'] = $column_style['backgroundColor'];
					$bgcolor                  = $column_style['backgroundColor'];
				}

				$style['padding'] = self::extract4( (array) ( $column_style['padding'] ?? [] ) );
				$style            = self::add_border_style( $column_style, $style );
			}

			$rows = '';

			foreach ( $blocks as $block ) {
				$rows .= $this->render_block( $block, false )[0];
			}

			$html .= self::el( 'td', [
				'class'   => 'email-columns-cell ' . $cell[0],
				'bgcolor' => $bgcolor,
				'style'   => ( new Email_Block_Style() )->assign( $style ),
				'width'   => $cell[1] ?? false,
			], '<!--START:COLUMN-->' . self::el( 'table', [
					'class'       => trim( 'column ' . ( $blocks ? '' : 'empty' ) ),
					'cellpadding' => '0',
					'cellspacing' => '0',
					'role'        => 'presentation',
					'width'       => '100%',
				], $rows ) . '<!--END:COLUMN-->' );
		}

		return self::el( 'table', [
			'class'       => trim( "email-columns $layout " . ( self::truthy( $props['responsive'] ?? true ) ? 'responsive' : '' ) ),
			'cellspacing' => '0',
			'cellpadding' => '0',
			'width'       => '100%',
			'role'        => 'presentation',
			'style'       => ( new Email_Block_Style() )->assign( [
				'tableLayout'   => $table_layout,
				'borderSpacing' => 0,
				'width'         => '100%',
			] ),
		], self::el( 'tr', [ 'class' => 'email-columns-row' ], $html ) );
	}

	/**
	 * Read a columns block's settings, and the blocks in its columns, from its HTML
	 *
	 * @param string $inner
	 *
	 * @return array
	 */
	public function columns_parse( string $inner ): array {

		$table  = self::find_element( $inner, [ 'table' ], 'email-columns' );
		$layout = null;

		if ( $table ) {
			$classes = preg_split( '/\s+/', trim( (string) ( $table['attributes']['class'] ?? '' ) ) );
			$layout  = $classes[1] ?? null;
		}

		$cells = $table ? self::row_cells( $table['inner'] ) : [];

		$first_cell   = null;
		$columns      = [];
		$custom       = [];
		$column_style = [];

		foreach ( $cells as $cell ) {

			$classes = preg_split( '/\s+/', (string) ( $cell['attributes']['class'] ?? '' ) );

			if ( in_array( 'gap', $classes, true ) ) {
				continue;
			}

			$column = self::find_element( $cell['inner'], [ 'table' ], 'column' );

			if ( $column && ! $first_cell ) {
				$first_cell = $cell;
			}

			$columns[] = $column ? $this->read_rows( $column['inner'] ) : [];
			$custom[]  = self::parse_int( $cell['attributes']['width'] ?? null );
		}

		while ( count( $columns ) < 4 ) {
			$columns[] = [];
		}

		$vertical_align = '';

		if ( $first_cell ) {

			$style          = new Email_Block_Style( (string) ( $first_cell['attributes']['style'] ?? '' ) );
			$vertical_align = $style->get( 'vertical-align' );

			$column_style = [ 'padding' => self::parse4( $style, 'padding-%s' ) ];

			if ( ! empty( $first_cell['attributes']['bgcolor'] ) ) {
				$column_style['backgroundColor'] = $first_cell['attributes']['bgcolor'];
			}

			$column_style = array_merge( $column_style, self::parse_border_style( $style ) );
		}

		return [
			'layout'        => $layout,
			'verticalAlign' => $vertical_align,
			'responsive'    => $table && in_array( 'responsive', preg_split( '/\s+/', (string) ( $table['attributes']['class'] ?? '' ) ), true ),
			'columnStyle'   => $column_style,
			'columns'       => $columns,
			'customColumns' => self::adjust_to_100( $custom ),
		];
	}

	/**
	 * The CSS of the blocks in the columns
	 *
	 * @param array $props
	 *
	 * @return string
	 */
	public function columns_css( array $props ): string {

		$css = '';

		foreach ( (array) $props['columns'] as $column ) {
			if ( ! empty( $column ) ) {
				$css .= $this->blocks_css( $column );
			}
		}

		return $css;
	}

	/**
	 * Column widths that add up to 100 at most, like adjustArrayToSum100() in the editor
	 *
	 * @param array $widths
	 *
	 * @return array
	 */
	protected static function adjust_to_100( array $widths ): array {

		$sum = 0;

		foreach ( $widths as $width ) {
			$sum = is_nan( $sum ) || is_nan( (float) $width ) ? NAN : $sum + $width;
		}

		if ( is_nan( $sum ) || $sum <= 100 ) {
			return $widths;
		}

		$factor   = ( $sum - 100 ) / $sum;
		$adjusted = array_map( function ( $width ) use ( $factor ) {
			return $width - $width * $factor;
		}, $widths );

		$last = count( $adjusted ) - 1;

		foreach ( $adjusted as $i => $width ) {
			if ( $i !== $last ) {
				$adjusted[ $i ] = (int) floor( $width );
			}
		}

		$adjusted[ $last ] = 100 - array_sum( array_slice( $adjusted, 0, $last ) );

		return $adjusted;
	}

	/*
	 * Reading blocks
	 */

	/**
	 * Read a block from its row, like parseBlockFromRow() in the editor: the props in its comment, the attributes
	 * read from its HTML, its advanced style and visibility
	 *
	 * @param string $markup
	 *
	 * @return array|null
	 */
	public function read_block( string $markup ) {

		$doc = new Email_Block_Document( $markup, true );
		$ids = $doc->top_level();

		if ( empty( $ids ) ) {
			return null;
		}

		return $this->read_from( $doc, $ids[0] );
	}

	/**
	 * The blocks in rows of a table, like parseBlocksFromTable() in the editor
	 *
	 * @param string $html
	 *
	 * @return array[]
	 */
	public function read_rows( string $html ): array {

		$doc    = new Email_Block_Document( $html, true );
		$blocks = [];

		foreach ( $doc->top_level() as $id ) {
			$blocks[] = $this->read_from( $doc, $id );
		}

		return $blocks;
	}

	/**
	 * Read every block in an email's content
	 *
	 * @param string $content
	 *
	 * @return array[]
	 */
	public function read( string $content ): array {
		return $this->read_rows( $content );
	}

	/**
	 * @param Email_Block_Document $doc
	 * @param string               $id
	 *
	 * @return array
	 */
	protected function read_from( Email_Block_Document $doc, string $id ): array {

		$found = $doc->get( $id );
		$type  = $found['type'];
		$block = array_merge( [ 'type' => $type, 'id' => $id ], (array) $found['props'] );

		$markup = $doc->markup( $id );
		$td     = preg_match( '/^<!--.*?-->\s*(<td\b[^>]*>)/s', $markup, $m ) ? $m[1] : '';
		$td     = self::tag_attributes( $td );
		$inner  = $doc->inner_html( $id );

		// the template's wrapper around top level blocks, see block_markup()
		$wrapper = self::find_element( $inner, [ 'td' ], null, null, 'b-' . $id . '-inner' );
		$width   = $wrapper ? self::parse_int( $wrapper['attributes']['width'] ?? null ) : null;

		if ( $wrapper && preg_match( '/^\s*<table\b/i', $inner ) ) {
			$inner = $wrapper['inner'];
		}

		$definition = $this->types()[ $type ] ?? null;

		if ( $definition && is_callable( $definition['parse'] ?? null ) ) {

			$block = array_merge( $block, (array) call_user_func( $definition['parse'], $inner, $block, $this ) );

			// the editor uses the default for an attribute it can't read
			foreach ( $definition['attributes'] ?? [] as $attribute ) {
				if ( ! array_key_exists( $attribute, $block ) ) {
					$block[ $attribute ] = $this->defaults( $type )[ $attribute ] ?? null;
				}
			}
		}

		$style    = new Email_Block_Style( (string) ( $td['style'] ?? '' ) );
		$advanced = [];

		if ( $width && ! is_nan( $width ) ) {
			$advanced['width'] = $width;
		}

		$advanced['padding'] = self::parse4( $style, 'padding-%s' );

		if ( ! empty( $td['bgcolor'] ) ) {
			$advanced['backgroundColor'] = $td['bgcolor'];
		}

		if ( ! empty( $td['background'] ) ) {
			$advanced['backgroundImage'] = $td['background'];
		}

		$block['advancedStyle'] = array_merge( $advanced, self::parse_border_style( $style ) );

		$classes = preg_split( '/\s+/', (string) ( $td['class'] ?? '' ) );

		foreach ( self::VISIBILITY as $flag => $class ) {
			$block[ $flag ] = in_array( $class, $classes, true );
		}

		return $block;
	}

	/**
	 * Font settings from a style, like parseFontStyle() in the editor
	 *
	 * @param Email_Block_Style $style
	 *
	 * @return array
	 */
	protected static function parse_font_style( Email_Block_Style $style ): array {
		return [
			'color'         => $style->get( 'color' ),
			'fontFamily'    => Email_Block_Style::normalize_font( $style->get( 'font-family' ) ),
			'lineHeight'    => $style->get( 'line-height' ),
			'fontWeight'    => $style->get( 'font-weight' ),
			'fontStyle'     => $style->get( 'font-style' ),
			'fontSize'      => self::parse_int( $style->get( 'font-size' ) ),
			'textTransform' => $style->get( 'text-transform' ),
		];
	}

	/**
	 * Font settings from the first of some tags, or the defaults, like parseFontStyleFromTag() in the editor
	 *
	 * @param string   $html
	 * @param string[] $tags
	 * @param array    $default
	 *
	 * @return array
	 */
	protected function font_style_from_tag( string $html, array $tags, array $default ): array {

		$found = self::find_element( $html, $tags );

		if ( ! $found ) {
			return $default;
		}

		return self::parse_font_style( new Email_Block_Style( (string) ( $found['attributes']['style'] ?? '' ) ) );
	}

	/**
	 * Border settings from a style, like parseBorderStyle() in the editor
	 *
	 * @param Email_Block_Style $style
	 *
	 * @return array
	 */
	protected static function parse_border_style( Email_Block_Style $style ): array {

		$radius = [
			'top'    => self::parse_int( $style->get( 'border-top-left-radius' ) ),
			'right'  => self::parse_int( $style->get( 'border-top-right-radius' ) ),
			'bottom' => self::parse_int( $style->get( 'border-bottom-right-radius' ) ),
			'left'   => self::parse_int( $style->get( 'border-bottom-left-radius' ) ),
		];

		$radius['linked'] = self::all_same( $radius );

		return [
			'borderStyle'  => $style->get( 'border-style' ),
			'borderColor'  => $style->get( 'border-color' ),
			'borderWidth'  => self::parse4( $style, 'border-%s-width' ),
			'borderRadius' => $radius,
		];
	}

	/**
	 * Top, right, bottom and left from a style, like parse4FromStyle() in the editor
	 *
	 * @param Email_Block_Style $style
	 * @param string            $format like padding-%s
	 *
	 * @return array
	 */
	protected static function parse4( Email_Block_Style $style, string $format ): array {

		$sides = [];

		foreach ( [ 'top', 'right', 'bottom', 'left' ] as $side ) {
			$sides[ $side ] = self::parse_int( $style->get( sprintf( $format, $side ) ) );
		}

		$sides['linked'] = self::all_same( $sides );

		return $sides;
	}

	/**
	 * Whether the sides are all the same number, NaN never being the same
	 *
	 * @param array $sides
	 *
	 * @return bool
	 */
	protected static function all_same( array $sides ): bool {

		foreach ( $sides as $side ) {
			if ( is_float( $side ) && is_nan( $side ) || $side !== $sides['top'] ) {
				return false;
			}
		}

		return true;
	}

	/*
	 * Block CSS
	 */

	/**
	 * The CSS of blocks, like renderBlocksCSS() in the editor
	 *
	 * @param array[] $blocks
	 *
	 * @return string
	 */
	public function blocks_css( array $blocks ): string {

		$css = [];

		foreach ( $blocks as $block ) {
			$css[] = $this->block_css( $block );
		}

		$css = preg_replace( '/(\s*\n|\s*\r\n|\s*\r)+/', "\n", implode( "\n", $css ) );

		return self::optimize_css( $css );
	}

	/**
	 * A block's CSS, like BlockRegistry.css() in the editor: its type's CSS and its custom CSS
	 *
	 * @param array $block
	 *
	 * @return string
	 */
	protected function block_css( array $block ): string {

		$css      = [];
		$selector = '#b-' . $block['id'];
		$type     = $this->types()[ $block['type'] ] ?? null;

		if ( $type && is_callable( $type['css'] ?? null ) ) {
			$css[] = (string) call_user_func( $type['css'], array_merge( $this->defaults( $block['type'] ), $block ), $selector, $this );
		}

		if ( ! empty( $block['css'] ) && is_string( $block['css'] ) ) {
			$css[] = str_replace( 'selector', $selector, $block['css'] );
		}

		return implode( "\n", array_filter( $css, 'strlen' ) );
	}

	/**
	 * Merge rules with the same declarations, like optimizeCSS() in the editor
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public static function optimize_css( string $css ): string {

		$rules = [];
		$css   = preg_replace( '/\/\*[\s\S]*?\*\//', '', $css );

		foreach ( explode( '}', $css ) as $rule ) {

			$parts = explode( '{', $rule );

			if ( count( $parts ) < 2 || $parts[0] === '' || $parts[1] === '' ) {
				continue;
			}

			$declarations = preg_replace( '/;$/', '', preg_replace( '/\s+/', ' ', trim( $parts[1] ) ) );

			if ( $declarations === '' ) {
				continue;
			}

			$rules[ $declarations ][] = trim( $parts[0] );
		}

		$optimized = '';

		foreach ( $rules as $declarations => $selectors ) {
			$optimized .= implode( ',', $selectors ) . '{' . $declarations . '}';
		}

		return $optimized;
	}

	/*
	 * Specs
	 */

	/**
	 * An email's blocks as specs: what's in each block's comment, and what the editor reads from its HTML
	 *
	 * Compact specs leave out every setting that doesn't change the block, like the ones that are the site's defaults
	 * and the inline styles that the text block adds itself, so they're short. Rendering them gives the same blocks:
	 * each setting is only left out when the block renders the same without it.
	 *
	 * @param string $content
	 * @param bool   $compact
	 *
	 * @return array[]
	 */
	public function to_specs( string $content, bool $compact = true ): array {

		$specs = [];

		foreach ( $this->read( $content ) as $block ) {
			$spec    = $this->spec_of( $block );
			$specs[] = $compact ? $this->compact( $spec, true ) : $spec;
		}

		return $specs;
	}

	/**
	 * Some blocks of an email as specs, see to_specs()
	 *
	 * @param string   $content
	 * @param string[] $ids
	 * @param bool     $compact
	 *
	 * @return array[] by id, for the ids that were found
	 */
	public function specs_of( string $content, array $ids, bool $compact = true ): array {

		$specs = [];

		$walk = function ( array $blocks, bool $top_level ) use ( &$walk, &$specs, $ids, $compact ) {
			foreach ( $blocks as $block ) {

				if ( in_array( $block['id'], $ids, true ) ) {
					$spec                   = $this->spec_of( $block );
					$specs[ $block['id'] ] = $compact ? $this->compact( $spec, $top_level ) : $spec;
				}

				if ( $block['type'] === 'columns' ) {
					foreach ( (array) $block['columns'] as $column ) {
						$walk( $column, false );
					}
				}
			}
		};

		$walk( $this->read( $content ), true );

		return $specs;
	}

	/**
	 * A block read from content as a spec, with every setting
	 *
	 * @param array $block
	 *
	 * @return array
	 */
	protected function spec_of( array $block ): array {

		$spec = [];

		foreach ( $block as $key => $value ) {

			if ( $key === 'columns' || ( isset( self::VISIBILITY[ $key ] ) && ! $value ) ) {
				continue;
			}

			$spec[ $key ] = self::clean_value( $value );
		}

		if ( $block['type'] === 'columns' ) {
			$spec['columns'] = array_map( function ( $column ) {
				return array_map( [ $this, 'spec_of' ], $column );
			}, array_slice( (array) $block['columns'], 0, $this->column_count( $block ) ) );
		}

		return $spec;
	}

	/**
	 * A spec without the settings that don't change how it renders
	 *
	 * @param array $spec
	 * @param bool  $top_level
	 *
	 * @return array
	 */
	protected function compact( array $spec, bool $top_level ): array {

		$target = $this->markup_of( $spec, $top_level );

		if ( $target === null ) {
			return $spec;
		}

		$same = function ( array $candidate ) use ( $target, $top_level ) {
			return $this->markup_of( $candidate, $top_level ) === $target;
		};

		if ( $spec['type'] === 'columns' ) {
			foreach ( $spec['columns'] as $c => $column ) {
				foreach ( $column as $i => $child ) {
					$spec['columns'][ $c ][ $i ] = $this->compact( $child, false );
				}
			}
		}

		// the inline styles the text block adds to its tags itself
		if ( $spec['type'] === 'text' && is_string( $spec['content'] ?? null ) ) {

			$font   = [ 'line-height', 'font-family', 'font-weight', 'font-size', 'font-style', 'text-transform', 'color' ];
			$margin = [ 'margin', 'margin-top', 'margin-bottom' ];

			$candidate            = $spec;
			$candidate['content'] = self::without_styles( $spec['content'], [
				'p'      => array_merge( $font, $margin ),
				'li'     => array_merge( $font, $margin ),
				'h1'     => array_merge( $font, $margin ),
				'h2'     => array_merge( $font, $margin ),
				'h3'     => array_merge( $font, $margin ),
				'a'      => $font,
				'b'      => [ 'font-weight' ],
				'strong' => [ 'font-weight' ],
				'ul'     => array_merge( [ 'list-style', 'padding-left' ], $margin ),
				'ol'     => array_merge( [ 'padding-left' ], $margin ),
			] );

			if ( $same( $candidate ) ) {
				$spec = $candidate;
			}
		}

		foreach ( array_keys( $spec ) as $key ) {

			if ( in_array( $key, [ 'type', 'id', 'columns' ], true ) ) {
				continue;
			}

			$candidate = $spec;
			unset( $candidate[ $key ] );

			if ( $same( $candidate ) ) {
				$spec = $candidate;
				continue;
			}

			if ( ! is_array( $spec[ $key ] ) || wp_is_numeric_array( $spec[ $key ] ) ) {
				continue;
			}

			// the settings of an object, like a font style
			foreach ( array_keys( $spec[ $key ] ) as $sub ) {

				if ( count( $spec[ $key ] ) === 1 ) {
					break;
				}

				$candidate = $spec;
				unset( $candidate[ $key ][ $sub ] );

				if ( $same( $candidate ) ) {
					$spec = $candidate;
				}
			}

			// sides that are all the same as one number, like padding
			foreach ( $spec[ $key ] as $sub => $value ) {

				if ( ! is_array( $value ) || ! isset( $value['top'] ) ) {
					continue;
				}

				$candidate                 = $spec;
				$candidate[ $key ][ $sub ] = $value['top'];

				if ( $same( $candidate ) ) {
					$spec = $candidate;
				}
			}
		}

		return $spec;
	}

	/**
	 * The markup of a spec as it's saved, with its own id, or null if it can't be rendered
	 *
	 * @param array $spec
	 * @param bool  $top_level
	 *
	 * @return string|null
	 */
	protected function markup_of( array $spec, bool $top_level ) {

		$renderer           = clone $this;
		$renderer->used_ids = [];

		$rows = $renderer->render_rows( [ $spec ], $top_level );

		return is_wp_error( $rows ) ? null : Create_Email_Template::kses_preserving_block_comments( $rows['html'] );
	}

	/**
	 * HTML without some style properties of some tags, and without style attributes that are left empty
	 *
	 * @param string $html
	 * @param array  $properties the properties to take out, by tag
	 *
	 * @return string
	 */
	protected static function without_styles( string $html, array $properties ): string {

		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag() ) {

			$tag   = strtolower( $processor->get_tag() );
			$value = $processor->get_attribute( 'style' );

			if ( ! isset( $properties[ $tag ] ) || ! is_string( $value ) ) {
				continue;
			}

			$style = new Email_Block_Style( $value );

			foreach ( $properties[ $tag ] as $property ) {
				$style->remove( $property );
			}

			if ( $style->is_empty() ) {
				$processor->remove_attribute( 'style' );
			} else {
				$processor->set_attribute( 'style', rtrim( (string) $style, ';' ) );
			}
		}

		// removing the last attribute leaves a space before the >
		return preg_replace( '/(<[a-z0-9]+(?:\s[^<>]*?)?)\s+>/i', '$1>', $processor->get_updated_html() );
	}

	/**
	 * How many columns a columns block's layout has
	 *
	 * @param array $block
	 *
	 * @return int
	 */
	protected function column_count( array $block ): int {

		if ( ( $block['layout'] ?? '' ) === 'custom_columns' ) {
			return count( (array) ( $block['customColumns'] ?? [] ) );
		}

		return count( self::LAYOUTS[ $block['layout'] ?? 'two_columns' ] ?? self::LAYOUTS['two_columns'] );
	}

	/**
	 * NaN as null, so it can be written as JSON
	 *
	 * @param mixed $value
	 *
	 * @return mixed
	 */
	protected static function clean_value( $value ) {

		if ( is_float( $value ) && is_nan( $value ) ) {
			return null;
		}

		if ( is_array( $value ) ) {
			return array_map( [ __CLASS__, 'clean_value' ], $value );
		}

		return $value;
	}

	/*
	 * HTML helpers
	 */

	/**
	 * Props that are CSS properties, without the editor's own settings like `use`
	 *
	 * @param array $props
	 *
	 * @return array
	 */
	protected static function css_props( array $props ): array {
		return array_diff_key( $props, array_flip( [ 'use', 'linked', 'selector' ] ) );
	}

	/**
	 * The attributes of an opening tag, decoded
	 *
	 * @param string $tag
	 *
	 * @return array
	 */
	public static function tag_attributes( string $tag ): array {

		if ( $tag === '' ) {
			return [];
		}

		$processor = new WP_HTML_Tag_Processor( $tag );

		if ( ! $processor->next_tag() ) {
			return [];
		}

		$attributes = [];

		foreach ( $processor->get_attribute_names_with_prefix( '' ) ?? [] as $name ) {
			$value               = $processor->get_attribute( $name );
			$attributes[ $name ] = $value === true ? '' : (string) $value;
		}

		return $attributes;
	}

	/**
	 * The first element like querySelector(): of one of some tags (any tag when null), with a class, an attribute or
	 * an id
	 *
	 * @param string        $html
	 * @param string[]|null $tags
	 * @param string|null   $class
	 * @param string|null   $attribute
	 * @param string|null   $id
	 *
	 * @return array|null tag, attributes, inner (its HTML), outer
	 */
	public static function find_element( string $html, $tags, $class = null, $attribute = null, $id = null ) {
		$found = self::find_elements( $html, $tags, $class, $attribute, $id, 1 );

		return $found[0] ?? null;
	}

	/**
	 * Every element like querySelectorAll(), see find_element()
	 *
	 * @param string        $html
	 * @param string[]|null $tags
	 * @param string|null   $class
	 * @param string|null   $attribute
	 * @param string|null   $id
	 * @param int           $limit
	 *
	 * @return array[]
	 */
	public static function find_elements( string $html, $tags, $class = null, $attribute = null, $id = null, int $limit = 0 ): array {

		$pattern = $tags ? '(' . implode( '|', array_map( 'preg_quote', $tags ) ) . ')' : '([a-z][a-z0-9]*)';
		$found   = [];
		$offset  = 0;

		// comments aren't elements
		$masked = self::mask_comments( $html );

		while ( preg_match( '/<' . $pattern . '(?=[\s>\/])[^>]*>/i', $masked, $m, PREG_OFFSET_CAPTURE, $offset ) ) {

			$offset     = $m[0][1] + strlen( $m[0][0] );
			$tag        = strtolower( $m[1][0] );
			$attributes = self::tag_attributes( $m[0][0] );

			if ( $class !== null && ! in_array( $class, preg_split( '/\s+/', (string) ( $attributes['class'] ?? '' ) ), true ) ) {
				continue;
			}

			if ( $attribute !== null && ! array_key_exists( $attribute, $attributes ) ) {
				continue;
			}

			if ( $id !== null && ( $attributes['id'] ?? null ) !== $id ) {
				continue;
			}

			$close = self::closing_tag( $masked, $tag, $offset );
			$inner = $close === null ? '' : substr( $html, $offset, $close - $offset );

			$found[] = [
				'tag'        => $tag,
				'attributes' => $attributes,
				'inner'      => $inner,
				'start'      => $m[0][1],
			];

			if ( $limit && count( $found ) >= $limit ) {
				break;
			}
		}

		return $found;
	}

	/**
	 * Where an element's closing tag starts, counting the same tags inside it
	 *
	 * @param string $html
	 * @param string $tag
	 * @param int    $from just after its opening tag
	 *
	 * @return int|null
	 */
	protected static function closing_tag( string $html, string $tag, int $from ) {

		if ( in_array( $tag, [ 'img', 'br', 'hr', 'input', 'meta', 'link', 'col', 'area', 'base', 'wbr', 'source' ], true ) ) {
			return null;
		}

		$depth = 1;

		while ( preg_match( '/<(\/?)' . preg_quote( $tag, '/' ) . '(?=[\s>\/])[^>]*>/i', $html, $m, PREG_OFFSET_CAPTURE, $from ) ) {

			$from = $m[0][1] + strlen( $m[0][0] );

			$depth += $m[1][0] === '/' ? - 1 : 1;

			if ( $depth === 0 ) {
				return $m[0][1];
			}
		}

		// an element that isn't closed, like a <p>, ends where its parent does
		return strlen( $html );
	}

	/**
	 * The cells of a table's first row, not those of tables inside them
	 *
	 * @param string $table_inner
	 *
	 * @return array[]
	 */
	protected static function row_cells( string $table_inner ): array {

		$row = self::find_element( $table_inner, [ 'tr' ] );

		if ( ! $row ) {
			return [];
		}

		$inner  = $row['inner'];
		$masked = self::mask_comments( $inner );
		$cells  = [];
		$offset = 0;

		while ( preg_match( '/<td(?=[\s>\/])[^>]*>/i', $masked, $m, PREG_OFFSET_CAPTURE, $offset ) ) {

			$open_end = $m[0][1] + strlen( $m[0][0] );
			$close    = self::closing_tag( $masked, 'td', $open_end );

			$cells[] = [
				'tag'        => 'td',
				'attributes' => self::tag_attributes( $m[0][0] ),
				'inner'      => substr( $inner, $open_end, $close - $open_end ),
			];

			$offset = $close + 5;
		}

		return $cells;
	}

	/**
	 * HTML with its comments blanked out, so tags in them aren't found, keeping every position the same
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function mask_comments( string $html ): string {
		return preg_replace_callback( '/<!--.*?-->/s', function ( $m ) {
			return str_repeat( ' ', strlen( $m[0] ) );
		}, $html );
	}

	/**
	 * The text in some HTML, like innerText of an element that isn't shown
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected static function inner_text( string $html ): string {
		return html_entity_decode( wp_strip_all_tags( $html, false ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}

	/**
	 * HTML written again the way the browser writes it (innerHTML), optionally without some tags
	 *
	 * @param string   $html
	 * @param string[] $remove tags to take out, with what's in them
	 *
	 * @return string
	 */
	public static function normalize_html( string $html, array $remove = [] ): string {
		return self::serialize( $html, [], $remove );
	}

	/**
	 * Style the elements in some HTML like the editor does with el.style[ prop ] = value, see textContent() and
	 * inlineStyle() in the editor
	 *
	 * @param string $html
	 * @param array  $styles      props by selector, a tag or tags separated by commas
	 * @param bool   $first_last  whether the first element has no top margin and the last no bottom margin
	 *
	 * @return string
	 */
	public function style_html( string $html, array $styles, bool $first_last = false ): string {

		$by_tag = [];

		foreach ( $styles as $selector => $props ) {
			foreach ( array_map( 'trim', explode( ',', $selector ) ) as $tag ) {
				$by_tag[ $tag ] = self::css_props( (array) $props );
			}
		}

		return self::serialize( $html, $by_tag, [], $first_last );
	}

	/**
	 * Parse HTML as the browser does and write it back out like innerHTML, styling elements on the way
	 *
	 * @param string   $html
	 * @param array    $styles     props to set by tag
	 * @param string[] $remove     tags to take out
	 * @param bool     $first_last see style_html()
	 *
	 * @return string
	 */
	protected static function serialize( string $html, array $styles, array $remove = [], bool $first_last = false ): string {

		if ( trim( $html ) === '' ) {
			return $html === '' ? '' : self::esc_text( $html );
		}

		$tokens = self::tokens( $html );

		if ( $tokens === null ) {
			return self::style_with_tag_processor( $html, $styles, $first_last );
		}

		// the first and last elements at the top
		$top   = null;
		$first = null;
		$last  = null;

		foreach ( $tokens as $i => $token ) {
			if ( $token['type'] === 'open' ) {
				if ( $top === null || $token['depth'] < $top ) {
					$top   = $token['depth'];
					$first = $i;
				}
				if ( $token['depth'] === $top ) {
					$last = $i;
				}
			}
		}

		$out      = '';
		$skipping = 0;

		foreach ( $tokens as $i => $token ) {

			// inside a removed element
			if ( $skipping > 0 ) {
				if ( $token['type'] === 'open' && ! $token['void'] && $token['raw'] === null ) {
					$skipping ++;
				} else if ( $token['type'] === 'close' ) {
					$skipping --;
				}
				continue;
			}

			switch ( $token['type'] ) {

				case 'text':
					$out .= self::esc_text( $token['text'] );
					break;

				case 'comment':
					// as email_kses() leaves comments when the editor's content is saved
					$comment = trim( preg_replace( '/-$/', '', preg_replace( '/--+/', '-', trim( $token['text'] ) ) ) );
					$out     .= $comment === '' ? '' : '<!--' . $comment . '-->';
					break;

				case 'close':
					$out .= '</' . $token['tag'] . '>';
					break;

				case 'open':

					if ( in_array( $token['tag'], $remove, true ) ) {
						if ( ! $token['void'] && $token['raw'] === null ) {
							$skipping = 1;
						}
						break;
					}

					$attributes = $token['attributes'];
					$set        = $styles[ $token['tag'] ] ?? null;

					if ( $set !== null || ( $first_last && ( $i === $first || $i === $last ) ) ) {

						$had_style = array_key_exists( 'style', $attributes );
						$style     = new Email_Block_Style( (string) ( $attributes['style'] ?? '' ) );

						if ( $set !== null ) {
							$style->assign( $set );
						}

						if ( $first_last && $i === $first ) {
							$style->set( 'margin-top', 0 );
						}

						if ( $first_last && $i === $last ) {
							$style->set( 'margin-bottom', 0 );
						}

						// a new style attribute is written last
						if ( ! $had_style ) {
							unset( $attributes['style'] );
						}

						$attributes['style'] = (string) $style;
					}

					$out .= '<' . $token['tag'];

					foreach ( $attributes as $name => $value ) {
						$out .= ' ' . $name . '="' . self::esc_attr( $value ) . '"';
					}

					$out .= '>';

					// elements whose text isn't parsed as HTML, like <style>, come with their text and closing tag
					if ( $token['raw'] !== null ) {
						$out .= in_array( $token['tag'], [ 'textarea', 'title' ], true ) ? self::esc_text( $token['raw'] ) : $token['raw'];
						$out .= '</' . $token['tag'] . '>';
					}
					break;
			}
		}

		return $out;
	}

	/**
	 * The tokens of some HTML as the browser would parse it in a <body>, or null when the HTML API can't parse it
	 *
	 * @param string $html
	 *
	 * @return array[]|null
	 */
	protected static function tokens( string $html ) {

		$processor = WP_HTML_Processor::create_fragment( $html );

		if ( ! $processor ) {
			return null;
		}

		$tokens = [];

		while ( $processor->next_token() ) {

			$type = $processor->get_token_type();

			if ( $type === '#text' ) {
				$tokens[] = [ 'type' => 'text', 'text' => $processor->get_modifiable_text() ];
				continue;
			}

			if ( $type === '#comment' || $type === '#funky-comment' ) {
				$tokens[] = [ 'type' => 'comment', 'text' => $processor->get_full_comment_text() ];
				continue;
			}

			if ( $type !== '#tag' ) {
				continue;
			}

			$tag = strtolower( $processor->get_tag() );

			if ( $processor->is_tag_closer() ) {
				$tokens[] = [ 'type' => 'close', 'tag' => $tag ];
				continue;
			}

			$raw = in_array( $tag, [ 'script', 'style', 'textarea', 'title', 'xmp', 'iframe', 'noembed', 'noframes' ], true ) ? $processor->get_modifiable_text() : null;

			$attributes = [];

			foreach ( $processor->get_attribute_names_with_prefix( '' ) ?? [] as $name ) {
				$value               = $processor->get_attribute( $name );
				$attributes[ $name ] = $value === true ? '' : (string) $value;
			}

			$void = WP_HTML_Processor::is_void( $tag );

			$tokens[] = [
				'type'       => 'open',
				'tag'        => $tag,
				'attributes' => $attributes,
				'depth'      => $processor->get_current_depth(),
				'void'       => $void,
				'raw'        => $raw,
			];
		}

		if ( $processor->get_last_error() !== null ) {
			return null;
		}

		return $tokens;
	}

	/**
	 * Style elements without parsing the HTML again, for HTML the HTML API can't parse. Everything else is left as it
	 * is.
	 *
	 * @param string $html
	 * @param array  $styles
	 * @param bool   $first_last
	 *
	 * @return string
	 */
	protected static function style_with_tag_processor( string $html, array $styles, bool $first_last ): string {

		$processor = new WP_HTML_Tag_Processor( $html );

		while ( $processor->next_tag() ) {

			$tag = strtolower( $processor->get_tag() );

			if ( ! isset( $styles[ $tag ] ) ) {
				continue;
			}

			$style = new Email_Block_Style( (string) $processor->get_attribute( 'style' ) );
			$style->assign( $styles[ $tag ] );
			$processor->set_attribute( 'style', (string) $style );
		}

		return $processor->get_updated_html();
	}
}
