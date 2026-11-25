<?php

namespace Groundhogg\Abilities\Emails;

use Groundhogg\Abilities\Ability;
use Groundhogg\Email_Fonts;
use WP_Error;

/**
 * Adds a font the site has already, from its theme or the Font Library, to the email editor's uploaded fonts, the same
 * as the Add button under "Fonts on this site" in the editor. The email guide lists the fonts that can be added.
 */
class Add_Site_Font extends Ability {

	protected const NAME     = 'groundhogg/add-site-font';
	protected const CATEGORY = 'groundhogg-email';

	protected const READONLY    = false;
	protected const DESTRUCTIVE = false;
	protected const IDEMPOTENT  = false;

	protected function get_args(): array {

		return [
			'label'       => __( 'Add Site Font', 'groundhogg' ),
			'description' => __( 'Add a font the site\'s theme or Font Library has to the email fonts, so emails can use it as fontFamily by name. The email guide (get-authoring-guide, topic "email") lists the fonts that can be added. Its files are copied to the uploads folder. Mail apps that show web fonts, like Apple Mail, show it; others, like Gmail and Outlook, show the fallback.', 'groundhogg' ),

			'input_schema' => [
				'type'                 => 'object',
				'additionalProperties' => false,
				'required'             => [ 'font' ],
				'properties'           => [
					'font'     => [
						'type'        => 'string',
						'minLength'   => 1,
						'description' => __( 'The font\'s name, as the email guide lists it, e.g. "Inter".', 'groundhogg' ),
					],
					'fallback' => [
						'type'        => 'string',
						'enum'        => Email_Block_Style::FONTS,
						'description' => __( 'The font shown where the font isn\'t. Pick one like it, a serif for a serif font. Guessed from the font if left out.', 'groundhogg' ),
					],
				],
			],

			'output_schema' => [
				'type'       => 'object',
				'properties' => [
					'name'     => [
						'type'        => 'string',
						'description' => __( 'The name to use as fontFamily.', 'groundhogg' ),
					],
					'fallback' => [ 'type' => 'string' ],
					'files'    => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'weight' => [
									'type'        => 'string',
									'description' => __( '100 to 900, or "variable" for a file with every weight.', 'groundhogg' ),
								],
								'style'  => [ 'type' => 'string' ],
								'url'    => [ 'type' => 'string' ],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * The fonts are saved with the editor's settings, which need manage_options, like the editor's Add button
	 *
	 * @param mixed $input
	 *
	 * @return bool
	 */
	public function can_execute( $input = null ) {
		return current_user_can( 'manage_options' ) && current_user_can( 'edit_emails' );
	}

	public function __invoke( $input ) {

		$name = (string) ( $input['font'] ?? '' );
		$font = Email_Fonts::add_site_font( $name, (string) ( $input['fallback'] ?? '' ) );

		if ( is_wp_error( $font ) ) {

			if ( $font->get_error_code() !== 'font_not_found' ) {
				return $font;
			}

			$available = wp_list_pluck( Email_Fonts::site_fonts(), 'name' );

			return new WP_Error( 'font_not_found', empty( $available )
				? __( 'There\'s no font on this site to add. The site\'s theme and Font Library have none, or they\'ve all been added already.', 'groundhogg' )
				/* translators: %s: font names */
				: sprintf( __( 'There\'s no font with that name to add. These can be added: %s.', 'groundhogg' ), implode( ', ', $available ) )
			);
		}

		return [
			'name'     => $font['name'],
			'fallback' => $font['fallback'],
			'files'    => array_map( function ( $file ) {
				return [
					'weight' => $file['weight'],
					'style'  => $file['style'],
					'url'    => $file['url'],
				];
			}, $font['files'] ),
		];
	}
}
