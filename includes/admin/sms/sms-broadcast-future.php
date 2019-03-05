<?php
/**
 * This is the page which allows the user to schedule a broadcast for SMS.
 *
 * Broadcasts are a closed process and thus have very limited hooks to modify the functionality.
 * If you are looking to extend the broadcast experience you are better off designing your own page to schedule broadcasts.
 *
 * @package     Admin
 * @subpackage  Admin/SMS/Broadcasts
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @see         WPGH_Broadcasts_Page::add()
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui' );

if ( ! class_exists( 'WPGH_SMS_Table' ) ){
			include dirname(__FILE__) . '/class-wpgh-sms-table.php';
		}

		$sms_table = new WPGH_SMS_Table(); ?>
        <div id="col-container" class="wp-clearfix">
            <div id="col-left">
                <div class="col-wrap">
                    <div class="form-wrap">
                        <h2><?php _e( 'Schedule New SMS Broadcast', 'groundhogg' ) ?></h2>
                        <form id="addsms" method="post" action="">
                            <?php wp_nonce_field(); ?>
                            <div class="form-field">
                                <label for="sms_id"><?php _e( 'Select an SMS to send.', 'groundhogg' ) ?></label>
                                <?php $args = array();
                                    $args[ 'id' ] = 'sms_id';
                                    $args[ 'name' ] = 'sms_id';
                                    $args[ 'data' ] = WPGH()->sms->get_sms_select();
                                    $args[ 'required' ] = true;
                                    echo WPGH()->html->select2( $args ); ?>
                            </div>
                            <div class="form-field">
                                <label for="description"><?php _e( 'Send To:', 'groundhogg' ); ?></label>
                                <?php $tag_args = array();
                                    $tag_args[ 'id' ] = 'tags';
                                    $tag_args[ 'name' ] = 'tags[]';
                                    $tag_args[ 'required' ] = true;

                                    echo WPGH()->html->tag_picker( $tag_args ); ?>
                                    <p class="description"><?php _e( 'This sms broadcast will be sent to contacts with these tags.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field">
                                <label for="description"><?php _e( 'Exclude These Contacts:', 'groundhogg' ); ?></label>
                                <?php $tag_args = array();
                                $tag_args[ 'id' ] = 'exclude_tags';
                                $tag_args[ 'name' ] = 'exclude_tags[]';
                                $tag_args[ 'required' ] = false;

                                echo WPGH()->html->tag_picker( $tag_args ); ?>
                                <p class="description"><?php _e( 'These contacts will be excluded.', 'groundhogg' ); ?></p>
                            </div>
                            <div class="form-field">

                            <label for="date"><?php _e( 'Send On:', 'groundhogg' ); ?></label>
                                <div style="display: inline-block; width: 100px;">
                                    <?php echo WPGH()->html->date_picker( array( 'name' => 'date', 'id' => 'date', 'class' => 'input' ) ); ?>
                                </div>
                                <input type="time" id="time" name="time" value="09:0" autocomplete="off" required><?php _e( '&nbsp;or&nbsp;', 'groundhogg' ); ?>
                                <style>
                                    label.gh-checkbox-label{ display: inline-block;}
                                </style>
                                <?php echo WPGH()->html->checkbox( array(
                                    'label'         => _x( 'Send Now', 'action', 'groundhogg' ),
                                    'name'          => 'send_now',
                                    'id'            => 'send_now',
                                    'class'         => '',
                                    'value'         => '1',
                                    'checked'       => false,
                                    'title'         => __( 'Send Now', 'groundhogg' ),
                                    'attributes'    => '',
                                    'required'      => false,) ); ?>
                                <p class="description"><?php _e( 'The day the SMS broadcast will be sent.', 'groundhogg' ); ?></p>
                            </div>
                            <?php submit_button( _x( 'Schedule SMS Broadcast', 'action', 'groundhogg' ), 'primary', 'save', false ); ?>
                        </form>
                    </div>
                </div>
            </div>
            <div id="col-right">
                <div class="col-wrap">
                    <form id="posts-filter" method="post">
                        <?php $sms_table->prepare_items(); ?>
                        <?php $sms_table->display(); ?>
                    </form>
                </div>
            </div>
        </div>
