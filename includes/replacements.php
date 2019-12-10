<?php
namespace Groundhogg;

if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Replacements
 *
 * The inspiration for this class came from EDD_Email_Tags by easy digital downloads.
 * But ours is better because it allows for dynamic arguments passed with the replacements code.
 *
 * @package     Includes
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */
class Replacements
{

    /**
     * Array of replacement codes and their callback functions
     *
     * @var array
     */
    var $replacement_codes = array();

    /**
     * The contact ID
     *
     * @var int
     */
    var $contact_id;

    /**
     * @var Contact
     */
    protected $current_contact;

    /**
     * Replacements constructor.
     */
    public function __construct()
    {

        add_action( 'init', [ $this, 'setup_defaults' ] );

        if ( isset_not_empty( $_GET, 'page' ) && strpos( $_GET[ 'page' ], 'gh_' ) !== false ) {
            add_action( 'admin_footer', [ $this, 'replacements_in_footer' ] );
        }

    }

    /**
     * Setup the default replacement codes
     */
    public function setup_defaults()
    {

        $replacements = array(
            array(
                'code' => 'id',
                'callback' => [ $this, 'replacement_id' ],
                'description' => _x( 'The contact\'s ID number.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'first',
                'callback' => [ $this, 'replacement_first_name' ],
                'description' => _x( 'The contact\'s first name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'first_name',
                'callback' => [ $this, 'replacement_first_name' ],
                'description' => _x( 'The contact\'s first name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'last',
                'callback' => [ $this, 'replacement_last_name' ],
                'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'last_name',
                'callback' => [ $this, 'replacement_last_name' ],
                'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'full_name',
                'callback' => [ $this, 'replacement_full_name' ],
                'description' => _x( 'The contact\'s full name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'username',
                'callback' => [ $this, 'replacement_username' ],
                'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'email',
                'callback' => [ $this, 'replacement_email' ],
                'description' => _x( 'The contact\'s email address.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'phone',
                'callback' => [ $this, 'replacement_phone' ],
                'description' => _x( 'The contact\'s phone number.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'phone_ext',
                'callback' => [ $this, 'replacement_phone_ext' ],
                'description' => _x( 'The contact\'s phone number extension.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'address',
                'callback' => [ $this, 'replacement_address' ],
                'description' => _x( 'The contact\'s full address.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'company_name',
                'callback' => [ $this, 'replacement_company_name' ],
                'description' => _x( 'The contact\'s company name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'job_title',
                'callback' => [ $this, 'replacement_job_title' ],
                'description' => _x( 'The contact\'s job title.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'company_address',
                'callback' => [ $this, 'replacement_company_address' ],
                'description' => _x( 'The contact\'s company address.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'meta',
                'callback' => [ $this, 'replacement_meta' ],
                'description' => _x( 'Any meta data related to the contact. Usage: {meta.attribute}', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'user',
                'callback' => [ $this, 'replacement_user' ],
                'description' => _x( 'Any data related to the contact\'s linked user record. Usage: {user.attribute}', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'business_name',
                'callback' => [ $this, 'replacement_business_name' ],
                'description' => _x( 'The business name as defined in the settings.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'business_phone',
                'callback' => [ $this, 'replacement_business_phone' ],
                'description' => _x( 'The business phone number as defined in the settings.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'business_address',
                'callback' => [ $this, 'replacement_business_address' ],
                'description' => _x( 'The business address as defined in the settings.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'site_url',
                'callback' => [ $this, 'site_url' ],
                'description' => _x( 'The site url.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'owner_first_name',
                'callback' => [ $this, 'replacement_owner_first_name' ],
                'description' => _x( 'The contact owner\'s name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'owner_last_name',
                'callback' => [ $this, 'replacement_owner_last_name' ],
                'description' => _x( 'The contact owner\'s name.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'owner_email',
                'callback' => [ $this, 'replacement_owner_email' ],
                'description' => _x( 'The contact owner\'s email address.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'owner_phone',
                'callback' => [ $this, 'replacement_owner_phone' ],
                'description' => _x( 'The contact owner\'s phone number.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'confirmation_link',
                'callback' => [ $this, 'replacement_confirmation_link' ],
                'description' => _x( 'A link to confirm the email address of a contact.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'confirmation_link_raw',
                'callback' => [ $this, 'replacement_confirmation_link_raw' ],
                'description' => _x( 'A link to confirm the email address of a contact which can be placed in a button or link.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'date',
                'callback' => [ $this, 'replacement_date' ],
                'description' => _x( 'Insert a dynamic date. Usage {date.format|time}. Example: {date.Y-m-d|+2 days}', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'files',
                'callback' => [ $this, 'replacement_files' ],
                'description' => _x( 'Insert all the files in a contact\'s file box.', 'replacement', 'groundhogg' ),
            ),
            array(
                'code' => 'groundhogg_day_quote',
                'callback' => [ $this, 'get_random_groundhogday_quote' ],
                'description' => _x( 'Inserts a random quote from the movie Groundhog Day featuring Bill Murray', 'replacement', 'groundhogg' ),
            )
        );

        $replacements = apply_filters( 'groundhogg/replacements/defaults', $replacements );

        foreach ( $replacements as $replacement ) {
            $this->add( $replacement[ 'code' ], $replacement[ 'callback' ], $replacement[ 'description' ] );
        }

        do_action( 'groundhogg/replacements/init', $this );
    }

    /**
     * Add a replacement code
     *
     * @param $code string the code
     * @param $callback string|array the callback function
     * @param string $description string description of the code
     *
     * @return bool
     */
    function add( $code, $callback, $description = '' )
    {
        if ( !$code || !$callback )
            return false;

        if ( is_callable( $callback ) ) {
            $this->replacement_codes[ $code ] = array(
                'code' => $code,
                'callback' => $callback,
                'description' => $description
            );

            return true;
        }

        return false;

    }

    /**
     * Remove a replacement code
     *
     * @param string $code to remove
     * @since 1.9
     *
     */
    public function remove( $code )
    {
        unset( $this->replacement_codes[ $code ] );
    }

    /**
     * See if the replacement code exists already
     *
     * @param $code
     *
     * @return bool
     */
    function has_replacement( $code )
    {
        return array_key_exists( $code, $this->replacement_codes );
    }

    /**
     * Returns a list of all replacement codes
     *
     * @return array
     * @since 1.9
     *
     */
    public function get_replacements()
    {
        return $this->replacement_codes;
    }

    /**
     * Process the codes based on the given contact ID
     *
     * @param $contact_id_or_email int|bool|Contact ID of the contact
     * @param $content
     *
     * @return string
     */
    public function process( $content, $contact_id_or_email = false )
    {

        if ( !preg_match( '/{([^{}]+)}/', $content ) ) {
            return $content;
        }

        if ( $contact_id_or_email instanceof Contact ){
            $contact = $contact_id_or_email;
        } else {
            $contact = get_contactdata( $contact_id_or_email );
        }

        if ( ! $contact || ! $contact->exists() ){
            return $content;
        }

        $this->contact_id = $contact->get_id();
        $this->current_contact = $contact ;

        // Check if there is at least one tag added
        if ( empty( $this->replacement_codes ) || !is_array( $this->replacement_codes ) ) {
            return $content;
        }

        $new_content = preg_replace_callback( "/{([^{}]+)}/s", array( $this, 'do_replacement' ), $content );

        return $new_content;
    }

    /**
     * @return Contact
     */
    protected function get_current_contact()
    {
        return $this->current_contact;
    }

    /**
     * @param string $code
     * @return array
     */
    protected function parse_code( $code = '' )
    {

        $default = $code;
        $arg = false;

        //Support Default Arguments.
        if ( strpos( $code, '::' ) > 0 ) {
            $parts = explode( '::', $code, 2 );
            $code = $parts[ 0 ];
            $default = $parts[ 1 ];
        }

        /* make sure that if it's a dynamic code to remove anything after the period */
        if ( strpos( $code, '.' ) > 0 ) {
            $parts = explode( '.', $code, 2 );
            $code = $parts[ 0 ];
            $arg = $parts[ 1 ];
        }

        return [
            'code' => $code,
            'arg' => $arg,
            'default' => $default
        ];

    }

    /**
     * Process the given replacement code
     *
     * @param $m
     *
     * @return mixed
     */
    private function do_replacement( $m )
    {
        // Get tag
        $code = $m[ 1 ];

        $parts = $this->parse_code( $code );

        $arg = $parts[ 'arg' ];
        $code = $parts[ 'code' ];
        $default = $parts[ 'default' ];

        // Return tag if tag not set
        if ( !$this->has_replacement( $code ) && substr( $code, 0, 1 ) !== '_' ) {
            return $default;
        }

        // Access contact fields.
        if ( substr( $code, 0, 1 ) === '_' ) {
            $field = substr( $code, 1 );
            $text = $this->get_current_contact()->$field;
        } else if ( $arg ) {
            $text = call_user_func( $this->replacement_codes[ $code ][ 'callback' ], $arg, $this->contact_id, $code );
        } else {
            $text = call_user_func( $this->replacement_codes[ $code ][ 'callback' ], $this->contact_id, $code );
        }

        if ( empty( $text ) ) {
            $text = $default;
        }

        return apply_filters( "groundhogg/replacements/{$code}", $text );

    }

    public function get_table()
    {
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
            <tr>
                <th><?php _e( 'Replacement Code' ); ?></th>
                <th><?php _e( 'Description' ); ?></th>
            </tr>
            </thead>
            <tbody>

            <?php foreach ( $this->get_replacements() as $replacement ): ?>
                <tr>
                    <td>
                        <input class="replacement-selector"
                               style="border: none;outline: none;background: transparent;width: 100%;"
                               onfocus="this.select();" value="{<?php echo $replacement[ 'code' ]; ?>}" readonly>
                    </td>
                    <td>
                        <span><?php esc_html_e( $replacement[ 'description' ] ); ?></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }

    public function replacements_in_footer()
    {
        ?>
        <div id="footer-replacement-codes" class="hidden">
            <?php $this->get_table(); ?>
        </div>
        <?php
    }

    public function show_replacements_button( $short = false )
    {
        wp_enqueue_script( 'groundhogg-admin-replacements' );

        echo Plugin::$instance->utils->html->modal_link( array(
            'title' => __( 'Replacements', 'groundhogg' ),
            'text' => $short ? '<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>' : '<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>&nbsp;' . _x( 'Insert Replacement', 'replacement', 'groundhogg' ),
            'footer_button_text' => __( 'Insert' ),
            'id' => 'replacements',
            'class' => 'button button-secondary no-padding replacements replacements-button',
            'source' => 'footer-replacement-codes',
            'height' => 900,
            'width' => 700,
        ) );

    }


    /**
     * Return the contact meta
     *
     * @param $contact_id int
     * @param $arg string the meta key
     * @return mixed|string
     */
    function replacement_meta( $arg, $contact_id )
    {
        if ( empty( $arg ) )
            return '';

        return print_r( $this->get_current_contact()->get_meta( $arg ), true );
    }

    /**
     * Return the contact meta
     *
     * @param $contact_id int
     * @param $arg string the meta key
     * @return mixed|string
     */
    function replacement_user( $arg, $contact_id )
    {
        if ( empty( $arg ) || ! $this->get_current_contact()->get_user_id() )
            return '';

        $rep = $this->get_current_contact()->get_userdata()->$arg;

        // Try to get from meta
        if ( ! $rep ){
            $rep = get_user_meta( $this->get_current_contact()->get_user_id(), $arg, true );
        }

        return print_r( $rep, true );
    }


    /**
     * Return back the ID of the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the first name
     */
    function replacement_id( $contact_id )
    {
        return $contact_id;
    }

    /**
     * Return back the first name ot the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the first name
     */
    function replacement_first_name( $contact_id )
    {
        return $this->get_current_contact()->get_first_name();
    }

    /**
     * Return back the last name ot the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the last name
     */
    function replacement_last_name( $contact_id )
    {
        return $this->get_current_contact()->get_last_name();
    }

    /**
     * Return back the full name ot the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the last name
     */
    function replacement_full_name( $contact_id )
    {
        return $this->get_current_contact()->get_full_name();
    }

    /**
     * Return the username of the contact if one exists.
     *
     * @param $contact_id int the contact's id
     * @return string
     */
    function replacement_username( $contact_id )
    {
        return $this->get_current_contact()->get_userdata() ? $this->get_current_contact()->get_userdata()->user_login : $this->get_current_contact()->get_email();
    }

    /**
     * Return back the email of the contact.
     *
     * @param $contact_id int the contact ID
     * @return string the email
     */
    function replacement_email( $contact_id )
    {
        return $this->get_current_contact()->get_email();
    }

    /**
     * Return back the phone # ot the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the first name
     */
    function replacement_phone( $contact_id )
    {
        return $this->get_current_contact()->get_phone_number();
    }

    /**
     * Return back the phone # ext the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the first name
     */
    function replacement_phone_ext( $contact_id )
    {
        return $this->get_current_contact()->get_phone_extension();
    }

    /**
     * Return back the address of the contact.
     *
     * @param $contact_id int the contact_id
     * @return string the first name
     */
    function replacement_address( $contact_id )
    {
        $address = implode( ', ', $this->get_current_contact()->get_address() );

        return $address;

    }

    /**
     * Get the company name of a contact
     *
     * @param $contact_id
     * @return mixed
     */
    function replacement_company_name( $contact_id )
    {
        return $this->get_current_contact()->get_meta( 'company_name' );
    }

    /**
     * Get the company address of a contact
     *
     * @param $contact_id
     * @return mixed
     */
    function replacement_company_address( $contact_id )
    {
        return $this->get_current_contact()->get_meta( 'company_address' );
    }

    /**
     * Get the job title of a contact
     *
     * @param $contact_id
     * @return mixed
     */
    function replacement_job_title( $contact_id )
    {
        return $this->get_current_contact()->get_meta( 'job_title' );
    }

    /**
     * Return back the email address of the contact owner.
     *
     * @param $contact_id int the contact ID
     * @return string the owner's email
     */
    function replacement_owner_email( $contact_id )
    {
        $user = $this->get_current_contact()->get_ownerdata();

        if ( !$user )
            return get_bloginfo( 'admin_email' );

        return $user->user_email;
    }

    /**
     * Return back the first name of the contact owner.
     *
     * @param $contact_id int the contact
     * @return string the owner's name
     */
    function replacement_owner_first_name( $contact_id )
    {
        $user = $this->get_current_contact()->get_ownerdata();

        if ( !$user ) {
            // return admin details
            $user = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
        }

        return $user->first_name;
    }

    /**
     * Return back the first name of the contact owner.
     *
     * @param $contact_id int the contact
     * @return string the owner's name
     */
    function replacement_owner_last_name( $contact_id )
    {
        $user = $this->get_current_contact()->get_ownerdata();

        if ( !$user ) {
            //return admin details
            $user = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
        }

        return $user->last_name;
    }

    function replacement_owner_phone( $contact_id )
    {
        $user = $this->get_current_contact()->get_ownerdata();

        if ( !$user || !$user->phone ) {
            return $this->replacement_business_phone();
        }

        return $user->phone;
    }

    /**
     * Return a confirmation link for the contact
     * This just gets the Optin Page link for now.
     *
     * @return string the optin link
     */
    function replacement_confirmation_link()
    {
        $link_text = apply_filters( 'groundhogg/replacements/confirmation_text', Plugin::$instance->settings->get_option( 'confirmation_text', __( 'Confirm your email.', 'groundhogg' ) ) );
        $link_url = managed_page_url( 'preferences/confirm/' );

        return sprintf( "<a href=\"%s\" target=\"_blank\">%s</a>", $link_url, $link_text );
    }

    /**
     * Return a raw confirmation link for the contact that can be placed in a button.
     * This just gets the Optin Page link for now.
     *
     * @return string the optin link
     */
    function replacement_confirmation_link_raw()
    {
        $link_url = managed_page_url( 'preferences/confirm/' );
        return $link_url;
    }

    /**
     * @return string
     */
    function site_url()
    {
        return site_url();
    }

    /**
     * Return a formatted date in local time.
     *
     * @param $time_string
     *
     * @return string
     */
    function replacement_date( $time_string )
    {

        $parts = preg_split( "/\||;/", $time_string );

        if ( count( $parts ) === 1 ) {
            $format = get_date_time_format();
            $when = $parts[ 0 ];
        } else {
            $format = $parts[ 0 ];
            $when = $parts[ 1 ];
        }

        /* convert to local time */
        $time = strtotime( $when );

        return date_i18n( $format, $time );
    }

    /**
     * Return the business name
     *
     * @return string
     */
    function replacement_business_name()
    {
        return Plugin::$instance->settings->get_option( 'business_name' );
    }

    /**
     * Return eh business phone #
     *
     * @return string
     */
    function replacement_business_phone()
    {
        return Plugin::$instance->settings->get_option( 'phone' );
    }

    /**
     * Return the business address
     *
     * @return array|string
     */
    function replacement_business_address()
    {
        $address_keys = [
            'street_address_1',
            'street_address_2',
            'zip_or_postal',
            'city',
            'region',
            'country',
        ];

        $address = [];

        foreach ( $address_keys as $key ) {

            $val = Plugin::$instance->settings->get_option( $key );
            if ( !empty( $val ) ) {
                $address[ $key ] = $val;
            }
        }

        $address = implode( ', ', $address );

        return $address;
    }

    /**
     * Get a file download link from a contact record.
     *
     * @param $key string|int the key for the file
     * @param $contact_id int
     *
     * @return string
     */
    function replacement_files( $key = '', $contact_id = null )
    {
        // Backwards compat
        if ( ! $contact_id ){
            $contact_id = $key;
            $key = false;
        }

        $files = $this->get_current_contact()->get_files();

        if ( empty( $files ) ) {
            return __( 'No files found.', 'groundhogg' );
        }

        $html = '';

        foreach ( $files as $i => $file ) {
            $html .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $file[ 'file_url' ] ), esc_html( $file[ 'file_name' ] ) );
        }

        return sprintf( '<ul>%s</ul>', $html );
    }

    /**
     * Return a random quote from the movie groundhog day staring bill murray.
     * Also the movie of which branding is based upon.
     *
     * @return mixed
     */
    function get_random_groundhogday_quote()
    {
        $quotes = array();

        $quotes[] = "I'm not going to live by their rules anymore!";
        $quotes[] = "When Chekhov saw the long winter, he saw a winter bleak and dark and bereft of hope. Yet we know that winter is just another step in the cycle of life. But standing here among the people of Punxsutawney and basking in the warmth of their hearths and hearts, I couldn't imagine a better fate than a long and lustrous winter.";
        $quotes[] = "Hi, three cheeseburgers, two large fries, two milkshakes, and one large coke.";
        $quotes[] = "It's the same thing every day, Clean up your room, stand up straight, pick up your feet, take it like a man, be nice to your sister, don't mix beer and wine ever, Oh yeah, don't drive on the railroad tracks.";
        $quotes[] = "I'm a god, I'm not the God. I don't think.";
        $quotes[] = "Don't drive angry! Don't drive angry!";
        $quotes[] = "I'm betting he's going to swerve first.";
        $quotes[] = "You want a prediction about the weather? You're asking the wrong Phil. I'm going to give you a prediction about this winter? It's going to be cold, it's going to be dark and it's going to last you for the rest of your lives!";
        $quotes[] = "We mustn't keep our audience waiting.";
        $quotes[] = "Okay campers, rise and shine, and don't forget your booties cause its cold out there...its cold out there every day.";
        $quotes[] = "I peg you as a glass half empty kinda guy.";
        $quotes[] = "Well, what if there is no tomorrow? There wasn't one today.";
        $quotes[] = "Did he actually refer to himself as \"the talent\"?";
        $quotes[] = "Did you sleep well Mr. Connors?";

        $quotes = apply_filters( 'add_movie_quotes', $quotes );

        $quote = rand( 0, count( $quotes ) - 1 );

        return $quotes[ $quote ];
    }

}