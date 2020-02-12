<?php
namespace Groundhogg;

class Preferences
{
    // Optin Statuses
    const UNCONFIRMED   = 1;
    const CONFIRMED     = 2;
    const UNSUBSCRIBED  = 3;
    const WEEKLY        = 4;
    const MONTHLY       = 5;
    const HARD_BOUNCE   = 6;
    const SPAM          = 7;
    const COMPLAINED    = 8;

    public function __construct()
    {
        add_action( 'init', [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
        add_filter( 'template_include', [ $this, 'template_include' ] );
    }

    /**
     * Add the rewrite rules required for the Preferences center.
     */
    public function add_rewrite_rules()
    {
        add_managed_rewrite_rule( 'preferences/([^/?]*)', 'subpage=preferences&action=$matches[1]', 'top' );
    }

    /**
     * Add the query vars needed to manage the request.
     *
     * @param $vars
     * @return array
     */
    public function add_query_vars( $vars )
    {
        $vars[] = 'subpage';
        $vars[] = 'action';
        return $vars;
    }

    /**
     * Overwrite the existing template with the manage preferences template.
     *
     * @param $template
     * @return string
     */
    public function template_include( $template )
    {
        if ( ! is_managed_page() ){
            return $template;
        }

        $page = get_query_var( 'subpage' );

        if ( $page !== 'preferences' ){
            return $template;
        }

        $new_template = GROUNDHOGG_PATH . 'templates/preferences.php';
        if ( file_exists( $new_template ) ){
            return $new_template;
        }

        return $template;
    }

    /**
     * Get the text explanation for the optin status of a contact
     * 0 = unconfirmed, can send email
     * 1 = confirmed, can send email
     * 2 = opted out, can't send email
     *
     * @param $id_or_email int|string the contact in question
     *
     * @return bool|string
     */
    public function get_optin_status_text( $id_or_email )
    {
        $contact = Plugin::instance()->utils->get_contact( $id_or_email );

        if ( ! $contact ){
            return _x( 'No Contact', 'notice', 'groundhogg' );
        }

        if ( $this->is_gdpr_enabled() && $this->is_gdpr_strict() ) {
            $consent = $contact->get_meta( 'gdpr_consent' );
            if ( $consent !== 'yes' ){
                return _x( 'This contact has not agreed to receive email marketing from you.', 'optin_status', 'groundhogg' );
            }
        }

        switch ( $contact->get_optin_status() ){
            default:
            case self::UNCONFIRMED:
                if ( $this->is_confirmation_strict() ) {
                    if ( ! $this->is_in_grace_period( $contact->ID ) ){
                        return _x( 'Unconfirmed. This contact will not receive emails, they are passed the email confirmation grace period.', 'optin_status', 'groundhogg' );
                    }
                }
                return _x( 'Unconfirmed. They will receive marketing.', 'optin_status', 'groundhogg' );
                break;
            case self::CONFIRMED:
                return _x( 'Confirmed. They will receive marketing.', 'optin_status', 'groundhogg' );
                break;
            case self::UNSUBSCRIBED:
                return _x( 'Unsubscribed. They will not receive marketing.','optin_status', 'groundhogg' );
                break;
            case self::WEEKLY:
	            return _x( 'This contact will only receive marketing weekly.', 'optin_status','groundhogg' );
	            break;
	        case self::MONTHLY:
		        return _x( 'This contact will only receive marketing monthly.', 'optin_status','groundhogg' );
		        break;
            case self::HARD_BOUNCE:
                return _x( 'This email address bounced, they will not receive marketing.', 'optin_status', 'groundhogg' );
                break;
            case self::SPAM:
                return _x( 'This contact was marked as spam. They will not receive marketing.','optin_status','groundhogg' );
                break;
            case self::COMPLAINED:
                return _x( 'This contact complained about your emails. They will not receive marketing.', 'optin_status','groundhogg' );
                break;
        }
    }

	/**
	 * @param $preference int
	 *
	 * @return string
	 */
    public static function get_preference_pretty_name( $preference )
    {
	    switch ( $preference ){
		    default:
		    case self::UNCONFIRMED:
			    return _x( 'Unconfirmed', 'optin_status', 'groundhogg' );
			    break;
		    case self::CONFIRMED:
			    return _x( 'Confirmed', 'optin_status', 'groundhogg' );
			    break;
		    case self::UNSUBSCRIBED:
			    return _x( 'Unsubscribed', 'optin_status', 'groundhogg' );
			    break;
		    case self::WEEKLY:
			    return _x( 'Subscribed Weekly', 'optin_status','groundhogg' );
			    break;
		    case self::MONTHLY:
			    return _x( 'Subscribed Monthly', 'optin_status','groundhogg' );
			    break;
		    case self::HARD_BOUNCE:
			    return _x( 'Bounced', 'optin_status', 'groundhogg' );
			    break;
		    case self::SPAM:
			    return _x( 'Spam', 'optin_status','groundhogg' );
			    break;
		    case self::COMPLAINED:
			    return _x( 'Complained', 'optin_status','groundhogg' );
			    break;
	    }
    }

    /**
     * Return whether the contact is marketable or not.
     *
     * @return bool
     */
    public function is_marketable( $id_or_email )
    {
        $contact = Plugin::instance()->utils->get_contact( $id_or_email );

        if ( ! $contact ){
            return _x( 'No Contact', 'notice', 'groundhogg' );
        }

        /* check for strict GDPR settings */
        if ( $this->is_gdpr_enabled() && $this->is_gdpr_strict() ) {
            $consent = $contact->get_meta( 'gdpr_consent' );
            if ( $consent !== 'yes' ){
                return false;
            }
        }

        switch ( $contact->get_optin_status() )
        {
	        default:
	        case self::UNCONFIRMED:
                /* check for grace period if necessary */
                if ( $this->is_confirmation_strict() ) {
                    if ( ! $this->is_in_grace_period( $contact->ID ) )
                        return false;
                }

                return true;
                break;
	        case self::CONFIRMED:
                return true;
                break;
	        case self::SPAM;
	        case self::COMPLAINED;
	        case self::HARD_BOUNCE;
	        case self::UNSUBSCRIBED:
                return false;
                break;
	        case self::WEEKLY:
                $last_sent = $contact->get_meta( 'last_sent' );
                return ( time() - absint( $last_sent ) ) > 7 * 24 * HOUR_IN_SECONDS;
                break;
	        case self::MONTHLY:
                $last_sent = $contact->get_meta( 'last_sent' );
                return ( time() - absint( $last_sent ) ) > 30 * 24 * HOUR_IN_SECONDS;
                break;
        }
    }

    /**
     * Check if GDPR is enabled throughout the plugin.
     *
     * @return bool, whether it's enable or not.
     */
    public function is_gdpr_enabled()
    {
        return Plugin::$instance->settings->is_option_enabled( 'enable_gdpr' );
    }

    /**
     * check if the GDPR strict option is enabled
     *
     * @return bool
     */
    public function is_gdpr_strict()
    {
        return Plugin::$instance->settings->is_option_enabled( 'strict_gdpr' );
    }

    /**
     * Whether strict confirmation is enabled for CASL.
     *
     * @return bool
     */
    public function is_confirmation_strict()
    {
        return Plugin::$instance->settings->is_option_enabled( 'strict_confirmation' );
    }

    /**
     * Get the grace period for confirmation
     *
     * @return mixed
     */
    public function get_grace_period()
    {
        return Plugin::$instance->settings->get_option( 'confirmation_grace_period', 14 );
    }


    /**
     * Return whether the given contact is within the strict confirmation grace period
     *
     * @param $id_or_email
     * @return bool
     */
    public function is_in_grace_period( $id_or_email )
    {

        $contact = Plugin::instance()->utils->get_contact( $id_or_email );

        $grace = absint( $this->get_grace_period() ) * 24 * HOUR_IN_SECONDS;

        $base = absint( $contact->last_optin );

        if ( ! $base )
        {
            $base = strtotime( $contact->get_date_created() );
        }

        $time_passed = time() - $base;

        return $time_passed < $grace;
    }

}