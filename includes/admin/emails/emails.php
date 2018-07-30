<?php
/**
 * View Emails
 *
 * Allow the user to view & edit the emails
 *
 * @package     wp-funnels
 * @subpackage  Includes/Emails
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['ID'] ) && is_numeric( $_GET['ID'] ) ) {

	include dirname( __FILE__ ) . '/email-editor.php';

} else {

	if ( ! class_exists( 'WPFN_Emails_Table' ) ){
		include dirname( __FILE__ ) . '/class-emails-table.php';
	}

	$emails_table = new WPFN_Emails_Table();

	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php echo __('Emails', 'wp-funnels');?></h1>
		<form method="post" >
			<!-- search form -->
			<p class="search-box">
				<label class="screen-reader-text" for="post-search-input">Search Emails:</label>
				<input type="search" id="post-search-input" name="s" value="">
				<input type="submit" id="search-submit" class="button" value="Search Contacts">
			</p>
			<?php $emails_table->prepare_items(); ?>
			<?php $emails_table->display(); ?>
		</form>
	</div>
	<?php

}