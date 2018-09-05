<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-08-17
 * Time: 3:11 PM
 */

if ( ! defined( 'ABSPATH' ) ) exit;

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );

?>
<form name="edittag" id="edittag" method="post" action="">
    <?php wp_nonce_field(); ?>
    <table class="form-table">
        <tbody><tr class="form-field term-email-wrap">
            <th scope="row"><label for="email_id"><?php _e( 'Select an email to send.' ) ?></label></th>
            <td><?php $dropdown_args = array();
                $dropdown_args[ 'id' ] = 'email_id';
                $dropdown_args[ 'name' ] = 'email_id';
                $dropdown_args[ 'class' ] = 'hidden';
                $dropdown_args[ 'required' ] = true;

                wpgh_dropdown_emails( $dropdown_args ); ?>
                <p class="description"><?php _e( 'THe Broadcast tool uses your global emails.', 'groundhogg' ) ?></p>
            </td>
        </tr>
        <tr class="form-field term-tags-wrap">
            <th scope="row"><label for="description"><?php _e( 'Send To:' ); ?></label></th>
            <td><?php $tag_args = array();
                $tag_args[ 'id' ] = 'tags';
                $tag_args[ 'name' ] = 'tags[]';
                $tag_args[ 'width' ] = '100%';
                $tag_args[ 'class' ] = 'hidden';
                $tag_args[ 'required' ] = true;
                ?>
                <?php wpgh_dropdown_tags( $tag_args ); ?>
                <p class="description"><?php _e( 'This broadcast will be sent to contacts with these tags.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr class="form-field term-date-wrap">
            <th scope="row">
                <label for="date"><?php _e( 'Send On:' ); ?></label>
            </th>
            <td>
                <input style="height:29px;width: 100px" class="input" placeholder="Y/m/d" type="text" id="date" name="date" value="" required><input type="time" id="time" name="time" value="09:15" required>
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
