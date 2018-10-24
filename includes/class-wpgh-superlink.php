<?php
/**
 * Superlink
 *
 * Process a superlink if one is in progress.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Superlink
{
    /**
     * The current superlink process
     *
     * @var int the ID
     */
    public $ID;

    /**
     * The target URL
     *
     * @var string
     */
    public $target;

    /**
     * List of tags to apply to the contact
     *
     * @var array
     */
    public $tags;

    /**
     * The current contact to provide the redirect to.
     *
     * @var
     */
    public $contact_id;

    /**
     * WPGH_Superlink constructor.
     */
    function __construct() {

        if ( strpos( $_SERVER['REQUEST_URI'], '/superlinks/link/' ) === false ) {
            return;
        }

        add_action( 'init', array( $this, 'setup' ) );
        add_action( 'template_redirect', array( $this, 'process' ) );

    }

    public function setup()
    {
        $link_path  = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $link_parts = explode( '/', $link_path );
        $this->ID   = intval( $link_parts[ count( $link_parts ) - 1 ] );

        if ( ! WPGH()->superlinks->exists( $this->ID ) ){
            remove_action(  'template_redirect', array( $this, 'process' )  );
            return;
        }

        $link = WPGH()->superlinks->get_superlink( $this->ID );

        $this->contact_id = WPGH()->tracking->get_contact()->ID;

        $this->target = esc_url_raw( WPGH()->replacements->process( $link->target, $this->contact_id ) );

        $this->tags = maybe_unserialize( $link->tags );

    }


    /**
     * Whether the current process will result in a redirect to the new target.
     *
     * @return bool
     */
    public function doing_superlink()
    {
        return ! empty( $this->target );
    }

    /**
     * Redirect to the superlink target
     */
    public function process()
    {
        $contact = new WPGH_Contact( $this->contact_id );

        if ( ! empty( $this->tags ) && $contact->exists() ){

            foreach ( $this->tags as $tag )
            {
                if ( $contact ){
                    $contact->add_tag( $tag );
                }
            }

        }

        wp_redirect( $this->target );
        die();
    }


}