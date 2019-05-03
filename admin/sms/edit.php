<?php
namespace Groundhogg\Admin\SMS;

use Groundhogg\Plugin;


/**
 * Edit An SMS message
 *
 * @package     Admin
 * @subpackage  Admin/SMS
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 1.2
 */


if ( ! defined( 'ABSPATH' ) ) exit;

$id = intval( $_GET[ 'sms' ] );

$sms = Plugin::$instance->dbs->get_db('sms')->get($id);

?>
<form name="edittag" id="edittag" method="post" action="">
    <?php wp_nonce_field(); ?>
    <table class="form-table">
        <tbody><tr class="form-field term-name-wrap">
            <th scope="row"><label for="sms-title"><?php _e( 'Title', 'groundhogg' ) ?></label></th>
            <td><input name="title" id="sms-title" type="text" value="<?php echo $sms->title; ?>" maxlength="100" autocomplete="off">
                <p class="description"><?php _e( 'Name it something simple so you do not forget it.', 'groundhogg' ); ?></p>
            </td>
        </tr>
        <tr class="form-field term-message-wrap">
            <th scope="row"><label for="sms-message"><?php _e( 'Target URL', 'groundhogg' ) ?></label></th>
            <td><textarea name="message" id="sms-message" rows="5"><?php echo $sms->message; ?></textarea>
                <p class="description">
		            <?php Plugin::$instance->replacements->show_replacements_button(); ?>
                    <?php _e( 'Use any valid replacement codes in your text message.', 'groundhogg' ); ?>
                </p>
            </td>
        </tr>
        </tbody>
    </table>
    <div class="edit-sms-actions">
        <?php submit_button( __( 'Update' ), 'primary', 'update', false ); ?>
        <?php submit_button( __( 'Update & Test' ), 'secondary', 'save_and_test', false ); ?>
        <span id="delete-link"><a class="delete" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=gh_sms&action=delete&sms='. $id ), 'delete'  ) ?>"><?php _e( 'Delete' ); ?></a></span>
    </div>
</form>