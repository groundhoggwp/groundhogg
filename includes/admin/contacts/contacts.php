<?php
/**
 * Created by PhpStorm.
 * User: Adrian
 * Date: 2018-07-23
 * Time: 9:32 AM
 */

if ( isset( $_GET['id'] ) && is_numeric( $_GET['id'] ) ) {

	include dirname( __FILE__ ) . '/contact-record.php';

} else {

	if ( ! class_exists( 'WPFN_Contacts_Table' ) ){
		include dirname( __FILE__ ) . '/class-contacts-table.php';
	}

	$contacts_table = new WPFN_Contacts_Table();

	?>
	<div class="wrap">
		<form method="post" >
			<!-- search form -->
			<h1 class="wp-heading-inline">Contacts</h1>
			<p><?php wp_nonce_field('', 'formlift_export_nonce' )?><input type="submit" class="button button-primary" name="export_contacts" value="Export Contacts"></p>
		</form>
		<form method="post">
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input">Search Contacts:</label>
				<input type="search" id="post-search-input" name="s" value="">
				<input type="submit" id="search-submit" class="button" value="Search Contacts">
			</p>
			<?php $contacts_table->prepare_items();
			$contacts_table->display(); ?>
		</form>
	</div>
<?php

}