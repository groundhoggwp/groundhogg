<?php

namespace Groundhogg;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replacements
 *
 * The inspiration for this class came from EDD_Email_Tags by easy digital downloads.
 * But ours is better because it allows for dynamic arguments passed with the replacements code.
 *
 * @since       File available since Release 0.1
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @package     Includes
 */
class Replacements implements \JsonSerializable {

	/**
	 * Array of replacement codes and their callback functions
	 *
	 * @var array
	 */
	var $replacement_codes = [];

	/**
	 * Groups to which codes can be assigned
	 *
	 * @var array
	 */
	var $replacement_code_groups = [];

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
	public function __construct() {

		add_action( 'init', [ $this, 'setup_defaults' ] );

		if ( isset_not_empty( $_GET, 'page' ) && strpos( $_GET['page'], 'gh_' ) !== false ) {
			add_action( 'admin_footer', [ $this, 'replacements_in_footer' ] );
		}

	}

	/**
	 * Setup the default replacement codes
	 */
	public function setup_defaults() {

		$groups = [
			'contact'    => __( 'Contact', 'groundhogg' ),
			'user'       => __( 'WP User', 'groundhogg' ),
			'owner'      => __( 'Contact Owner', 'groundhogg' ),
			'site'       => __( 'Site', 'groundhogg' ),
			'post'       => __( 'Post', 'groundhogg' ),
			'compliance' => __( 'Compliance', 'groundhogg' ),
			'other'      => __( 'Other', 'groundhogg' ),
		];

		$replacement_groups = apply_filters( 'groundhogg/replacements/default_groups', $groups );

		foreach ( $replacement_groups as $group => $name ) {
			$this->add_group( $group, $name );
		}

		$replacements = [
			[
				'code'        => 'id',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_id' ],
				'name'        => __( 'Contact ID', 'groundhogg' ),
				'description' => _x( 'The contact\'s ID number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'first',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_first_name' ],
				'name'        => __( 'First Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s first name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'first_name',
				'callback'    => [ $this, 'replacement_first_name' ],
				'description' => _x( 'The contact\'s first name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'last',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_last_name' ],
				'name'        => __( 'Last Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'last_name',
				'callback'    => [ $this, 'replacement_last_name' ],
				'description' => _x( 'The contact\'s last name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'full_name',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_full_name' ],
				'name'        => __( 'Full Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s full name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'username',
				'group'       => 'user',
				'callback'    => [ $this, 'replacement_username' ],
				'name'        => __( 'User Name', 'groundhogg' ),
				'description' => _x( 'The contact\'s user record user name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'email',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_email' ],
				'name'        => __( 'Email Address', 'groundhogg' ),
				'description' => _x( 'The contact\'s email address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'phone',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_phone' ],
				'name'        => __( 'Primary Phone', 'groundhogg' ),
				'description' => _x( 'The contact\'s phone number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'phone_ext',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_phone_ext' ],
				'name'        => __( 'Primary Phone (with extension)', 'groundhogg' ),
				'description' => _x( 'The contact\'s phone number with the extension if available.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'mobile_phone',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_mobile_phone' ],
				'name'        => __( 'Mobile Phone', 'groundhogg' ),
				'description' => _x( 'The contact\'s mobile phone number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'address',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_address' ],
				'name'        => __( 'Full Address', 'groundhogg' ),
				'description' => _x( 'The contact\'s full address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'birthday',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_birthday' ],
				'name'        => __( 'Birthday', 'groundhogg' ),
				'description' => _x( 'The contact\'s birthday.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'tag_names',
				'group'       => 'contact',
				'callback'    => [ $this, 'tag_names' ],
				'name'        => __( 'Tag Names', 'groundhogg' ),
				'description' => _x( 'List of tags applied to the contact.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'meta',
				'group'        => 'contact',
				'default_args' => 'meta_key',
				'callback'     => [ $this, 'replacement_meta' ],
				'name'         => __( 'Meta Data', 'groundhogg' ),
				'description'  => _x( 'Any meta data related to the contact. Usage: {meta.attribute}', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'profile_picture',
				'group'        => 'contact',
				'default_args' => '300',
				'callback'     => [ $this, 'replacement_profile_picture' ],
				'name'         => __( 'Profile Picture', 'groundhogg' ),
				'description'  => _x( 'The contact\'s profile picture.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'user',
				'group'        => 'user',
				'default_args' => 'attribute',
				'callback'     => [ $this, 'replacement_user' ],
				'name'         => __( 'User Data', 'groundhogg' ),
				'description'  => _x( 'Any data related to the contact\'s linked user record. Usage: {user.attribute}', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'business_name',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_business_name' ],
				'name'        => __( 'Name', 'groundhogg' ),
				'description' => _x( 'The business name as defined in the settings.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'business_phone',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_business_phone' ],
				'name'        => __( 'Phone', 'groundhogg' ),
				'description' => _x( 'The business phone number as defined in the settings.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'business_address',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_business_address' ],
				'name'        => __( 'Address', 'groundhogg' ),
				'description' => _x( 'The business address as defined in the settings.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'site_url',
				'group'       => 'site',
				'callback'    => [ $this, 'site_url' ],
				'name'        => __( 'URL', 'groundhogg' ),
				'description' => _x( 'The site url.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_first_name',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_first_name' ],
				'name'        => __( 'First Name', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_last_name',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_last_name' ],
				'name'        => __( 'Last Name', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s name.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_email',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_email' ],
				'name'        => __( 'Email', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s email address.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_phone',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_phone' ],
				'name'        => __( 'Phone', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s phone number.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner_signature',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner_signature' ],
				'name'        => __( 'Email Signature', 'groundhogg' ),
				'description' => _x( 'The contact owner\'s signature.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'owner',
				'group'       => 'owner',
				'callback'    => [ $this, 'replacement_owner' ],
				'name'        => __( 'Owner Data', 'groundhogg' ),
				'description' => _x( 'Any data related to the contact\'s linked owner. Usage: {owner.attribute}', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'confirmation_link',
				'group'       => 'compliance',
				'callback'    => [ $this, 'replacement_confirmation_link' ],
				'name'        => __( 'Confirmation Link', 'groundhogg' ),
				'description' => _x( 'A link to confirm the email address of a contact.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'confirmation_link_raw',
				'group'       => 'compliance',
				'callback'    => [ $this, 'replacement_confirmation_link_raw' ],
				'name'        => __( 'Raw Confirmation Link', 'groundhogg' ),
				'description' => _x( 'A link to confirm the email address of a contact which can be placed in a button or link.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'unsubscribe_link',
				'group'       => 'compliance',
				'callback'    => [ $this, 'replacement_unsubscribe_link' ],
				'name'        => __( 'Unsubscribe Link', 'groundhogg' ),
				'description' => _x( 'A link that will unsubscribe the contact.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'auto_login_link',
				'group'       => 'site',
				'callback'    => [ $this, 'replacement_auto_login_link' ],
				'name'        => __( 'Auto-Login link', 'groundhogg' ),
				'description' => _x( 'Automatically login the contact if they have a user account.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'date',
				'group'        => 'site',
				'default_args' => 'Y-m-d|now',
				'callback'     => [ $this, 'replacement_date' ],
				'name'         => __( 'Date', 'groundhogg' ),
				'description'  => _x( 'Insert a dynamic date based on the site\'s timezone. Usage {date.format|time}. Example: {date.Y-m-d|+2 days}', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'local_date',
				'group'        => 'site',
				'default_args' => 'Y-m-d|now',
				'callback'     => [ $this, 'replacement_local_date' ],
				'name'         => __( 'Local Date', 'groundhogg' ),
				'description'  => _x( 'Same as {date} but will display in local time of the contact instead of the site.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'files',
				'group'       => 'contact',
				'callback'    => [ $this, 'replacement_files' ],
				'name'        => __( 'Files List', 'groundhogg' ),
				'description' => _x( 'Insert all the files in a contact\'s file box.', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'GET',
				'group'        => 'other',
				'callback'     => [ $this, 'replacement_get_params' ],
				'name'         => __( '$_GET', 'groundhogg' ),
				'default_args' => 'url_param',
				'description'  => _x( 'Retrieve something from the URL query string. Only works on the frontend.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'groundhogg_day_quote',
				'group'       => 'other',
				'name'        => __( 'Groundhog Day Quote', 'groundhogg' ),
				'callback'    => [ $this, 'get_random_groundhogday_quote' ],
				'description' => _x( 'Inserts a random quote from the movie Groundhog Day featuring Bill Murray!', 'replacement', 'groundhogg' ),
			],
			[
				'code'         => 'posts',
				'default_args' => 'layout=grid number=5 featured excerpt',
				'group'        => 'post',
				'callback'     => [ $this, 'posts' ],
				'name'         => __( 'Recent Posts', 'groundhogg' ),
				'description'  => _x( 'Show links posts in your email.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_title',
				'group'       => 'post',
				'callback'    => [ $this, 'post_title' ],
				'name'        => __( 'Post Title', 'groundhogg' ),
				'description' => _x( 'Return the title of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_excerpt',
				'group'       => 'post',
				'callback'    => [ $this, 'post_excerpt' ],
				'name'        => __( 'Post Excerpt', 'groundhogg' ),
				'description' => _x( 'Return the excerpt of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_content',
				'group'       => 'post',
				'callback'    => [ $this, 'post_content' ],
				'name'        => __( 'Post Content', 'groundhogg' ),
				'description' => _x( 'Return the content of a single recent post.', 'replacement', 'groundhogg' ),
			],
			[
				'code'        => 'post_featured_image',
				'group'       => 'post',
				'callback'    => [ $this, 'post_featured_image' ],
				'name'        => __( 'Post Featured Image', 'groundhogg' ),
				'description' => _x( 'Return the featured image of a single recent post.', 'replacement', 'groundhogg' ),
			],
		];

		$replacements = apply_filters( 'groundhogg/replacements/defaults', $replacements );

		foreach ( $replacements as $replacement ) {
			$this->add(
				$replacement['code'],
				$replacement['callback'],
				$replacement['description'],
				get_array_var( $replacement, 'name' ),
				get_array_var( $replacement, 'group' ),
				get_array_var( $replacement, 'default_args' )
			);
		}

		do_action( 'groundhogg/replacements/init', $this );
	}

	/**
	 * Add a replacement code
	 *
	 * @param string   $code         the code
	 * @param callable $callback     the callback function
	 * @param string   $description  string description of the code
	 * @param string   $name         the display name of the replacement for the dropdown
	 * @param string   $group        the group where it should be displayed
	 * @param string   $default_args the default args that should be inserted when selected
	 *
	 * @return bool
	 */
	function add( $code, $callback, $description = '', $name = '', $group = 'other', $default_args = '' ) {
		if ( ! $code || ! $callback ) {
			return false;
		}

		if ( is_callable( $callback ) ) {
			$this->replacement_codes[ $code ] = [
				'code'        => $code,
				'callback'    => $callback,
				'name'        => $name ?: $code,
				'group'       => $group,
				'description' => $description,
				'insert'      => ! empty( $default_args ) ? sprintf( '{%s.%s}', $code, $default_args ) : sprintf( '{%s}', $code ),
				'hidden'      => false,
			];

			return true;
		}

		return false;

	}

	/**
	 * Hide a replacement code from view
	 * Useful for making replacement codes backwards compatible without showing it in the UI
	 *
	 * @param $id
	 */
	function make_hidden( $id ) {
		$this->replacement_codes[ $id ]['hidden'] = true;
	}

	/**
	 * Register a new group
	 *
	 * @param $group
	 * @param $name
	 */
	public function add_group( $group, $name ) {
		$this->replacement_code_groups[ $group ] = $name;
	}

	/**
	 * Remove a replacement code
	 *
	 * @since 1.9
	 *
	 * @param string $code to remove
	 *
	 */
	public function remove( $code ) {
		unset( $this->replacement_codes[ $code ] );
	}

	/**
	 * Remove a replacement code group
	 *
	 * @param $group
	 */
	public function remove_group( $group ) {
		unset( $this->replacement_code_groups[ $group ] );
	}

	/**
	 * See if the replacement code exists already
	 *
	 * @param $code
	 *
	 * @return bool
	 */
	function has_replacement( $code ) {
		return array_key_exists( $code, $this->replacement_codes );
	}

	/**
	 * Returns a list of all replacement codes
	 *
	 * @since 1.9
	 *
	 * @return array
	 */
	public function get_replacements() {
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
	public function process( $content, $contact_id_or_email = false ) {

		if ( is_a_contact( $contact_id_or_email ) ) {
			$contact = $contact_id_or_email;
		} else {
			$contact = get_contactdata( $contact_id_or_email );
		}

		if ( $contact && $contact->exists() ) {
			$this->contact_id      = $contact->get_id();
			$this->current_contact = $contact;
		} else {
			$this->contact_id      = 0;
			$this->current_contact = new Contact;
		}

		return $this->tackle_replacements( $content );
	}

	/**
	 * Recursive function to tackle nested replacement codes until no more replacements are found.
	 *
	 * @param $content
	 *
	 * @return mixed
	 */
	public function tackle_replacements( $content ) {

		$pattern = '/{([^{}\n]+)}/';

		if ( ! preg_match( $pattern, $content ) ) {
			return $content;
		} // Check if there is at least one tag added
		else if ( empty( $this->replacement_codes ) || ! is_array( $this->replacement_codes ) ) {
			return $content;
		}

		return $this->tackle_replacements( preg_replace_callback( $pattern . 's', [
			$this,
			'do_replacement'
		], $content ) );
	}

	/**
	 * @return Contact
	 */
	protected function get_current_contact() {
		return $this->current_contact;
	}

	/**
	 * @param string $code
	 *
	 * @return array
	 */
	protected function parse_code( $code = '' ) {

		$default = "";
		$arg     = false;

		//Support Default Arguments.
		if ( strpos( $code, '::' ) > 0 ) {
			$parts   = explode( '::', $code, 2 );
			$code    = $parts[0];
			$default = $parts[1];
		}

		/* make sure that if it's a dynamic code to remove anything after the period */
		if ( strpos( $code, '.' ) > 0 ) {
			$parts = explode( '.', $code, 2 );
			$code  = $parts[0];
			$arg   = $parts[1];
		}

		return [
			'code'    => $code,
			'arg'     => $arg,
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
	private function do_replacement( $m ) {
		// Get tag
		$code = $m[1];

		$parts = $this->parse_code( $code );

		$arg     = $parts['arg'];
		$code    = $parts['code'];
		$default = $parts['default'];

		// Return tag if tag not set
		if ( ! $this->has_replacement( $code ) && substr( $code, 0, 1 ) !== '_' ) {
			return $default;
		}

//		else if ( ! $this->contact_id || ! $this->current_contact ){
//			return $default;
//        }

		$cache_key   = 'key:' . ( $this->contact_id ?: 'anon' ) . ':' . md5( serialize( $parts ) );
		$cache_value = wp_cache_get( $cache_key, 'replacements' );

		if ( $cache_value ) {
			return $cache_value;
		}

		// Access contact fields.
		if ( substr( $code, 0, 1 ) === '_' ) {
			$field = substr( $code, 1 );
			$text  = $this->get_current_contact()->$field;
		} else if ( $arg ) {
			$text = call_user_func( $this->replacement_codes[ $code ]['callback'], $arg, $this->contact_id, $code );
		} else {
			$text = call_user_func( $this->replacement_codes[ $code ]['callback'], $this->contact_id, $code );
		}

		if ( empty( $text ) ) {
			$text = $default;
		}

		$value = apply_filters( "groundhogg/replacements/{$code}", $text );
		wp_cache_set( $cache_key, $value, 'replacements' );

		return $value;

	}

	public function replacements_in_footer() {
		?>
        <div id="footer-replacement-codes" class="hidden">
			<?php $this->get_table(); ?>
        </div>
		<?php
	}

	public function get_table() {

		foreach ( $this->replacement_code_groups as $group => $name ):

			$codes = array_filter( $this->get_replacements(), function ( $code ) use ( $group ) {
				return $code['group'] === $group;
			} );

			?>
            <h3 class="replacements-group"><?php _e( $name ) ?></h3>
            <table class="wp-list-table widefat fixed striped replacements-table">
                <thead>
                <tr>
                    <th><?php _e( 'Name' ); ?></th>
                    <th><?php _e( 'Code' ); ?></th>
                    <th><?php _e( 'Description' ); ?></th>
                </tr>
                </thead>
                <tbody>

				<?php foreach ( $codes as $code => $replacement ):

					if ( $replacement['hidden'] ) {
						continue;
					}

					?>
                    <tr>
                        <td><?php _e( get_array_var( $replacement, 'name' ) ); ?></td>
                        <td>
                            <input class="replacement-selector code"
                                   type="text"
                                   style="border: none;outline: none;background: transparent;width: 100%;"
                                   onfocus="this.select();"
                                   value="<?php echo get_array_var( $replacement, 'insert', '{' . $code . '}' ) ?>"
                                   readonly>
                        </td>
                        <td>
                            <span class="description"><?php esc_html_e( $replacement['description'] ); ?></span>
                        </td>
                    </tr>
				<?php endforeach; ?>
                </tbody>
            </table>
		<?php
		endforeach;
	}

	public function show_replacements_button( $short = false ) {
		wp_enqueue_script( 'groundhogg-admin-replacements' );

		echo Plugin::$instance->utils->html->modal_link( array(
			'title'              => __( 'Replacements', 'groundhogg' ),
			'text'               => $short
				? '<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>'
				: '<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>&nbsp;' . _x( 'Insert Replacement', 'replacement', 'groundhogg' ),
			'footer_button_text' => __( 'Insert' ),
			'id'                 => 'replacements',
			'class'              => 'button button-secondary no-padding replacements replacements-button',
			'source'             => 'footer-replacement-codes',
			'height'             => 900,
			'width'              => 700,
		) );

	}

	public function show_replacements_dropdown( $echo = true ) {
		wp_enqueue_script( 'groundhogg-admin-replacements' );

		$options = [];

		/**
		 * Build the categorized replacements list.
		 */
		foreach ( $this->replacement_code_groups as $group => $name ) {
			$options[ $name ] = array_map_with_keys( array_map_keys( array_filter( $this->replacement_codes, function ( $atts ) use ( $group ) {
				return $atts['group'] === $group && ! $atts['hidden'];
			} ), function ( $code, $atts ) {
				return get_array_var( $atts, 'insert', '{' . $code . '}' );
			} ), function ( $atts, $code ) {
				return $atts['name'];
			} );
		}

		$return = html()->e( 'div', [
			'class' => 'replacements-dropdown-wrap'
		], [
			'<span style="vertical-align: middle" class="dashicons dashicons-admin-users"></span>',
			html()->dropdown( [
				'option_none' => _x( 'Insert Replacement', 'replacement', 'groundhogg' ),
				'name'        => 'replacement_code',
				'id'          => 'replacement-code',
				'class'       => 'replacement-code-dropdown',
				'options'     => $options
			] )
		] );

		if ( $echo ) {
			echo $return;

			return true;
		}

		return $return;
	}

	function replacement_birthday() {
		return $this->get_current_contact()->get_meta( 'birthday' );
	}

	/**
	 * Return the contact meta
	 *
	 * @param $contact_id int
	 * @param $arg        string the meta key
	 *
	 * @return mixed|string
	 */
	function replacement_meta( $arg, $contact_id ) {
		if ( empty( $arg ) ) {
			return '';
		}

		$parts    = explode( '|', $arg );
		$meta_key = get_array_var( $parts, 0 );
		$format   = get_array_var( $parts, 1 );

		$value = $this->get_current_contact()->get_meta( $meta_key );

		switch ( $format ) {
			default:
				return print_r( $value, true );
			case 'csv':
				return is_array( $value ) ? implode( ', ', $value ) : print_r( $value, true );
			case 'ol':
			case 'ul':
				return html()->e( $format, [], array_map( function ( $item ) {
					return html()->e( 'li', [], $item );
				}, is_array( $value ) ? $value : array_map( 'trim', explode( ',', $value ) ) ) );

		}
	}


	/**
	 * Return the profile_picture
	 *
	 * @param $contact_id int
	 * @param $arg        string the meta key
	 *
	 * @return mixed|string
	 */
	function replacement_profile_picture( $arg, $contact_id ) {

		$size = absint( $arg );

		if ( empty( $contact_id ) ) {
			$size = 300;
		}

		$size = min( $size, 1000 );
		$size = max( $size, 20 );

		return $this->get_current_contact()->get_profile_picture( $size );
	}


	/**
	 * Returns comma separated tags
	 *
	 * @param $contact_id
	 *
	 * @return string
	 */
	function tag_names( $contact_id ) {

		$tag_ids = $this->get_current_contact()->get_tags();
		$tags    = array_map( [ $this, 'get_contact_tag_names' ], $tag_ids );

		return implode( ',', $tags );
	}

	/**
	 * Returns tag name of the contact
	 *
	 * @param $tag_id
	 *
	 * @return string
	 */
	function get_contact_tag_names( $tag_id ) {
		$tag = new Tag( $tag_id );

		return $tag->get_name();
	}

	/**
	 * Return the contact meta
	 *
	 * @param $contact_id int
	 * @param $arg        string the meta key
	 *
	 * @return mixed|string
	 */
	function replacement_user( $arg, $contact_id ) {
		if ( empty( $arg ) || ! $this->get_current_contact()->get_user_id() ) {
			return '';
		}

		$rep = $this->get_current_contact()->get_userdata()->$arg;

		// Try to get from meta
		if ( ! $rep ) {
			$rep = get_user_meta( $this->get_current_contact()->get_user_id(), $arg, true );
		}

		return print_r( $rep, true );
	}

	/**
	 * Get something from $_GET
	 *
	 * @param $key
	 *
	 * @return mixed
	 */
	public function replacement_get_params( $key ) {
		return get_url_var( $key );
	}


	/**
	 * Return back the ID of the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_id( $contact_id ) {
		return $contact_id;
	}

	/**
	 * Return back the first name ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_first_name( $contact_id ) {
		return $this->get_current_contact()->get_first_name();
	}

	/**
	 * Return back the last name ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the last name
	 */
	function replacement_last_name( $contact_id ) {
		return $this->get_current_contact()->get_last_name();
	}

	/**
	 * Return back the full name ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the last name
	 */
	function replacement_full_name( $contact_id ) {
		return $this->get_current_contact()->get_full_name();
	}

	/**
	 * Return the username of the contact if one exists.
	 *
	 * @param $contact_id int the contact's id
	 *
	 * @return string
	 */
	function replacement_username( $contact_id ) {
		return $this->get_current_contact()->get_userdata() ? $this->get_current_contact()->get_userdata()->user_login : $this->get_current_contact()->get_email();
	}

	/**
	 * Return back the email of the contact.
	 *
	 * @param $contact_id int the contact ID
	 *
	 * @return string the email
	 */
	function replacement_email( $contact_id ) {
		return $this->get_current_contact()->get_email();
	}

	/**
	 * Return back the phone # ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_mobile_phone( $contact_id ) {
		return $this->get_current_contact()->get_mobile_number();
	}

	/**
	 * Return back the phone # ot the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_phone( $contact_id ) {
		return $this->get_current_contact()->get_phone_number();
	}

	/**
	 * Return back the phone # ext the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_phone_ext( $contact_id ) {

		$ext = $this->get_current_contact()->get_phone_extension();

		if ( $ext ) {
			return sprintf( "%s ext. %s", $this->current_contact->get_phone_number(), $this->current_contact->get_phone_extension() );
		} else {
			return $this->current_contact->get_phone_number();
		}
	}

	/**
	 * Return back the address of the contact.
	 *
	 * @param $contact_id int the contact_id
	 *
	 * @return string the first name
	 */
	function replacement_address( $contact_id ) {
		$address = implode( ', ', $this->get_current_contact()->get_address() );

		return $address;

	}


	/**
	 * Get the contact notes
	 *
	 * @param $contact_id
	 *
	 * @return mixed
	 */
	function replacement_notes( $contact_id ) {
		$notes = $this->get_current_contact()->get_all_notes();

		$return = "";

		foreach ( $notes as $note ) {
			$return .= sprintf( "\n\n===== %s =====", date( get_date_time_format(), $note->timestamp ) );
			$return .= sprintf( "\n\n%s", $note->content );
			$return .= sprintf( "\n\n%s", $note->content );
		}

		return $return;
	}


	/**
	 * Return back the email address of the contact owner.
	 *
	 * @param $contact_id int the contact ID
	 *
	 * @return string the owner's email
	 */
	function replacement_owner_email( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			return get_default_from_email();
		}

		return $user->user_email;
	}

	/**
	 * Return back the first name of the contact owner.
	 *
	 * @param $contact_id int the contact
	 *
	 * @return string the owner's name
	 */
	function replacement_owner_first_name( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			// return admin details
			$user = get_primary_owner();

			if ( ! $user ) {
				return '';
			}
		}

		return $user->first_name;
	}

	/**
	 * Return back the first name of the contact owner.
	 *
	 * @param $contact_id int the contact
	 *
	 * @return string the owner's name
	 */
	function replacement_owner_last_name( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			//return admin details
			$user = get_primary_owner();

			if ( ! $user ) {
				return '';
			}
		}

		return $user->last_name;
	}

	/**
	 * Return the owner's phone #
	 *
	 * @param $contact_id
	 *
	 * @return mixed|string
	 */
	function replacement_owner_phone( $contact_id ) {
		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user || ! $user->phone ) {
			return $this->replacement_business_phone();
		}

		return $user->phone;
	}

	/**
	 * Return the owner's signature
	 *
	 * @param int $user_id
	 * @param int $contact_id
	 *
	 * @return mixed|string
	 */
	function replacement_owner_signature( $user_id = 0, $contact_id = 0 ) {

		$user_id = absint( $user_id );

		// If a specific user ID was passed
		if ( $user_id > 0 && $contact_id > 0 ) {
			$user = get_userdata( $user_id );
		} else {
			// Use contact's actual owner...
			$user = $this->get_current_contact()->get_ownerdata();
		}

		return $user->signature;
	}

	/**
	 * Return the owner's signature
	 *
	 * @param mixed $attr the attribute to fetch...
	 * @param int   $contact_id
	 *
	 * @return mixed|string
	 */
	function replacement_owner( $attr, $contact_id = 0 ) {

		$user = $this->get_current_contact()->get_ownerdata();

		if ( ! $user ) {
			return false;
		}

		return $user->$attr;
	}

	/**
	 * Return a confirmation link for the contact
	 * This just gets the Optin Page link for now.
	 *
	 * @param $redirect_to string
	 *
	 * @return string the optin link
	 */
	function replacement_confirmation_link( $redirect_to ) {

		$link_text = apply_filters( 'groundhogg/replacements/confirmation_text', Plugin::$instance->settings->get_option( 'confirmation_text', __( 'Confirm your email.', 'groundhogg' ) ) );

		$link_url = $this->replacement_confirmation_link_raw( $redirect_to );

		return sprintf( "<a href=\"%s\" target=\"_blank\">%s</a>", $link_url, $link_text );
	}

	/**
	 * Return a raw confirmation link for the contact that can be placed in a button.
	 * This just gets the Optin Page link for now.
	 *
	 * @param string $redirect_to
	 *
	 * @return string the optin link
	 */
	function replacement_confirmation_link_raw( $redirect_to = '' ) {

		$link_url = confirmation_url( $this->get_current_contact() );

		$redirect_to = is_string( $redirect_to ) ? esc_url_raw( no_and_amp( $redirect_to ) ) : false;

		if ( $redirect_to && is_string( $redirect_to ) ) {
			$link_url = add_query_arg( [
				'redirect_to' => urlencode( $redirect_to )
			], $link_url );
		}

		return $link_url;
	}

	/**
	 * Autologin the user
	 *
	 * @param $redirect_to
	 *
	 * @return string|void
	 */
	function replacement_auto_login_link( $redirect_to ) {

		$link_url    = managed_page_url( 'auto-login' );
		$redirect_to = is_string( $redirect_to ) ? esc_url_raw( no_and_amp( $redirect_to ) ) : false;

		if ( ! $this->get_current_contact()->get_userdata() ) {
			return $redirect_to;
		}

		$link_url = permissions_key_url( $link_url, $this->get_current_contact(), 'auto_login', DAY_IN_SECONDS, true );

		if ( $redirect_to && is_string( $redirect_to ) ) {
			$link_url = add_query_arg( [
				'redirect_to' => urlencode( $redirect_to )
			], $link_url );
		}

		return $link_url;
	}

	/**
	 * Merge in the unsubscribe link
	 *
	 * @return string|void
	 */
	function replacement_unsubscribe_link() {
		return unsubscribe_url( $this->get_current_contact() );
	}

	/**
	 * @return string
	 */
	function site_url() {
		return home_url();
	}


	/**
	 * Flag to modify the date replacement to output in the local time of the contact
	 *
	 * @var bool
	 */
	protected $date_display_in_contacts_local_time = false;

	/**
	 * Return a formatted date in local time.
	 *
	 * @param $time_string
	 *
	 * @return string
	 */
	function replacement_date( $time_string ) {

		$parts = preg_split( "/\||;/", $time_string );

		if ( count( $parts ) === 1 ) {
			$format = get_date_time_format();
			$when   = $parts[0];
		} else {
			$format = $parts[0];
			$when   = $parts[1];
		}

		try {
			$dateTime = new \DateTime( $when, wp_timezone() );
		} catch ( \Exception $e ) {

			// Swap the variables
			$temp   = $when;
			$when   = $format;
			$format = $temp;

			try {
				$dateTime = new \DateTime( $when, wp_timezone() );
			} catch ( \Exception $e ) {
				return '';
			}
		}

		if ( $this->date_display_in_contacts_local_time ) {
			$dateTime->setTimezone( $this->get_current_contact()->get_time_zone( false ) );
		}

		return $dateTime->format( $format );
	}

	/**
	 * Return a formatted date in contact's local time.
	 *
	 * @param $time_string
	 *
	 * @return string
	 */
	function replacement_local_date( $time_string ) {
		$this->date_display_in_contacts_local_time = true;
		$date                                      = $this->replacement_date( $time_string );
		$this->date_display_in_contacts_local_time = false;

		return $date;
	}


	/**
	 * Return the business name
	 *
	 * @return string
	 */
	function replacement_business_name() {
		return Plugin::$instance->settings->get_option( 'business_name' );
	}

	/**
	 * Return eh business phone #
	 *
	 * @return string
	 */
	function replacement_business_phone() {
		return Plugin::$instance->settings->get_option( 'phone' );
	}

	/**
	 * Return the business address
	 *
	 * @return array|string
	 */
	function replacement_business_address() {
		$address_keys = [
			'street_address_1',
			'street_address_2',
			'city',
			'region',
			'zip_or_postal',
			'country',
		];

		$address = [];

		foreach ( $address_keys as $key ) {

			$val = Plugin::$instance->settings->get_option( $key );
			if ( ! empty( $val ) ) {
				$address[ $key ] = $val;
			}
		}

		return implode( ', ', $address );
	}

	/**
	 * Get a file download link from a contact record.
	 *
	 * @param $key        string|int the key for the file
	 * @param $contact_id int
	 *
	 * @return string
	 */
	function replacement_files( $key = '', $contact_id = null ) {
		// Backwards compat
		if ( ! $contact_id ) {
			$contact_id = $key;
			$key        = false;
		}

		$files = $this->get_current_contact()->get_files();

		if ( empty( $files ) ) {
			return __( 'No files found.', 'groundhogg' );
		}

		$html = '';

		foreach ( $files as $i => $file ) {
			$html .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( permissions_key_url( $file['url'], $this->get_current_contact(), 'download_files' ) ), esc_html( $file['name'] ) );
		}

		return sprintf( '<ul>%s</ul>', $html );
	}

	/**
	 * Return a random quote from the movie groundhog day staring bill murray.
	 * Also the movie of which branding is based upon.
	 *
	 * @return mixed
	 */
	function get_random_groundhogday_quote() {
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

	/**
	 * Parse atts for replacement codes
	 *
	 * @param $text
	 *
	 * @return array
	 */
	function parse_atts( $text ) {
		$_atts = shortcode_parse_atts( $text );

		if ( empty( $_atts ) ) {
			return [];
		}

		$atts = [];

		foreach ( $_atts as $key => $value ) {

			// fix flags
			if ( is_numeric( $key ) ) {
				$atts[ $value ] = true;
				continue;
			}

			$atts[ $key ] = $value;
		}

		return $atts;
	}

	/**
	 * Output a part about a post
	 *
	 * @param string $args
	 * @param string $which
	 *
	 * @return string
	 */
	function single_post( $args, $which = '' ) {

		$props = $this->parse_atts( $args );

		$props = wp_parse_args( $props, [
			'id'         => '',
			'offset'     => 0,
			'post_type'  => 'post',
			'category'   => '',
			'tag'        => '',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_key'   => '',
			'meta_value' => '',
			'within'     => '',
		] );

		$post_query = [
			'post_status' => 'publish',
			'numberposts' => 1,
			'offset'      => $props['offset'],
			'post_type'   => $props['post_type'],
			'category'    => $props['category'],
			'tag'         => $props['tag'],
			'orderby'     => $props['orderby'],
			'order'       => $props['order'],
			'meta_key'    => $props['meta_key'],
			'meta_value'  => $props['meta_value'],
		];

		if ( isset_not_empty( $props, 'within' ) ) {
			$days = absint( $props['within'] );
			if ( $days ) {
				$post_query['date_query'] = [
					'after' => $days . ' days ago'
				];
			}
		}

		if ( isset_not_empty( $props, 'id' ) ) {
			/**
			 * Filter post query variables
			 *
			 * @param $query   array the query vars
			 * @param $contact Contact the current contact
			 */
			$post_query = apply_filters( "groundhogg/posts/query/{$props['id']}", $post_query, $this->current_contact );
		}

		$cache_key = md5( wp_json_encode( $post_query ) );

		// Check posts cache
		if ( isset_not_empty( self::$posts_cache, $cache_key ) ) {
			$posts = self::$posts_cache[ $cache_key ];
		} else {
			$posts                           = get_posts( $post_query );
			self::$posts_cache[ $cache_key ] = $posts;
		}

		if ( empty( $posts ) ) {
			return '';
		}

		if ( isset_not_empty( $props, 'id' ) ) {
			/**
			 * Filter posts
			 *
			 * @param $posts   \WP_Post[] the query vars
			 * @param $contact Contact the current contact
			 */
			$posts = apply_filters( "groundhogg/posts/{$props['id']}", $posts, $this->current_contact );
		}

		$post = array_shift( $posts );

		switch ( $which ) {
			default:
			case 'title':
				$content = html_entity_decode( get_the_title( $post ), ENT_QUOTES );
				break;
			case 'content':
				$content = get_the_content( null, false, $post );

				/**
				 * Filters the post content.
				 *
				 * @since 0.71
				 *
				 * @param string $content Content of the current post.
				 *
				 */
				$content = apply_filters( 'the_content', $content );
				$content = str_replace( ']]>', ']]&gt;', $content );
				break;
			case 'excerpt':
				$content = get_the_excerpt( $post );
				break;
			case 'thumbnail':
			case 'featured_image':

				if ( ! has_post_thumbnail( $post ) ) {
					return '';
				}

				return html()->e( 'a', [
					'href' => get_permalink( $post )
				], html()->e( 'img', [
					'class'  => 'featured-image',
					'src'    => get_the_post_thumbnail_url( $post ),
					'width'  => get_array_var( $props, 'width' ),
					'height' => get_array_var( $props, 'height' ),
				] ) );
			case 'featured_image_url':
			case 'thumbnail_url':

				if ( ! has_post_thumbnail( $post ) ) {
					return '';
				}

				$content = get_the_post_thumbnail_url( $post );
				break;
			case 'url':
				$content = get_permalink( $post );
				break;
		}

		return $content;
	}

	/**
	 * Output a title from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_title( $args, $contact_id = null ) {
		return $this->single_post( $args, 'title' );
	}

	/**
	 * Output the excerpt from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_excerpt( $args, $contact_id = null ) {
		return $this->single_post( $args, 'excerpt' );
	}

	/**
	 * Output a title from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_featured_image( $args, $contact_id = null ) {
		return $this->single_post( $args, 'featured_image' );
	}

	/**
	 * Output the content from a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_content( $args, $contact_id = null ) {
		return $this->single_post( $args, 'content' );
	}

	/**
	 * Output a url for a recent post
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function post_url( $args, $contact_id = null ) {
		return $this->single_post( $args, 'url' );
	}

	static $posts_cache = [];

	/**
	 * Display posts!
	 *
	 * @param $args
	 * @param $contact_id
	 *
	 * @return string
	 */
	function posts( $args, $contact_id = null ) {

		$props = $this->parse_atts( $args );

		$props = wp_parse_args( $props, [
			'id'         => '',
			'number'     => 5,
			'offset'     => 0,
			'layout'     => 'ul',
			'featured'   => false,
			'excerpt'    => false,
			'post_type'  => 'post',
			'category'   => '',
			'tag'        => '',
			'orderby'    => 'date',
			'order'      => 'DESC',
			'meta_key'   => '',
			'meta_value' => '',
			'within'     => '',
		] );

		$post_query = [
			'post_status' => 'publish',
			'numberposts' => $props['number'],
			'offset'      => $props['offset'],
			'post_type'   => $props['post_type'],
			'category'    => $props['category'],
			'tag'         => $props['tag'],
			'orderby'     => $props['orderby'],
			'order'       => $props['order'],
			'meta_key'    => $props['meta_key'],
			'meta_value'  => $props['meta_value'],
		];

		if ( isset_not_empty( $props, 'within' ) ) {
			$days = absint( $props['within'] );
			if ( $days ) {
				$post_query['date_query'] = [
					'after' => $days . ' days ago'
				];
			}
		}

		if ( isset_not_empty( $props, 'id' ) ) {
			/**
			 * Filter post query variables
			 *
			 * @param $query   array the query vars
			 * @param $contact Contact the current contact
			 */
			$post_query = apply_filters( "groundhogg/posts/query/{$props['id']}", $post_query, $this->current_contact );
		}

		$cache_key = md5( wp_json_encode( $post_query ) );

		// Check posts cache
		if ( isset_not_empty( self::$posts_cache, $cache_key ) ) {
			$posts = self::$posts_cache[ $cache_key ];
		} else {
			$posts                           = get_posts( $post_query );
			self::$posts_cache[ $cache_key ] = $posts;
		}

		if ( empty( $posts ) ) {
			return '';
		}

		if ( isset_not_empty( $props, 'id' ) ) {
			/**
			 * Filter posts
			 *
			 * @param $posts   \WP_Post[] the query vars
			 * @param $contact Contact the current contact
			 */
			$posts = apply_filters( "groundhogg/posts/{$props['id']}", $posts, $this->current_contact );
		}

		switch ( $props['layout'] ) {
			default:
			case 'ul':
			case 'ol':

				$content = html()->e( $props['layout'] ?? 'ul', [], array_map( function ( $post ) {
					return html()->e( 'li', [], html()->e( 'a', [ 'href' => get_permalink( $post ) ], get_the_title( $post ) ) );
				}, $posts ) );

				break;
			case 'grid':
			case 'cards':

				$rows = [];

				$render_post = function ( $post ) use ( $props ) {
					return html()->e( 'div', [
						'class' => [
							$props['layout'] === 'grid' ? 'post' : 'post-card',
							has_post_thumbnail( $post ) ? 'has-thumbnail' : ''
						]
					], [
						has_post_thumbnail( $post ) ? html()->e( 'div', [
							'class' => 'featured-image-wrap'
						], html()->e( 'a', [
							'href' => get_permalink( $post )
						], html()->e( 'img', [
							'class' => 'featured-image',
							'src'   => get_the_post_thumbnail_url( $post ),
						] ) ) ) : '',
						html()->e( 'div', [ 'class' => 'card-content' ], [
							html()->e( 'h2', [], html()->e( 'a', [
								'href' => get_permalink( $post )
							], get_the_title( $post ) ) ),
							$props['excerpt'] ? html()->e( 'p', [ 'class' => 'post-excerpt' ], get_the_excerpt( $post ) ) : ''
						] ),
					] );
				};

				if ( $props['featured'] ) {
					$post   = array_shift( $posts );
					$rows[] = $render_post( $post );
				}

				$rows[] = '<div class="email-columns">';


				while ( ! empty( $posts ) ):

					$post = array_shift( $posts );

					$render_column = function ( $content = '' ) {
						return html()->e( 'div', [
							'class' => 'email-columns-cell one-half'
						], [
							$content
						] );
					};

					$columns = [
						$render_column( $render_post( $post ) ),
						'<div class="email-columns-cell gap" style="width: 20px;"></div>',
					];

					$post = array_shift( $posts );

					if ( ! empty( $post ) ) {
						$columns[] = $render_column( $render_post( $post ) );
					} else {
						$columns[] = $render_column();
					}


					$rows[] = html()->e( 'div', [
						'class' => 'email-columns-row'
					], $columns );

				endwhile;

				$content = html()->e( 'div', [
					'class' => $props['layout']
				], $rows );

				break;

//			case 'list':
//				break;
			case 'h1':
			case 'h2':
			case 'h3':
			case 'h4':
			case 'h5':

				$tag = $props['layout'];

				$content = implode( '', array_map( function ( $post ) use ( $props, $tag ) {

					$_html = html()->e( $tag, [], html()->e( 'a', [ 'href' => get_permalink( $post ) ], get_the_title( $post ) ) );

					if ( $props['excerpt'] ) {
						$_html .= html()->e( 'p', [ 'class' => 'post-excerpt' ], get_the_excerpt( $post ) );
					}

					return $_html;
				}, $posts ) );


				break;
			case 'plain':

				$content = implode( "\n\n", array_map( function ( $post ) use ( $props ) {
					return sprintf( '%s 🔗 %s', html_entity_decode( get_the_title( $post ) ), get_permalink( $post ) );
				}, $posts ) );

				break;
		}

		return $content;
	}

	/**
	 * We don't want this to be serialized
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return false;
	}
}
