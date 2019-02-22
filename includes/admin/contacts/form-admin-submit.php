<?php
/**
 * Submit a form manually via the admin
 *
 * @package     Admin
 * @subpackage  Admin/Contacts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Contacts_Page::edit()
 * @since       File available since Release 1.1.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $_GET[ 'form' ] ) ){
    return;
}

$form_id = intval( $_GET[ 'form' ] );
$step = new WPGH_Step( $form_id );

?>
<!-- Title -->
<span class="hidden" id="new-title"><?php echo $step->title ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<!--/ Title -->
<div>
    <div style="max-width: 800px; margin: 100px auto">
        <?php echo do_shortcode( sprintf( '[gh_form id="%d"]', $form_id ) ); ?>
    </div>
</div>