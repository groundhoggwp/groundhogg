<?php
/**
 * Shortcodes
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */


// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Alternate form shortcode
 *
 * @param $atts
 * @param $content
 *
 * @return string
 */
function wpgh_custom_form_shortcode( $atts, $content )
{
    $form = new WPGH_Form( $atts, $content );

    return sprintf( "%s", $form );
}

add_shortcode( 'gh_form', 'wpgh_custom_form_shortcode' );

/**
 * Prevent the shortcode api from texturizing the contents of [gh_form_alt]
 *
 * @param $list
 * @return array
 */
function wpgh_no_texturize_form( $list )
{
    $list[] = 'gh_form';
    return $list;
}

add_filter( 'no_texturize_shortcodes', 'wpgh_no_texturize_form' );

/**
 * Mere contact replacements into page content with this shortcode.
 *
 * @param $atts array should be empty
 * @param string $content the content to perform the merge fields
 * @return string the updated content,.
 */
function wpgh_merge_replacements_shortcode( $atts, $content = '' )
{
    $contact = WPGH()->tracking->get_contact();

    if ( ! $contact )
        return '';

    return WPGH()->replacements->process( $content, $contact->ID );
}

add_shortcode( 'gh_replacements', 'wpgh_merge_replacements_shortcode' );

/**
 * Process the contact shortcode
 */
function wpgh_contact_replacement_shortcode( $atts )
{
	$a = shortcode_atts( array(
		'field' => 'first'
	), $atts );

    $contact = WPGH()->tracking->get_contact();

	if ( ! $contact )
		return __( 'Friend', 'groundhogg' );

	$content = sprintf( '{%s}', $a[ 'field' ] );

	return WPGH()->replacements->process( $content, $contact->ID );
}

add_shortcode( 'gh_contact', 'wpgh_contact_replacement_shortcode' );

/**
 * Output content if and only if the current visitor is a contact.
 *
 * @param $atts[]
 * @param string $content
 * @return string
 */
function wpgh_is_contact_shortcode( $atts, $content='' )
{
    $contact = WPGH()->tracking->get_contact();

    if ( $contact ) {
        return $content;
    } else {
        return '';
    }
}

add_shortcode( 'gh_is_contact', 'wpgh_is_contact_shortcode' );

/**
 * Output content if and only if the current visitor is NOT a contact
 *
 * @param $atts
 * @param string $content
 * @return string
 */
function wpgh_is_not_contact_shortcode( $atts, $content='' )
{
    $contact = WPGH()->tracking->get_contact();

    if ( $contact ) {
        return '';
    } else {
        return $content;
    }
}

add_shortcode( 'gh_is_not_contact', 'wpgh_is_not_contact_shortcode' );

/**
 * Return the content if and only if the contact does have given tags
 *
 * @param $atts
 * @param string $content
 * @return string
 */
function wpgh_contact_has_tag_shortcode( $atts, $content='' )
{
    $a = shortcode_atts( array(
        'tags' => '',
        'has' => 'all'
    ), $atts );

    $tags = explode( ',', $a[ 'tags' ] );
    $tags = array_map( 'trim', $tags );
    $tags = array_map( 'intval', $tags );

    $contact = WPGH()->tracking->get_contact();

    if ( ! $contact ) {
        return '';
    }

    switch ( $a[ 'has' ] ){
        case 'all':
            foreach ( $tags as $tag ){
                if ( ! $contact->has_tag( $tag ) ) {
                    return '';
                }
            }
            return $content;
            break;
        case 'one':
        case 'single':
        case '1':
            foreach ( $tags as $tag ){
                if ( $contact->has_tag( $tag ) ) {
                    return $content;
                }
            }
            return '';

            break;
        default:
            return '';
    }
}

add_shortcode( 'gh_has_tags', 'wpgh_contact_has_tag_shortcode' );


/**
 * Return content if and only if the contact does not have the given tags
 *
 * @param $atts
 * @param string $content
 * @return string
 */
function wpgh_contact_does_not_have_tag_shortcode( $atts, $content='' )
{
    $a = shortcode_atts( array(
        'tags' => '',
        'needs' => 'all'
    ), $atts );

    $tags = explode( ',', $a[ 'tags' ] );
    $tags = array_map( 'trim', $tags );
    $tags = array_map( 'intval', $tags );

    $contact = WPGH()->tracking->get_contact();

    if ( ! $contact ) {
        return '';
    }

    switch ( $a[ 'needs' ] ){
        case 'all':
            foreach ( $tags as $tag ){
                if ( $contact->has_tag( $tag ) ) {
                    return '';
                }
            }
            return $content;
            break;
        case 'one':
        case 'single':
        case '1':
            foreach ( $tags as $tag ){
                if ( ! $contact->has_tag( $tag ) ) {
                    return $content;
                }
            }
            return '';
            break;
        default:
            return $content;
    }
}

add_shortcode( 'gh_does_not_have_tags', 'wpgh_contact_does_not_have_tag_shortcode' );

/**
 * Return contents if and only if the contact is logged in
 *
 * @param $atts
 * @param $content
 *
 * @return string
 */
function wpgh_is_logged_in( $atts, $content )
{
    if ( is_user_logged_in() )
        return do_shortcode( $content );
    else
        return '';
}

add_shortcode( 'gh_is_logged_in', 'wpgh_is_logged_in' );

/**
 * Return content if user is no logged in.
 *
 * @param $atts
 * @param $content
 * @return string
 */
function wpgh_is_not_logged_in( $atts, $content ){
    if ( ! is_user_logged_in() )
        return do_shortcode( $content );
    else
        return '';
}

add_shortcode( 'gh_is_not_logged_in', 'wpgh_is_not_logged_in' );
