<?php
/**
 * View Funnels
 *
 * Allow the user to view & edit the funnels
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( isset( $_GET['ID'] ) && is_numeric( $_GET['ID'] ) ) {

    include dirname( __FILE__ ) . '/funnel-builder.php';

} else {

    if ( ! class_exists( 'WPFN_Funnel_Builder' ) ){
        include dirname( __FILE__ ) . '/class-funnels-table.php';
    }

    $funnels_table = new WPFN_Funnels_Table();

    ?>
    <div class="wrap">
        <h1 class="wp-heading-inline"><?php echo __('Funnels', 'wp-funnels');?></h1>
        <form method="post" >
            <!-- search form -->
            <p class="search-box">
                <label class="screen-reader-text" for="post-search-input">Search Funnels:</label>
                <input type="search" id="post-search-input" name="s" value="">
                <input type="submit" id="search-submit" class="button" value="Search Funnels">
            </p>
            <?php $funnels_table->prepare_items(); ?>
            <?php $funnels_table->display(); ?>
        </form>
    </div>
    <?php
}