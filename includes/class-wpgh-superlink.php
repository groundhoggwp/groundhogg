<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-21
 * Time: 2:00 PM
 */
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

        $link_path  = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
        $link_parts = explode( '/', $link_path );
        $this->ID   = intval( $link_parts[ count( $link_parts ) - 1 ] );

        if ( ! WPGH()->superlinks->exists( $this->ID ) )
            return;

        $link = WPGH()->superlinks->get_superlink( $this->ID );

        $this->target = esc_url_raw( WPGH()->replacements->process( $link->target ) );
        $this->tags = maybe_unserialize( $link->tags );

        $this->contact_id = WPGH()->tracking->get_contact()->ID;

        add_action( 'template_redirect', array( $this, 'process' ) );

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