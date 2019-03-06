<?php
/**
 * Bounce Checker
 *
 * This will add an action to the recurring WPGH_cron_event o check the bounce inbox (if given) for bounced email addresses
 *
 * We have HEAVILY modified the BounceHandler class as it was incompatible at the time of implementation with modern PHP 7
 *
 * @uses BounceHandler
 *
 * @package     Include
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Bounce_Checker
{
    /**
     * The inbox in which bounces are located
     *
     * @var mixed|void
     */
    var $inbox;

    /**
     * The inbox password
     *
     * @var mixed|void
     */
    var $password;

    /**
     * The bounce handler class
     *
     * @var BounceHandler
     */
    var $bounce_handler;

    public function __construct()
    {

        if ( ! wpgh_get_option( 'gh_bounce_inbox', false ) || ! wpgh_get_option( 'gh_bounce_inbox_password', false ) )
            return;

        include_once dirname( __FILE__ ) . '/lib/PHP-Bounce-Handler-master/bounce_driver.class.php';

        $this->bounce_handler = new BounceHandler();

        $this->inbox    = wpgh_get_option( 'gh_bounce_inbox' );
        $this->password = wpgh_get_option( 'gh_bounce_inbox_password' );

        /* run whenever these jobs are run */
        add_action( 'init', array( $this, 'setup_cron' ) );
        add_action( 'wpgh_check_bounces', array( $this, 'check' )  );

        if ( is_admin() && isset( $_GET[ 'test_imap_connection' ] ) ){
            add_action( 'init', array( $this, 'do_test_connection' ) );
        }
    }

    public function setup_cron()
    {
        if ( ! wp_next_scheduled( 'wpgh_check_bounces' )  ){
            wp_schedule_event( time(), 'hourly' , 'wpgh_check_bounces' );
        }
    }

    public function test_connection_ui(){

        if ( $this->inbox && $this->password ){
            ?>
<a href="<?php echo add_query_arg( 'test_imap_connection', '1', $_SERVER[ 'REQUEST_URI' ] ); ?>" class="button-secondary"><?php _ex( 'Test IMAP Connection', 'action', 'groundhogg' ) ?></a>
<?php
        }

    }

    /**
     * Test the bounce inbox connection
     */
    public function do_test_connection()
    {
        if ( ! is_admin() || ! current_user_can( 'manage_options' ) ){
            return;
        }

        $test = $this->test_connection();

        if ( is_wp_error( $test ) ){
            WPGH()->notices->add( $test );
            return;
        }

        WPGH()->notices->add( 'imap_success', _x( 'Successful IMAP connection established.', 'notice', 'groundhogg' ) );

    }

    /**
     * Test the bounce inbox connection
     *
     * @return bool|WP_Error
     */
    public function test_connection()
    {
        $domain = explode( '@', $this->inbox );
        $domain = $domain[1];
        $domain = wpgh_get_option( 'gh_bounce_inbox_host', $domain );
        $port = wpgh_get_option( 'gh_bounce_inbox_port', 993 );

        $hostname = sprintf( '{%s:%d/imap/ssl/novalidate-cert}INBOX', $domain, $port );

        /* try to connect */
        try{
            $inbox = @imap_open( $hostname, $this->inbox, $this->password, OP_READONLY );
//            $errors = imap_errors();
//            $alerts = imap_alerts();

            if ( $inbox ){
                imap_close( $inbox );
            }
        } catch ( Exception $e ){
            $inbox = new WP_Error( $e->getCode(), $e->getMessage() );
        }

//        ob_clean();

        if ( is_wp_error( $inbox ) ){
            return $inbox;
        }

        if ( ! $inbox ){
            return new WP_Error( 'imap_failed', sprintf( "Failed to connect. Error: %s", imap_last_error() ) );
        }

        return true;
    }

    public function check()
    {
        $domain = explode( '@', $this->inbox );
        $domain = $domain[1];
        $domain = wpgh_get_option( 'gh_bounce_inbox_host', $domain );
        $port = wpgh_get_option( 'gh_bounce_inbox_port', 993 );

        $hostname = sprintf( '{%s:%d/imap/ssl/novalidate-cert}INBOX', $domain, $port );

        /* try to connect */
        $inbox = imap_open( $hostname, $this->inbox, $this->password, OP_READONLY );

        if ( ! $inbox ){
            return;
        }

        /* grab emails, for now assume these messages go unread */
        $emails = imap_search( $inbox, sprintf( 'SINCE "%s" UNSEEN', date( 'j F Y', strtotime( '1 day ago' ) ) ) );

        if ( ! $emails )
            return;

        //print_r( $bounce_handler->bouncelist );

        foreach( $emails as $email_number ) {

            /* get information specific to this email */
            $message = imap_fetchbody( $inbox, $email_number,"" );
	        $multiArray = $this->bounce_handler->get_the_facts( $message );

            foreach( $multiArray as $the ){

                $contact  = new WPGH_Contact( $the['recipient'] );

                if ( ! $contact->email ){
                    continue;
                }

                switch( $the['action'] ){
                    case 'failed':
                        //do something
                        if ( $contact->optin_status !== WPGH_HARD_BOUNCE ){

                            $contact->add_note( sprintf( $this->bounce_handler->fetch_status_messages( $the['status'] ) ) );
                            $contact->update(
                                array( 'optin_status' => WPGH_HARD_BOUNCE )
                            );

                        }
                        break;
                    case 'transient':
                        //do something else
                        break;
                    case 'autoreply':
                        //do something different
                        break;
                    default:
                        //don't do anything
                        break;
                }
            }
        }

        imap_close( $inbox );
    }

}