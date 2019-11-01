<?php

namespace Groundhogg\Steps\Actions;

use Groundhogg\Contact;
use function Groundhogg\do_replacements;
use Groundhogg\Event;
use Groundhogg\HTML;
use Groundhogg\Plugin;
use Groundhogg\Step;
use function Groundhogg\is_sms_plugin_active;
use function Groundhogg\validate_mobile_number;

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

if ( !defined( 'ABSPATH' ) ) exit;

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
        return _x( 'Send an email or SMS notification to any email or list of emails.', 'step_description', 'groundhogg' );
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

    protected function is_sms()
    {
        return (bool) $this->get_setting( 'is_sms' );
    }

    /**
     * @param $step Step
     */
    public function settings( $step )
    {
        $this->start_controls_section();

        if ( is_sms_plugin_active() ) {
            $this->add_control( 'is_sms', [
                'label' => __( 'Send as SMS', 'groundhogg' ),
                'type' => HTML::CHECKBOX,
                'default' => false,
                'field' => [
                    'label' => Plugin::$instance->utils->html->wrap( __( 'Send as a text message instead of as an email.', 'groundhogg' ), 'span', [ 'class' => 'description' ] ),
                    'class' => 'auto-save'
                ],
                'description' => false
            ] );
        }

        if ( !$this->is_sms() || !is_sms_plugin_active() ) {
            $this->add_control( 'send_to', [
                'label' => __( 'Send To:', 'groundhogg' ),
                'type' => HTML::INPUT,
                'default' => get_bloginfo( 'admin_email' ),
                'description' => __( 'Use any email address or the {owner_email} replacement code.', 'groundhogg' )
            ] );

            $this->add_control( 'reply_to', [
                'label' => __( 'Reply To:', 'groundhogg' ),
                'type' => HTML::INPUT,
                'default' => "{email}",
                'description' => __( 'The email address which you can reply to. Use any address or the {email} code.', 'groundhogg' )
            ] );

            $this->add_control( 'subject', [
                'label' => __( 'Subject:', 'groundhogg' ),
                'type' => HTML::INPUT,
                'default' => "Admin notification for {full_name} ({email})",
                'description' => __( 'Use any valid replacement codes.', 'groundhogg' )
            ] );
        } else {
            $this->add_control( 'send_to_sms', [
                'label' => __( 'Send To:', 'groundhogg' ),
                'type' => HTML::INPUT,
                'default' => get_option( 'gh_business_phone' ),
                'description' => __( 'Use any mobile phone number. Include country code!', 'groundhogg' )
            ] );
        }

        $this->add_control( 'note_text', [
            'label' => __( 'Content:', 'groundhogg' ),
            'type' => HTML::TEXTAREA,
            'default' => "Please follow up with {full_name} soon.\nEmail: {email}\nPhone: {phone}",
            'description' => __( 'Use any valid replacement codes.', 'groundhogg' ),
            'field' => [
                'cols' => 64,
                'rows' => 4
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

        if ( $send_to ) {
            $send_to = sanitize_text_field( $send_to );
            $emails = array_map( 'trim', explode( ',', $send_to ) );
            $sanitized_emails = array();

            foreach ( $emails as $email ) {
                $sanitized_emails[] = ( $email === '{owner_email}' ) ? '{owner_email}' : sanitize_email( $email );
            }

            $send_to = implode( ',', $sanitized_emails );
            $this->save_setting( 'send_to', $send_to );
        }

        if ( is_sms_plugin_active() ) {
            $send_to_sms = $this->get_posted_data( 'send_to_sms' );

            if ( $send_to_sms ) {
                $send_to_sms = sanitize_text_field( $send_to_sms );
                $numbers = array_map( 'trim', explode( ',', $send_to_sms ) );
                $sanitized_numbers = array();

                foreach ( $numbers as $number ) {
                    $sanitized_numbers[] = preg_replace( '/[^0-9]/', '', $number );
                }

                $send_to = implode( ', ', $sanitized_numbers );
                $this->save_setting( 'send_to_sms', $send_to );
            }
        }

        $reply_to = $this->get_posted_data( 'reply_to' );

        if ( $reply_to ) {
            $reply_to = sanitize_text_field( $reply_to );
            $emails = array_map( 'trim', explode( ',', $reply_to ) );
            $email = array_shift( $emails );
            $reply_to = ( $email === '{email}' ) ? '{email}' : sanitize_email( $email );
            $this->save_setting( 'reply_to', $reply_to );
        }

        if ( is_sms_plugin_active() ) {
            $this->save_setting( 'is_sms', boolval( $this->get_posted_data( 'is_sms' ) ) );
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

        $finished_note = sanitize_textarea_field( do_replacements( $note, $contact->get_id() ) );

        $is_sms = $this->get_setting( 'is_sms', false );

        // Email
        if ( !$is_sms ) {
            $finished_note .= sprintf( "\n\n======== %s ========\nEdit: %s\nReply: %s", __( 'Manage Contact', 'groundhogg' ),
                admin_url( 'admin.php?page=gh_contacts&action=edit&contact=' . $contact->get_id() ),
                $contact->get_email()
            );

            $subject = $this->get_setting( 'subject' );
            $subject = sanitize_text_field( do_replacements( $subject, $contact->get_id() ) );

            $send_to = $this->get_setting( 'send_to' );
            $reply_to = do_replacements( $this->get_setting( 'reply_to', $contact->get_email() ), $contact->get_id() );

            if ( !is_email( $send_to ) ) {
                $send_to = do_replacements( $send_to, $contact->get_id() );
            }

            if ( !$send_to ) {
                return false;
            }

            add_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );

            $headers = [];

            if ( is_email( $reply_to ) ) {
                $headers = sprintf( 'Reply-To: <%s>', $reply_to );
            }

            $sent = wp_mail( $send_to, $subject, $finished_note, $headers );

            remove_action( 'wp_mail_failed', [ $this, 'mail_failed' ] );
        } else {

            if ( !is_sms_plugin_active() ) {
                return new \WP_Error( 'sms_inactive', 'The SMS extension was not found.' );
            }

            $to = $this->get_setting( 'send_to_sms' );

            $sent = false;

            if ( is_array( $to ) ) {
                foreach ( $to as $number ) {
                    if ( function_exists( '\GroundhoggSMS\send_sms' ) ) {
                        $sent = \GroundhoggSMS\send_sms( $number, $finished_note );
                    }
                }
            } else {
                if ( function_exists( '\GroundhoggSMS\send_sms' ) ) {
                    $sent = \GroundhoggSMS\send_sms( $to, $finished_note );
                }
            }

            return $sent;
        }

        if ( $this->has_errors() ) {
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