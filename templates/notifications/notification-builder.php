<?php

namespace Groundhogg\Templates\Notifications;

use Groundhogg\Utils\Replacer;
use function Groundhogg\admin_page_url;
use function Groundhogg\array_map_with_keys;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use function Groundhogg\white_labeled_name;

class Notification_Builder {

	public static function get_template_part( $template = '' ) {
		$file = __DIR__ . "/{$template}.html";
		if ( ! file_exists( $file ) ) {
			return '';
		}

		return file_get_contents( $file );
	}

	/**
	 * Generate the table for the broadcasts performance
	 *
	 * @param array $headers
	 * @param array $theRows
	 *
	 * @return string
	 */
	public static function generate_list_table_html( array $headers, array $theRows ) {

		$rows = [];

		foreach ( $theRows as $i => $row ) {

			$cells = [];
			$k     = 0;

			foreach ( $row as $cellId => $cell ) {

				$classes = [
					'cell-' . $cellId
				];

				$style = [
					'padding' => '8px 8px 8px 12px',
				];

				if ( $k > 0 ) {
					$style['text-align'] = 'center';
				}

				$cells[] = html()->e( 'td', [
					'style' => $style,
					'class' => $classes,
				], $cell );

				$k ++;
			}

			$rows[] = html()->e( 'tr', [
				'style' => [
					'background-color' => $i % 2 === 0 ? '#F6F9FB' : ''
				]
			], $cells );
		}

		if ( empty( $rows ) ) {
			return '';
		}

		return html()->e( 'table', [
			'style' => [
				'border-collapse' => 'collapse',
				'width'           => '100%',
				'table-layout'    => 'auto',
			],
			'width' => '100%'
		], [
			html()->e( 'thead', [], [
				html()->e( 'tr', [], array_map_with_keys( $headers, function ( $header, $i ) {

					$style = [
						'padding' => '8px 8px 8px 12px',
					];

					if ( $i > 0 ) {
						$style['text-align'] = 'center';
					}

					return html()->e( 'th', [
						'style' => $style
					], $header );
				} ) )
			] ),
			html()->e( 'tbody', [], $rows )
		] );

	}


	/**
	 * Get the full template with headers and footers, along with the correct content template
	 *
	 * @param string $content_template
	 *
	 * @return string
	 */
	public static function get_general_notification_template_html( $content_template = '', $replacements = [] ) {

		$replacer = new Replacer( wp_parse_args( $replacements, [
			'the_header'         => self::get_template_part( ! is_white_labeled() ? 'branded-header' : 'generic-header' ),
			'the_content'        => self::get_template_part( $content_template ),
			'the_footer'         => self::get_template_part( ! is_white_labeled() ? 'branded-footer' : 'generic-footer' ),
			'assets_url'         => GROUNDHOGG_ASSETS_URL,
			'site_url'           => home_url(),
			'site_name'          => get_bloginfo(),
			'home_url'           => home_url(),
			'admin_url'          => admin_url(),
			'profile_url'        => admin_url( 'profile.php' ),
			'white_labeled_name' => white_labeled_name(),
			'dashboard_url'      => admin_page_url( 'groundhogg' )
		] ) );

		return $replacer->replace( self::get_template_part( 'general-template' ) );
	}
}
