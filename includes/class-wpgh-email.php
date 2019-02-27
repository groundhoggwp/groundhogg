<?php
/**
 * Email
 *
 * Lots of helper methods... also where the actual sending of emails occurs.
 *
 * One thing to note is the template.
 *
 * You may add your own email templates by defining, email-template.php in your theme.
 * The default template is email-default.php
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Email
{
    /**
     * The ID of the email
     *
     * @var string
     */
    public $ID;

    /**
     * The email subject line
     *
     * @var string
     */
    public $subject;

    /**
     * the email pre header
     *
     * @var string
     */
    public $pre_header;

    /**
     * The email content
     *
     * @var string
     */
    public $content;

    /**
     * The ID of the funnel
     *
     * @var int
     */
    public $funnel_id = 0;

    /**
     * The ID of the step
     *
     * @var int
     */
    public $step_id;

    /**
     * The email sender
     *
     * @var int
     */
    public $from_user = 0;

    /**
     * Who wrote the email
     *
     * @var int
     */
    public $author;

    /**
     * The date the email was last updated
     *
     * @var string
     */
    public $last_updated;

    /**
     * The date the email was created
     *
     * @var string
     */
    public $date_created;

    /**
     * The status of the email, draft or ready
     *
     * @var string
     */
    public $status;

    /**
     * Whether the email is a test or not.
     *
     * @var bool
     */
    public $testing = false;

    /**
     * A contact which may or may not be need. (optional)
     *
     * @var WPGH_Contact
     */
    public $contact;

    /**
     * The event related to this email send
     *
     * @var object
     */
    public $event;

    /**
     * The template to use
     *
     * @var
     */
    public $template = 'default';

    /**
     * @var string
     */
    protected $previous_altbody;

    /**
     * @var string
     */
    public $error_message;

    /**
     * WPGH_Email constructor.
     * @param $id
     */
    public function __construct($id)
    {

        $this->ID = intval($id);

        if ( ! WPGH()->emails->exists( $id ) )
            return false;

        $email = (object) WPGH()->emails->get_email( $id );

        $this->setup_email($email);

    }

    /**
     * Setup the email object
     *
     * @param $email
     * @return bool
     */
    public function setup_email($email)
    {

        if (!is_object($email)) {
            return false;
        }

        foreach ($email as $key => $value) {

            switch ($key) {
                default:
                    $this->$key = $value;
                    break;

            }

        }

        // Id and subject must exist.
        if (!empty($this->ID) && !empty($this->subject)) {
            return true;
        }

        return false;

    }

    public function exists()
    {
        return $this->ID > 0;
    }

    /**
     * get the template type
     *
     * @return string
     */
    public function get_template()
    {
        $template = apply_filters( 'wpgh_email_template', $this->template );
        return apply_filters( 'groundhogg/email/template', $template );
    }


    /**
     * Turns on test mode
     */
    public function enable_test_mode()
    {
        $this->testing = true;
    }

    /**
     * Whether browser view is enabled
     *
     * @param $bool
     *
     * @return bool
     */
    public function browser_view_enabled($bool)
    {
        return boolval($this->get_meta('browser_view', true));
    }

    /**
     * Whether the current email contains a confirmation link.
     *
     * @return bool
     */
    public function is_confirmation_email()
    {
        return strpos( $this->content, '{confirmation_link}' ) !== false;
    }

    /**
     * Return the browser view option for this email.
     *
     * @param $link
     *
     * @return string
     */
    public function browser_link( $link )
    {

        if ( wpgh_is_global_multisite() ){
            switch_to_blog( get_site()->site_id );
        }

        $url = get_permalink( wpgh_get_option( 'gh_view_in_browser_page' ) ) . '?email=' . $this->ID ;

        if ( is_multisite() && ms_is_switched() ){
            restore_current_blog();
        }

        return $url;
    }

    /**
     * Return the tracking link for this email when opened.
     *
     * @return string
     */
    public function get_open_tracking_link()
    {
        return site_url(sprintf(
            "gh-tracking/email/open/?u=%s&e=%s&i=%s",
            dechex($this->contact->ID),
            dechex($this->event->ID),
            dechex($this->ID)
        ));
    }

    /**
     * Return the tracking link for this email when a link is clicked.
     *
     * @return string
     */
    public function get_click_tracking_link()
    {
        return site_url(
            sprintf( 'gh-tracking/email/click/?u=%s&e=%s&i=%s&ref=',
                dechex($this->contact->ID),
                dechex($this->event->ID),
                dechex($this->ID)
            )
        );
    }

    /**
     * Add alignment CSS to the email content for outlook
     *
     * @param $css string the email's current css
     *
     * @return string
     */
    public function get_alignment_outlook($css)
    {
        $alignment = $this->get_meta('alignment', true);
        return ($alignment === 'left')? '' : 'center';
    }

    /**
     * Add alignment CSS to the email content
     *
     * @param $css string the email's current css
     *
     * @return string
     */
    public function get_alignment($css)
    {
        $alignment = $this->get_meta('alignment', true);
        $margins = ($alignment === 'left') ? "margin-left:0;margin-right:auto;" : "margin-left:auto;margin-right:auto;";
        return $css . $margins;
    }

    /**
     * Get the email being sent to
     *
     * @return  string
     */
    public function get_to()
    {
       $to = apply_filters( 'wpgh_email_to', $this->contact->email );
       return apply_filters( 'groundhogg/email/to', $to );
    }

    /**
     * Get the subject line for the email.
     *
     * @return string
     */
    public function get_subject_line()
    {
        $subject = WPGH()->replacements->process(
            sanitize_text_field( $this->subject ),
            $this->contact->ID
        );

        $subject = apply_filters( 'wpgh_email_subject_line', $subject );
        return apply_filters( 'groundhogg/email/subject', $subject );
    }

    /**
     * Return pre header text
     * This is called by a filter rather than directly
     *
     * @param $content
     *
     * @return string
     */
    public function get_pre_header( $content )
    {
        $pre_header = WPGH()->replacements->process(
            $this->pre_header,
            $this->contact->ID
        );

        $pre_header = apply_filters( 'wpgh_email_pre_header', $pre_header );
        return apply_filters( 'groundhogg/email/pre_header', $pre_header );
    }

    /**
     * Return email content
     * This is called by a filter rather than directly
     *
     * @param $content
     *
     * @return string
     */
    public function get_content( $content='' )
    {
        $content = WPGH()->replacements->process(
            $this->content,
            $this->contact->ID
        );

        /* filter out double http based on bug where superlinks have http:// prepended */
        $schema = is_ssl()? 'https://' : 'http://';
        $content = str_replace( 'http://https://', $schema, $content );
        $content = str_replace( 'http://http://', $schema, $content );

        /* Other filters */
        $content = apply_filters( 'wpgh_email_template_make_clickable', true ) ? make_clickable( $content ) : $content;
        $content = str_replace( '&#038;', '&amp;', $content );

        return $content;
    }

    /**
     * Convert superlinks to tracking superlinks
     *
     * @param $content string content which may contain superlinks
     *
     * @return string
     */
    public function convert_to_tracking_links( $content )
    {
        /* Filter the superlinks to include data about the email, campaign, and funnel steps... */
        return preg_replace_callback( '/(href=")(?!mailto)([^"]*)(")/i', array( $this, 'tracking_link_callback' ) , $content );
    }

    /**
     * Replace the link with another link which has the ?ref UTM which will lead to the original link
     *
     * @param $matches
     * @return string
     */
    public function tracking_link_callback( $matches )
    {
        return $matches[1] . $this->get_click_tracking_link() . urlencode( $matches[2] ) . $matches[3];
    }

    /**
     * Return footer content
     *
     * @param $content
     *
     * @return string
     */
    public function get_footer_text( $content )
    {
        $footer = "";

        if ( wpgh_is_global_multisite() ){
            switch_to_blog( get_site()->site_id );
        }

        if ( wpgh_get_option( 'gh_business_name' ) )
            $footer .= "&copy; {business_name}<br/>";

        if ( wpgh_get_option( 'gh_street_address_1' ) )
            $footer .= "{business_address}<br/>";

        $sub = array();

        if ( wpgh_get_option( 'gh_phone', 0 ) ) {
            $sub[] = sprintf(
                "<a href='tel:%s'>%s</a>",
                esc_attr( wpgh_get_option('gh_phone') ),
                esc_attr( wpgh_get_option('gh_phone') )
            );
        }

        if ( wpgh_get_option( 'gh_privacy_policy' ) ) {
            $sub[] = sprintf(
                "<a href='%s'>%s</a>",
                esc_attr( get_permalink( wpgh_get_option( 'gh_privacy_policy' ) ) ),
                apply_filters( 'gh_privacy_policy_footer_text', __( 'Privacy Policy', 'groundhogg' ) )
            );
        }

        if ( wpgh_get_option( 'gh_terms' ) ) {
            $sub[] = sprintf(
                "<a href='%s'>%s</a>",
                esc_attr( get_permalink( wpgh_get_option( 'gh_terms' ) ) ),
                apply_filters( 'gh_terms_footer_text', __( 'Terms', 'groundhogg' ) )
            );
        }

        $footer .= implode(' | ', $sub );

        $footer = WPGH()->replacements->process( $footer, $this->contact->ID );

        if ( is_multisite() && ms_is_switched() ){
            restore_current_blog();
        }

        $footer = apply_filters( 'wpgh_email_footer', $footer );
        return apply_filters( 'groundhogg/email/footer', $footer );
    }

    /**
     * Get the unsub link
     *
     * @param $url
     * @return false|string
     */
    public function get_unsubscribe_link( $url )
    {
        if ( wpgh_is_global_multisite() ){
            switch_to_blog( get_site()->site_id );
        }

        $url = get_permalink( wpgh_get_option( 'gh_email_preferences_page' ) );

        if ( is_multisite() && ms_is_switched() ){
            restore_current_blog();
        }

        return $url;

    }

    /**
     * Add all the filters relevant to the email content
     */
    private function add_filters()
    {
        add_filter( 'wpgh_email_alignment',          array( $this, 'get_alignment_outlook' ) );
        add_filter( 'wpgh_email_container_css',      array( $this, 'get_alignment' ) );
        add_filter( 'wpgh_email_browser_view',       array( $this, 'browser_view_enabled' ) );
        add_filter( 'wpgh_email_browser_link',       array( $this, 'browser_link' ) );
        add_filter( 'wpgh_email_pre_header_text',    array( $this, 'get_pre_header' ) );
        add_filter( 'wpgh_email_get_content',        array( $this, 'get_content' ) );
        add_filter( 'wpgh_email_footer_text',        array( $this, 'get_footer_text' ) );
        add_filter( 'wpgh_email_unsubscribe_link',   array( $this, 'get_unsubscribe_link' ) );
        add_filter( 'wpgh_email_open_tracking_link', array( $this, 'get_open_tracking_link' ) );
    }

    /**
     * Once the content is complete you will need to remove all the filters related to that specific content.
     */
    private function remove_filters()
    {
        remove_filter( 'wpgh_email_container_css',      array( $this, 'get_alignment' ) );
        remove_filter( 'wpgh_email_browser_view',       array( $this, 'browser_view_enabled' ) );
        remove_filter( 'wpgh_email_browser_link',       array( $this, 'browser_link' ) );
        remove_filter( 'wpgh_email_pre_header_text',    array( $this, 'get_pre_header' ) );
        remove_filter( 'wpgh_email_get_content',        array( $this, 'get_content' ) );
        remove_filter( 'wpgh_email_footer_text',        array( $this, 'get_footer_text' ) );
        remove_filter( 'wpgh_email_unsubscribe_link',   array( $this, 'get_unsubscribe_link' ) );
        remove_filter( 'wpgh_email_open_tracking_link', array( $this, 'get_open_tracking_link' ) );
    }


    /**
     * Build the email
     *
     * @return string
     */
    public function build()
    {
        $templates = new WPGH_Template_Loader();

        $this->add_filters();

        ob_start();

        $template = $this->get_template();

        if ( has_action( "groundhogg/email/header/{$template}" ) ){
            /**
             *  Rather than loading the email from the default template, load whatever the custom template is.
             */
            do_action( "groundhogg/email/header/{$template}" , $this );

        } else {
            $templates->get_template_part( 'emails/header', $this->get_template() );
        }

//        wp_die( 'HI!' );
        if ( has_action( "groundhogg/email/body/{$template}" ) ){
            /**
             *  Rather than loading the email from the default template, load whatever the custom template is.
             */
            do_action( "groundhogg/email/body/{$template}" , $this );
        } else {
            $templates->get_template_part( 'emails/body', $this->get_template() );
        }

        if ( has_action( "groundhogg/email/footer/{$template}" ) ){
            /**
             *  Rather than loading the email from the default template, load whatever the custom template is.
             */
            do_action( "groundhogg/email/footer/{$template}" , $this );

        } else {
            $templates->get_template_part( 'emails/footer', $this->get_template() );

        }


        $content = ob_get_clean();
//        return ob_get_clean();

        if ( empty( $content ) )
        $content = 'No content...';


        $content = $this->minify( $content );
        $content = $this->convert_to_tracking_links( $content );

        $this->remove_filters();

        return apply_filters( 'wpgh_the_email_content', $content );
//        return $content;
    }

    /**
     * Return the from name for the email
     *
     * @return string
     */
    public function get_from_name()
    {
        if ( intval( $this->from_user ) ) {

            $from_user  = get_userdata( $this->from_user );
            return $from_user->display_name;

        } else {

            $owner = $this->contact->owner;

            if ( $owner && is_email( $owner->user_email ) ) {

                return $owner->display_name;

            } else {

                return wpgh_get_option( 'gh_business_name' );

            }

        }
    }

    /**
     * Return the from name for the email
     *
     * @return string
     */
    public function get_from_email()
    {
        if ( intval( $this->from_user ) ) {

            $from_user  = get_userdata( $this->from_user );
            return $from_user->user_email;

        } else {

            $owner = $this->contact->owner;

            if ( $owner && is_email( $owner->user_email ) ) {

                return $owner->user_email;

            } else {

                return wpgh_get_option( 'admin_email' );

            }

        }
    }

    /**
     * Get the headers to send
     *
     * @return array
     */
    public function get_headers()
    {
        /* Use default mail-server */
        $headers = array();

        $headers['from']            = 'From: ' . $this->get_from_name() . ' <' . $this->get_from_email() . '>';
        $headers['reply_to']        = 'Reply-To: ' . $this->get_from_email();
        $headers['return_path']     = 'Return-Path: ' . wpgh_get_option( 'gh_bounce_inbox', $this->get_from_email() );
        $headers['content_type']    = 'Content-Type: text/html; charset=UTF-8';
        $headers['unsub']  = sprintf( 'List-Unsubscribe: <mailto:%s?subject=Unsubscribe%%20%s>,<%s%s>', get_bloginfo( 'admin_email' ), $this->contact->email, $this->get_click_tracking_link(), urlencode( $this->get_unsubscribe_link( '' ) ) );

        $headers = apply_filters( 'wpgh_email_headers', $headers );
        return apply_filters( "groundhogg/email/headers", $headers );
    }


    /**
     * Send the email
     *
     * @param $contact WPGH_Contact|int
     * @param $event object the of the associated event
     * @return bool|WP_Error
     */
    public function send( $contact, $event = null )
    {

        if ( is_numeric( $contact ) ) {

            /* catch if contact ID given rather than WPGH_Contact */
            $contact = new WPGH_Contact( $contact );

        }

        if ( ! is_object( $contact )  )
            return new WP_Error( 'BAD_CONTACT', __( 'No contact provided...' ) );

        $this->contact = $contact;

        /* we got an event so all is well */
        if ( is_object( $event ) ){
            $this->event  = $event;

        } else if ( is_object( WPGH()->event_queue->cur_event ) ) {

            /* We didn't get an event, but it looks like one is happening so we'll get it from global scope */
            $this->event = WPGH()->event_queue->cur_event;

        } else {

            /* set a default basic event */
            $this->event = new stdClass();
            $this->event->ID = 0;

        }

        if ( ! $this->testing ){
            /* Skip if testing */

            if ( ! $contact->is_marketable() ){

                /* The contact is unmarketable so exit out. */
                return new WP_Error( 'NON_MARKETABLE', __( 'Contact is not marketable.' ) );

            }

        }

//        if ( $contact->get_meta( 'last_sent' ) ){
//            $last_sent = intval( $contact->get_meta( 'last_sent' ) );
//            if ( ( time() - $last_sent ) <= 5 ){
//                return new WP_Error( 'TOO_MANY_EMAILS', __( 'There must be a minimum 5 seconds before a contact can receive another email.' ) );
//            }
//        }

        do_action( 'wpgh_before_email_send', $this );

        /* Additional settings */
        add_action( 'phpmailer_init', array( $this, 'set_bounce_return_path' ) );
        add_action( 'phpmailer_init', array( $this, 'set_plaintext_body' ) );
        add_action( 'wp_mail_failed', array( $this, 'mail_failed' ) );
        add_filter( 'wp_mail_content_type', array( $this, 'send_in_html' ) );

        $to = $this->get_to();
        $subject = $this->get_subject_line();
        $content = $this->build();

        $headers = $this->get_headers();

        /* Send with API. Do not send with API while in TEST MODE */
        if ( wpgh_is_email_api_enabled() && ! $this->testing ){
            $sent = $this->send_with_gh(
                $to,
                $subject,
                $content,
                $headers
            );
        } else {
        /* Send with default WP */
            $sent = $this->send_with_wp(
                $to,
                $subject,
                $content,
                $headers
            );
        }


        remove_action( 'phpmailer_init', array( $this, 'set_bounce_return_path' ) );
        remove_action( 'phpmailer_init', array( $this, 'set_plaintext_body' ) );
        remove_action( 'wp_mail_failed', array( $this, 'mail_failed' ) );
        remove_filter( 'wp_mail_content_type', array( $this, 'send_in_html' ) );

        if ( ! $sent ){

            do_action( 'wpgh_email_send_failed', $this );

        } else {

            $contact->update_meta( 'last_sent', time() );

        }

        do_action( 'wpgh_after_email_send', $this );

        return $sent;

    }

    /**
     * Send with generic WP mail
     *
     * @param $to
     * @param $subject
     * @param $content
     * @param $headers
     * @return bool
     */
    private function send_with_wp( $to,$subject,$content,$headers )
    {
        return wp_mail(
            $to,
            $subject,
            $content,
            $headers
        );
    }

    /**
     * Send to Groundhogg
     *
     * @param $to
     * @param $subject
     * @param $content
     * @param $headers
     *
     * @return bool|wp_error
     */
    private function send_with_gh( $to, $subject, $content, $headers )
    {

        //send to groundhogg

        $sender = $this->get_from_email();
        $domain = explode( '@', $sender );
        $domain = $domain[1];

        $data = array(

            'token'     => md5( wpgh_get_option( 'gh_email_token' ) ),
            'domain'    => $domain,
            'sender'    => $sender,
            'from'      => $this->get_from_name(),
            'recipient' => $to,
            'subject'   => $subject,
            'content'   => $content,
        );

// validate domain and senders email address before making sending request

        $url = 'https://www.groundhogg.io/wp-json/gh/aws/v1/send-email/';

        $request = wp_remote_post( $url, array( 'body' => $data ) );

        if ( ! $request || is_wp_error( $request ) ){
            do_action( 'wp_mail_failed' ,new WP_Error( 'API_MAIL_FAILED', $request->get_error_message() ) );

            /* Fall back to default WP */
            $this->send_with_wp(
                $to,
                $subject,
                $content,
                $headers
            );

            return false;
        }

        $result = wp_remote_retrieve_body( $request );
        $result = json_decode( $result );

        if ( ! is_object( $result ) ){

            do_action( 'wp_mail_failed' ,new WP_Error( 'API_MAIL_FAILED', __( 'An unknown error occurred.', 'groudhogg' ) ) );

            /* Fall back to default WP */
            $this->send_with_wp(
                $to,
                $subject,
                $content,
                $headers
            );

            return false;

            /* Error Code */
        } else if ( ! isset( $result->status ) || $result->status !== 'success' ){

            switch ( $result->code ){

                case 'EMAIL_COMPLAINT':
                    do_action( 'wp_mail_failed', new WP_Error( 'EMAIL_COMPLAINT', $result->message ) );
                    $this->contact->update( array( 'optin_status' => WPGH_COMPLAINED ) );
                    break;
                case 'EMAIL_BOUNCED':
                    do_action( 'wp_mail_failed', new WP_Error( 'EMAIL_BOUNCED', $result->message ) );
                    $this->contact->update( array( 'optin_status' => WPGH_HARD_BOUNCE ) );
                    break;
                DEFAULT:
                    do_action( 'wp_mail_failed' ,new WP_Error( $result->code, $result->message ) );
                    break;
            }

            /* Fall back to default WP */
            $this->send_with_wp(
                $to,
                $subject,
                $content,
                $headers
            );

            return false;

        }

        return true;

    }

    /**
     * Log failures
     *
     * @param $error WP_Error
     */
    public function mail_failed( $error )
    {
        $message = sprintf(
            __( "Email failed to send.\nSend time: %s\nTo: %s\nSubject: %s\n\nError: %s", 'groundhogg' ),
            date_i18n( 'F j Y H:i:s', current_time( 'timestamp' ) ),
            $this->contact->email,
            $this->subject,
            $error->get_error_message()
        );

        $this->contact->add_note(
            $message
        );

        error_log( $message );

        $this->error_message = $error->get_error_message();
    }

    /**
     * @return string
     */
    public function get_error_message()
    {
        return $this->error_message;
    }

    /**
     * Specify that we are sending an HTML email
     *
     * @return string
     */
    public function send_in_html()
    {
        return 'text/html';
    }

    /**
     * Set the return path to the bounce email in the settings
     *
     * @param $phpmailer PHPMailer
     */
    public function set_bounce_return_path( $phpmailer )
    {
        $phpmailer->Sender = wpgh_get_option( 'gh_bounce_inbox', $phpmailer->From );
    }

    /**
     * Set the plain text version of the email
     *
     * @param $phpmailer PHPMailer
     */
    public function set_plaintext_body( $phpmailer ) {

        // don't run if sending plain text email already
        if( $phpmailer->ContentType === 'text/plain' ) {
            return;
        }

        // don't run if altbody is set (by other plugin)
        if( ! empty( $phpmailer->AltBody ) && $phpmailer->AltBody !== $this->previous_altbody ) {
            return;
        }

        // set AltBody
        $text_message = $this->strip_html_tags( $phpmailer->Body );
        $phpmailer->AltBody = $text_message;
        $this->previous_altbody = $text_message;
    }

    /**
     * Remove HTML tags, including invisible text such as style and
     * script code, and embedded objects.  Add line breaks around
     * block-level tags to prevent word joining after tag removal.
     */
	private function strip_html_tags( $text ) {
        $text = preg_replace(
            array(
                // Remove invisible content
                '@<head[^>]*?>.*?</head>@siu',
                '@<style[^>]*?>.*?</style>@siu',
                '@<script[^>]*?.*?</script>@siu',
                '@<object[^>]*?.*?</object>@siu',
                '@<embed[^>]*?.*?</embed>@siu',
                '@<noscript[^>]*?.*?</noscript>@siu',
                '@<noembed[^>]*?.*?</noembed>@siu',
                '@\t+@siu',
                '@\n+@siu'
            ),
            '',
            $text );

        // replace certain elements with a line-break
        $text = preg_replace(
            array(
                '@</?((div)|(h[1-9])|(/tr)|(p)|(pre))@iu'
            ),
            "\n\$0",
            $text );

        // replace other elements with a space
        $text = preg_replace(
            array(
                '@</((td)|(th))@iu'
            ),
            " \$0",
            $text );

        // strip all remaining HTML tags
        $text = strip_tags( $text );

        // trim text
        $text = trim( $text );

        return $text;
    }

    /**
     * Update a email record
     *
     * @since  2.3
     * @param  array  $data Array of data attributes for a email (checked via whitelist)
     * @return bool         If the update was successful or not
     */
    public function update( $data = array() ) {

        if ( empty( $data ) ) {
            return false;
        }

        do_action( 'wpgh_email_pre_update', $this->ID, $data );

        $updated = false;

        if ( WPGH()->emails->update( $this->ID, $data ) ) {

            $email = WPGH()->emails->get_email_by( 'ID', $this->ID );
            $this->setup_email( $email );

            $updated = true;
        }

        do_action( 'wpgh_email_post_update', $updated, $this->ID, $data );

        return $updated;
    }

    /**
     * Get email metadata
     *
     * @param $key
     * @param bool $single
     * @return mixed
     */
    public function get_meta( $key, $single=true )
    {
        return WPGH()->email_meta->get_meta( $this->ID, $key, $single );
    }

    /**
     * Update email meta data
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function update_meta( $key, $value )
    {
        return WPGH()->email_meta->update_meta( $this->ID, $key, $value );
    }

    /**
     * add email meta data
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function add_meta( $key, $value )
    {
        return WPGH()->email_meta->add_meta( $this->ID, $key, $value );
    }

    /**
     * delete email meta data
     *
     * @param $key
     * @param $value
     * @return mixed
     */
    public function delete_meta( $key )
    {
        return WPGH()->email_meta->delete_meta( $this->ID, $key );
    }

    /**
     * Minify html content
     *
     * @param $content
     * @return string
     */
    public function minify( $content  )
    {
        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );

        $buffer = preg_replace($search, $replace, $content);

        return $buffer;
    }

}