<?php
namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;

/**
 * Admin Notification
 *
 * Registers the admin notification step in the funnel builder.
 * USes WP_MAIL to send all notifications
 *
 * @package     Elements
 * @subpackage  Elements/Actions
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class Admin_Notification extends Action
{

    /**
     * @return string
     */
    public function get_help_article()
    {
        return 'https://docs.groundhogg.io/docs/builder/actions/admin-notification/';
    }

    /**
     * An error if something goes wrong while sending the notification.
     *
     * @var \WP_Error
     */
    private $mail_error;

    /**
     * Get the element name
     *
     * @return string
     */
    public function get_name()
    {
        return _x( 'Admin Notification', 'step_name', 'groundhogg' );
    }

    /**
     * Get the element type
     *
     * @return string
     */
    public function get_type()
    {
        return 'admin_notification';
    }

    /**
     * Get the description
     *
     * @return string
     */
    public function get_description()
    {
        return _x( 'Send an email notification to any email or list of emails.', 'step_description', 'groundhogg' );
    }

    /**
     * Get the icon URL
     *
     * @return string
     */
    public function get_icon()
    {
        return GROUNDHOGG_ASSETS_URL . '/images/funnel-icons/admin-notification.png';
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {

        $this->start_controls_section();

        $this->add_control( 'sent_to', [
            'label' => __( 'Send To:', 'groundhogg' ),
            'type' => HTML::INPUT,
            'default' => get_bloginfo( 'admin_email' ),
            'description' => __( 'Use any email address or the {owner_email} replacement code.', 'groundhogg' )
        ] );

        $this->add_control( 'reply_to', [
            'label'         => __( 'Reply To:', 'groundhogg' ),
            'type'          => HTML::INPUT,
            'default'       => "{email}",
            'description'   => __( 'The email address which you can reply to. Use any address or the {email} code.', 'groundhogg' )
        ] );

        $this->add_control( 'subject', [
            'label'         => __( 'Subject:', 'groundhogg' ),
            'type'          => HTML::INPUT,
            'default'       => "Admin notification for {full_name} ({email})",
            'description'   => __( 'Use any valid replacement codes.', 'groundhogg' )
        ] );

        $this->add_control( 'note_text', [
            'label'         => __( 'Content:', 'groundhogg' ),
            'type'          => HTML::TEXTAREA,
            'default'       => "Please follow up with {full_name} soon.\nEmail: {email}]\nPhone: {phone}",
            'description'   => __( 'Use any valid replacement codes.', 'groundhogg' ),
            'field'         => [
                'cols'  => 64,
                'rows'  => 4
            ],
        ] );

        $this->end_controls_section();
    }

    /**
     * Save the step settings
     *
     * @param $step Step
     */
    public function save( $step )
    {

        $send_to = $this->get_posted_data( 'send_to' );

        if ( $send_to ){
            $send_to = sanitize_text_field( $send_to );
            $emails = array_map( 'trim', explode( ',', $send_to ) );
            $sanitized_emails = array();

            foreach ( $emails as $email ){
                $sanitized_emails[] = ( $email === '{owner_email}' )? '{owner_email}' : sanitize_email( $email );
            }

            $send_to = implode( ',', $sanitized_emails );
            $this->save_setting( 'send_to', $send_to );
        }

        $reply_to = $this->get_posted_data( 'reply_to' );

        if ( $reply_to ){
            $reply_to = sanitize_text_field( $reply_to );
            $emails = array_map( 'trim', explode( ',', $reply_to ) );
            $email = array_shift( $emails );
            $reply_to = ( $email === '{email}' )? '{email}' : sanitize_email( $email );
            $this->save_setting( 'reply_to', $reply_to );
        }

        $this->save_setting( 'subject', sanitize_text_field( $this->get_posted_data( 'subject' ) ) );
        $this->save_setting( 'note_text', sanitize_textarea_field( $this->get_posted_data( 'note_text' ) ) );
    }

    /**
     * Process the apply note step...
     *
     * @param $contact Contact
     * @param $event Event
     *
     * @return bool|\WP_Error
     */
    public function run( $contact, $event )
    {

        $note = $this->get_setting( 'note_text' );

        $finished_note = sanitize_textarea_field( Plugin::$instance->replacements->process( $note, $contact->get_id() ) );

        $finished_note.= sprintf( "\n\n%s: %s", __( 'Manage Contact', 'groundhogg' ), admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->get_id()  ) );

        $subject = $this->get_setting( 'subject' );
        $subject = sanitize_text_field( Plugin::$instance->replacements->process( $subject, $contact->get_id() ) );

        $send_to = $this->get_setting( 'send_to' );
        $reply_to = $this->get_setting( 'reply_to', $contact->get_email() );

        if ( ! is_email( $send_to ) ){
            $send_to = Plugin::$instance->replacements->process( $send_to, $contact->get_id() );
        }

        if ( ! $send_to ){
            return false;
        }

        add_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

        $sent = wp_mail( $send_to, $subject, $finished_note, [
            sprintf( 'Reply-To: <%s>', $reply_to )
        ] );

        remove_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

        if ( $this->has_errors() ){
            return $this->get_last_error();
        }

        return $sent;

    }
    /**
     * Map the error to the whatever
     *
     * @param $result
     */
    public function mail_failed( $result )
    {
        $this->add_error( $result );
    }
}