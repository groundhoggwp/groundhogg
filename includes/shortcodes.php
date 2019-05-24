<?php
namespace Groundhogg;

use Groundhogg\Form\Form;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Shortcodes
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Shortcodes
{

    public function __construct()
    {
        $this->register_shortcodes();
    }

    public function register_shortcodes()
    {
        add_filter( 'no_texturize_shortcodes', [ $this, 'no_texturize_form' ] );

        add_shortcode( 'gh_form', [ $this, 'custom_form_shortcode' ] );
        add_shortcode( 'gh_replacements', [ $this, 'merge_replacements_shortcode' ] );
        add_shortcode( 'ghr', [ $this, 'merge_replacements_shortcode' ] );
        add_shortcode( 'gh_contact', 'contact_replacement_shortcode' );
        add_shortcode( 'gh_is_contact', 'is_contact_shortcode' );
        add_shortcode( 'gh_is_not_contact', 'is_not_contact_shortcode' );
        add_shortcode( 'gh_is_not_logged_in', 'is_not_logged_in' );
        add_shortcode( 'gh_is_logged_in', 'is_logged_in' );
        add_shortcode( 'gh_does_not_have_tags', 'contact_does_not_have_tag_shortcode' );
        add_shortcode( 'gh_has_tags', 'contact_has_tag_shortcode' );
    }

    /**
     * Alternate form shortcode
     *
     * @param $atts
     * @param $content
     *
     * @return string
     */
    public function custom_form_shortcode( $atts )
    {
        $form = new Form( $atts );

        return sprintf( "%s", $form );
    }
    
    /**
     * Prevent the shortcode api from texturizing the contents of [gh_form_alt]
     *
     * @param $list
     * @return array
     */
    public function no_texturize_form( $list )
    {
        $list[] = 'gh_form';
        return $list;
    }
    
    /**
     * Mere contact replacements into page content with this shortcode.
     *
     * @param $atts array should be empty
     * @param string $content the content to perform the merge fields
     * @return string the updated content,.
     */
    public function merge_replacements_shortcode( $atts, $content = '' )
    {
        $contact = Plugin::$instance->tracking->get_current_contact();

        if ( ! $contact )
            return '';

        return Plugin::$instance->replacements->process( do_shortcode( $content ), $contact->ID );
    }
    
    /**
     * Process the contact shortcode
     */
    function contact_replacement_shortcode( $atts )
    {
        $a = shortcode_atts( array(
            'field' => 'first'
        ), $atts );

        $contact = Plugin::$instance->tracking->get_current_contact();

        if ( ! $contact )
            return __( 'Friend', 'groundhogg' );

        $content = sprintf( '{%s}', $a[ 'field' ] );

        return Plugin::$instance->replacements->process( $content, $contact->ID );
    }
    
    /**
     * Output content if and only if the current visitor is a contact.
     *
     * @param $atts[]
     * @param string $content
     * @return string
     */
    function is_contact_shortcode( $atts, $content )
    {
        $contact = Plugin::$instance->tracking->get_current_contact();

        if ( $contact ) {
            return do_shortcode( $content );
        } else {
            return '';
        }
    }
    
    /**
     * Output content if and only if the current visitor is NOT a contact
     *
     * @param $atts
     * @param string $content
     * @return string
     */
    function is_not_contact_shortcode( $atts, $content )
    {
        $contact = Plugin::$instance->tracking->get_current_contact();

        if ( $contact ) {
            return '';
        } else {
            return do_shortcode( $content );
        }
    }
    
    /**
     * Return the content if and only if the contact does have given tags
     *
     * @param $atts
     * @param string $content
     * @return string
     */
    function contact_has_tag_shortcode( $atts, $content )
    {
        $a = shortcode_atts( array(
            'tags' => '',
            'has' => 'all'
        ), $atts );

        $tags = explode( ',', $a[ 'tags' ] );
        $tags = array_map( 'trim', $tags );
        $tags = array_map( 'intval', $tags );

        $contact = Plugin::$instance->tracking->get_current_contact();

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
                return do_shortcode( $content );
                break;
            case 'one':
            case 'single':
            case '1':
                foreach ( $tags as $tag ){
                    if ( $contact->has_tag( $tag ) ) {
                        return do_shortcode( $content );
                    }
                }
                return '';

                break;
            default:
                return '';
        }
    }

    /**
     * Return content if and only if the contact does not have the given tags
     *
     * @param $atts
     * @param string $content
     * @return string
     */
    function contact_does_not_have_tag_shortcode( $atts, $content )
    {
        $a = shortcode_atts( array(
            'tags' => '',
            'needs' => 'all'
        ), $atts );

        $tags = explode( ',', $a[ 'tags' ] );
        $tags = array_map( 'trim', $tags );
        $tags = array_map( 'intval', $tags );

        $contact = Plugin::$instance->tracking->get_current_contact();

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
                return do_shortcode( $content );
                break;
            case 'one':
            case 'single':
            case '1':
                foreach ( $tags as $tag ){
                    if ( ! $contact->has_tag( $tag ) ) {
                        return do_shortcode( $content );
                    }
                }
                return '';
                break;
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
    function is_logged_in( $atts, $content )
    {
        if ( is_user_logged_in() )
            return do_shortcode( $content );
        else
            return '';
    }

    /**
     * Return content if user is no logged in.
     *
     * @param $atts
     * @param $content
     * @return string
     */
    function is_not_logged_in( $atts, $content ){
        if ( ! is_user_logged_in() )
            return do_shortcode( $content );
        else
            return '';
    }
}
