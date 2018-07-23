<?php
/**
 * Contact Record
 *
 * Allow the user to edit the contact details and contact fields
 *
 * @package     wp-funnels
 * @subpackage  Modules/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET['id'] ) || ! is_numeric( $_GET['id'] ) ) {
	wp_die( __( 'Contact ID not supplied. Please try again', 'wp-funnels' ), __( 'Error', 'wp-funnels' ) );
}

$contact_id = intval( $_GET['ID'] );
$contact = new WPFN_Contact( $contact_id );

?>

<div class="wrap">
	<h1><?php printf( '%s', $contact->getFullName() ); ?></h1>
	<?php do_action( 'wpfn_contact_record_before', $contact_id ); ?>
    <form method="post">

        <?php wp_nonce_field('update_contact', 'update_contact_nonce' ); ?>

        <?php do_action( 'wpfn_contact_record_form_before', $contact_id );?>

        <?php
	    if( isset( $_GET[ 'tab' ] ) ) {
		    $active_tab = $_GET[ 'tab' ];
	    } else {
	        $active_tab = 'general';
        }// end if
	    ?>

        <h2 class="nav-tab-wrapper">
            <a href="?ID=<?php echo $contact_id; ?>&tab=general" class="nav-tab <?php echo $active_tab == 'general' ? 'nav-tab-active' : ''; ?>">General</a>
            <a href="?ID=<?php echo $contact_id; ?>&tab=activity" class="nav-tab <?php echo $active_tab == 'activity' ? 'nav-tab-active' : ''; ?>">Activity</a>
            <a href="?ID=<?php echo $contact_id; ?>&tab=funnels" class="nav-tab <?php echo $active_tab == 'funnels' ? 'nav-tab-active' : ''; ?>">Funnels</a>
            <a href="?ID=<?php echo $contact_id; ?>&tab=tags" class="nav-tab <?php echo $active_tab == 'tags' ? 'nav-tab-active' : ''; ?>">Tags</a>
            <a href="?ID=<?php echo $contact_id; ?>&tab=orders" class="nav-tab <?php echo $active_tab == 'orders' ? 'nav-tab-active' : ''; ?>">Orders</a>
            <?php do_action('wpfn_contact_record_tabs_after', $contact_id ); ?>
        </h2>

        <?php switch ( $active_tab ): ?>

        <?php case 'general': ?>

            <h3><?php echo __( 'General Information', 'wp-funnels' ); ?></h3>

            <?php do_action( 'wpfn_contact_record_general_before', $contact_id ); ?>

            <table class="form-table">
                <tbody>
                    <tr>
                        <td><h3><?php echo __('Basic Information');?></h3></td>
                    </tr>
                    <tr>
                        <td><label for="first_name"><?php echo __( 'First Name', 'wp-funnels' )?></label></td>
                        <td><?php echo wpfn_contact_record_text_input_field( 'first_name', 'first_name', $contact->getFirst() );?></td>
                    </tr>
                    <tr>
                        <td><label for="last_name"><?php echo __( 'Last Name', 'wp-funnels' )?></label></td>
                        <td><?php echo wpfn_contact_record_text_input_field( 'last_name', 'last_name', $contact->getLast() );?></td>
                    </tr>
                    <tr>
                        <td><label for="email"><?php echo __( 'First Name', 'wp-funnels' )?></label></td>
                        <td>
                            <?php echo wpfn_contact_record_text_input_field( 'email', 'email', $contact->getEmail() );?>
                            <?php echo __('Email Status: ', 'wp-funnels') . wpfn_get_optin_status_text( $contact->getOptInStatus() ); ?>
                        </td>
                    </tr>
                    <tr>
                        <td><label for="primary_phone"><?php echo __( 'Primary Phone', 'wp-funnels' )?></label></td>
                        <td><?php echo wpfn_contact_record_text_input_field( 'primary_phone', 'primary_phone', $contact->getPhone() );?></td>
                    </tr>
                    <tr>
                        <td><label for="phone_extension"><?php echo __( 'Phone Extension', 'wp-funnels' )?></label></td>
                        <td><?php echo wpfn_contact_record_text_input_field( 'phone_extension', 'phone_extension', $contact->getPhoneExtension() );?></td>
                    </tr>
                </tbody>
            </table>

            <?php do_action( 'wpfn_contact_record_general_after', $contact_id ); ?>

            <?php submit_button( 'Save Changes', 'primary' ); ?>

        <?php break; ?>

        <?php case 'activity': ?>

            <h3><?php echo __( 'Recent Activity', 'wp-funnels' ); ?></h3>

            <table class="wp-list-table widefat striped contact-activity">
                <thead>
                    <tr>
                        <th><?php echo __('Date', 'wp-funnels');?></th>
                        <th><?php echo __('Entry', 'wp-funnels');?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php $entries = $contact->getParsedActivity(); ?>
                    <?php foreach ( $entries as $entry ): ?>
                    <tr>
                        <td><?php echo $entry[0];?></td>
                        <td><?php echo $entry[1];?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if ( empty( $entries ) ):?>
                    <tr>
                        <td colspan="2">
	                        <?php echo __( 'No Recent Activity Recorded...', 'wp-funnels' ); ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>

        <?php break; ?>

        <?php case 'funnels': ?>
        <?php break; ?>

        <?php case 'tags': ?>
        <?php break; ?>

        <?php case 'orders': ?>
        <?php break; ?>

        <?php default: ?>

            <?php do_action( 'wpfn_contact_record_tab_' . $active_tab ); ?>

        <?php break; ?>

        <?php endswitch; ?>

    </form>
</div>
