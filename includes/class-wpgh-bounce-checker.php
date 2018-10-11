<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-09-28
 * Time: 2:55 PM
 */

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
        if ( ! get_option( 'gh_bounce_inbox', false ) || ! get_option( 'gh_bounce_inbox_password', false ) )
            return;

        include_once dirname( __FILE__ ) . '/lib/PHP-Bounce-Handler-master/bounce_driver.class.php';

        $this->bounce_handler = new BounceHandler();

        $this->inbox    = get_option( 'gh_bounce_inbox' );
        $this->password = get_option( 'gh_bounce_inbox_password' );

        /* run whenever these jobs are run */
        add_action( 'wpgh_cron_event', array( $this, 'check' ) );
    }

    public function check()
    {
        $domain = explode( '@', $this->inbox );
        $domain = $domain[1];

        $hostname = sprintf( '{%s:993/imap/ssl/novalidate-cert}INBOX', $domain );

        /* try to connect */
        $inbox = imap_open( $hostname, $this->inbox, $this->password, OP_READONLY );

        if ( ! $inbox ){
            wp_die( imap_last_error() );
            return;
        }

        /* grab emails, for now assume these messages go unread */
        $emails = imap_search( $inbox, sprintf( 'SINCE "%s" UNSEEN', date( 'j F Y', strtotime( '1 day ago' ) ) ) );

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