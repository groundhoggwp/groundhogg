<?php
/**
 * Contact Record
 *
 * Allow the user to edit the contact details and contact fields
 *
 * @package     wp-funnels
 * @subpackage  Includes/Contacts
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['ID'] ) && is_numeric( $_GET['ID'] ) ) {

	include dirname(__FILE__) . '/contact-record.php';

} else {

	if ( ! class_exists( 'WPFN_Contacts_Table' ) ){
		include dirname( __FILE__ ) . '/class-contacts-table.php';
	}

	$contacts_table = new WPFN_Contacts_Table();

	?>
	<div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Contacts', 'wp-funnels');?></h1>
        <form method="post" >
			<!-- search form -->
            <?php $contacts_table->views(); ?>
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input">Search Contacts:</label>
				<input type="search" id="post-search-input" name="s" value="">
				<input type="submit" id="search-submit" class="button" value="Search Contacts">
			</p>
			<?php $contacts_table->prepare_items(); ?>
			<?php $contacts_table->display(); ?>
		</form>
	</div>
<?php

}