<?php

namespace Groundhogg;

use Groundhogg\Form\Form;
use Groundhogg\Form\Form_v2;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shortcodes
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Shortcodes {

	public function __construct() {
		$this->register_shortcodes();
	}

	public function register_shortcodes() {
		add_filter( 'no_texturize_shortcodes', [ $this, 'no_texturize_shortcodes' ] );

		add_shortcode( 'gh_email', [ $this, 'email_shortcode' ] );
		add_shortcode( 'gh_form', [ $this, 'custom_form_shortcode' ] );
		add_shortcode( 'gh_replacements', [ $this, 'merge_replacements_shortcode' ] );
		add_shortcode( 'ghr', [ $this, 'merge_replacements_shortcode' ] );
		add_shortcode( 'gh_contact', [ $this, 'contact_replacement_shortcode' ] );
		add_shortcode( 'gh_is_contact', [ $this, 'is_contact_shortcode' ] );
		add_shortcode( 'gh_is_not_contact', [ $this, 'is_not_contact_shortcode' ] );
		add_shortcode( 'gh_is_not_logged_in', [ $this, 'is_not_logged_in' ] );
		add_shortcode( 'gh_is_logged_in', [ $this, 'is_logged_in' ] );
		add_shortcode( 'gh_does_not_have_tags', [ $this, 'contact_does_not_have_tag_shortcode' ] );
		add_shortcode( 'gh_has_tags', [ $this, 'contact_has_tag_shortcode' ] );
	}

	/**
	 * Alternate form shortcode
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 */
	public function custom_form_shortcode( $atts ) {

		$atts = shortcode_atts( [
			'class'   => '',
			'id'      => 0,
			'fill'    => false,
			'contact' => null
		], $atts );

		$step = new Step( $atts['id'] );

		if ( $step->type_is( 'form_fill' ) ) {
			$form = new Form( $atts );
		} else if ( $step->type_is( 'web_form' ) ) {
			$form = new Form_v2( $atts );
		} else {
			return '';
		}

		return $form->shortcode();
	}

	/**
	 * Alternate form shortcode
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 */
	public function email_shortcode( $atts ) {

		$atts = shortcode_atts( [
			'id'     => 0,
			'iframe' => true,
		], $atts );

		$email = new Email( $atts['id'] );

		if ( ! $email->exists() ) {

			if ( current_user_can( 'edit_emails' ) ) {
				return __( 'The requested email does not exist.', 'groundhogg' );
			}

			return '';
		}

		$email->set_contact( get_contactdata() );

		if ( ! defined( 'GROUNDHOGG_IS_BROWSER_VIEW' ) ) {
			define( 'GROUNDHOGG_IS_BROWSER_VIEW', true );
		}

		// output the raw message
		if ( ! filter_var( $atts['iframe'], FILTER_VALIDATE_BOOLEAN ) ) {
			return html()->e( 'div', [
				'class' => 'gh-email-preview-wrapper no-iframe'
			], $email->get_merged_content() );
		}

		ob_start();

		?>
        <div class="gh-email-preview-wrapper has-iframe">
            <iframe style="overflow: hidden" scrolling="no" width="100%" class="gh-email-preview" id="gh-email-preview-<?php echo esc_attr( $atts['id'] ); ?>"></iframe>
            <script>

              let iframe = document.getElementById('gh-email-preview-<?php echo esc_attr( $atts['id'] ); ?>')

              let email = <?php echo wp_json_encode( $email ); ?>;

              let blob = new Blob([email.context.built], { type: 'text/html; charset=utf-8' })

              iframe.onload = () => {
                iframe.style.height = iframe.contentWindow.document.body.offsetHeight + 10 + 'px'
              }

              iframe.src = URL.createObjectURL(blob)
            </script>
        </div>
		<?php


		return ob_get_clean();
	}


	/**
	 * Prevent the shortcode api from texturizing the contents of [gh_form]
	 *
	 * @param $list
	 *
	 * @return array
	 */
	public function no_texturize_shortcodes( $list ) {
		$list[] = 'gh_form';

		return $list;
	}

	/**
	 * Mere contact replacements into page content with this shortcode.
	 *
	 * @param        $atts    array should be empty
	 * @param string $content the content to perform the merge fields
	 *
	 * @return string the updated content,.
	 */
	public function merge_replacements_shortcode( $atts, $content = '' ) {
		return do_replacements( do_shortcode( $content ), get_contactdata() );
	}

	/**
	 * Process the contact shortcode
	 */
	public function contact_replacement_shortcode( $atts ) {
		$a = shortcode_atts( array(
			'field'   => 'first',
			'default' => ''
		), $atts );

		$contact = get_contactdata();
		$default = $a['default'];

		if ( ! empty( $default ) ) {
			$content = sprintf( '{%s::%s}', $a['field'], $default );
		} else {
			$content = sprintf( '{%s}', $a['field'] );
		}

		return do_replacements( $content, $contact );
	}

	/**
	 * Output content if and only if the current visitor is a contact.
	 *
	 * @param        $atts []
	 * @param string $content
	 *
	 * @return string
	 */
	function is_contact_shortcode( $atts, $content ) {
		$contact = get_contactdata();

		if ( $contact ) {
			return do_shortcode( $content );
		} else {
			return '';
		}
	}

	/**
	 * Output content if and only if the current visitor is NOT a contact
	 *
	 * @param        $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function is_not_contact_shortcode( $atts, $content ) {
		$contact = get_contactdata();

		if ( $contact ) {
			return '';
		} else {
			return do_shortcode( $content );
		}
	}

	/**
	 * Return the content if and only if the contact does have given tags
	 *
	 * @param        $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function contact_has_tag_shortcode( $atts, $content ) {
		$a = shortcode_atts( array(
			'tags' => '',
			'has'  => 'all'
		), $atts );

		$tags = wp_parse_id_list( $a['tags'] );

		$contact = get_current_contact();

		if ( ! is_a_contact( $contact ) ) {
			return '';
		}

		switch ( $a['has'] ) {
			case 'all':

				if ( ! $contact->has_tags( $tags ) ) {
					return '';
				}

				return do_shortcode( $content );
			case 'one':
			case 'any':
			case 'single':
			case '1':
				foreach ( $tags as $tag ) {
					if ( $contact->has_tag( $tag ) ) {
						return do_shortcode( $content );
					}
				}

				return '';
			default:
				return '';
		}
	}

	/**
	 * Return content if and only if the contact does not have the given tags
	 *
	 * @param        $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function contact_does_not_have_tag_shortcode( $atts, $content ) {
		$a = shortcode_atts( array(
			'tags'  => '',
			'needs' => 'all'
		), $atts );

		$tags = wp_parse_id_list( $a['tags'] );

		$contact = get_current_contact();

		// If there is no contact, they defs don't have the tag!
		if ( ! is_a_contact( $contact ) ) {
			return do_shortcode( $content );
		}

		switch ( $a['needs'] ) {
			case 'all':
				if ( $contact->has_tags( $tags ) ) {
					return '';
				}

				return do_shortcode( $content );
			case 'one':
			case 'single':
			case 'any':
			case '1':
				foreach ( $tags as $tag ) {
					if ( ! $contact->has_tag( $tag ) ) {
						return do_shortcode( $content );
					}
				}

				return '';
			default:
				return do_shortcode( $content );
		}
	}


	/**
	 * Return contents if and only if the contact is logged in
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 */
	function is_logged_in( $atts, $content ) {
		if ( is_user_logged_in() ) {
			return do_shortcode( $content );
		} else {
			return '';
		}
	}

	/**
	 * Return content if user is no logged in.
	 *
	 * @param $atts
	 * @param $content
	 *
	 * @return string
	 */
	function is_not_logged_in( $atts, $content ) {
		if ( ! is_user_logged_in() ) {
			return do_shortcode( $content );
		} else {
			return '';
		}
	}
}
