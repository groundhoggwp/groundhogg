<?php
/**
 * This is the page which allows the user to schedule a broadcast.
 *
 * Broadcasts are a closed process and thus have very limited hooks to modify the functionality.
 * If you are looking to extend the broadcast experience you are better off designing your own page to schedule broadcasts.
 *
 * @package     Admin
 * @subpackage  Admin/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Broadcasts_Page::add()
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui' );

?>
<form name="edittag" id="edittag" method="post" action="">
    <?php wp_nonce_field(); ?>
    <table class="form-table">
        <tbody><tr class="form-field term-email-wrap">
            <th scope="row"><label for="email_id"><?php _e( 'Select an email to send.' ) ?></label></th>
            <td><?php $args = array();
                $args[ 'id' ] = 'email_id';
                $args[ 'name' ] = 'email_id';
                $args[ 'required' ] = true;

                echo WPGH()->html->dropdown_emails( $args ); ?>
                <p class="description"><?php _e( 'The Broadcast tool uses your global emails.', 'groundhogg' ) ?></p>
            </td>
        </tr>
        <tr class="form-field term-tags-wrap">
            <th scope="row"><label for="description"><?php _e( 'Send To:' ); ?></label></th>
            <td><?php $tag_args = array();
                $tag_args[ 'id' ] = 'tags';
                $tag_args[ 'name' ] = 'tags[]';
                $tag_args[ 'required' ] = true;

                echo WPGH()->html->tag_picker( $tag_args ); ?>
                <p class="description"><?php _e( 'This broadcast will be sent to contacts with these tags.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr class="form-field term-date-wrap">
            <th scope="row">
                <label for="date"><?php _e( 'Send On:' ); ?></label>
            </th>
            <td>
                <input style="height:29px;width: 100px" class="input" placeholder="Y/m/d" type="text" id="date" name="date" value="" autocomplete="off" required><input type="time" id="time" name="time" value="09:00" autocomplete="off" required>
                    <script>jQuery(function($){$('#date').datepicker({
                    changeMonth: true,
                    changeYear: true,
                    minDate:0,
                    dateFormat:'yy/m/d'
                })});</script>
                <p class="description"><?php _e( 'The day the broadcast will be sent.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="edit-tag-actions">
        <?php submit_button( __( 'Schedule Broadcast' ), 'primary', 'update', false ); ?>
    </div>
</form>
