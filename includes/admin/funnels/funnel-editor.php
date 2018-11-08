<?php
/**
 * Edit Funnel
 *
 * This page allows one to edit the funnels they have installed.
 *
 * @package     Admin
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$funnel_id = intval( $_GET['funnel'] );

//print_r($_POST);

do_action( 'wpgh_funnel_editor_before_everything', $funnel_id );

$funnel = WPGH()->funnels->get( $funnel_id );

?>
<span class="hidden" id="new-title"><?php echo $funnel->title; ?> &lsaquo; </span>
<script>
    document.title = jQuery( '#new-title' ).text() + document.title;
</script>
<form method="post">
    <?php wp_nonce_field(); ?>
    <?php $args = array(
        'type' => 'hidden',
        'name' => 'funnel',
        'id'    => 'funnel',
        'value' => $funnel_id
    ); echo WPGH()->html->input( $args ); ?>
    <div class="header-wrap">
        <div class="funnel-editor-header">
            <div class="title">
                <input class="title" placeholder="<?php echo __('Enter Funnel Name Here', 'groundhogg');?>" type="text" name="funnel_title" size="30" value="<?php echo $funnel->title; ?>" id="title" spellcheck="true" autocomplete="off">
            </div>
            <div id="reporting">
                <?php $args = array(
                    'name'      => 'date_range',
                    'id'        => 'date_range',
                    'options'   => array(
                        'last_24' => __( 'Last 24 Hours' ),
                        'last_7' => __( 'Last 7 Days' ),
                        'last_30' => __( 'Last 30 Days' ),
                        'custom' => __( 'Custom Range' ),
                    ),
                    'selected' => ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24',
                ); echo WPGH()->html->dropdown( $args ); ?>

                <?php $selected = ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24' ; ?>
                <input autocomplete="off" placeholder="<?php esc_attr_e('From:'); ?>" class="input <?php if ( $selected !== 'custom' ) echo 'hidden'; ?>" id="custom_date_range_start" name="custom_date_range_start" type="text" value="<?php if ( isset(  $_POST[ 'custom_date_range_start' ] ) ) echo $_POST['custom_date_range_start']; ?>">
                <input autocomplete="off" placeholder="<?php esc_attr_e('To:'); ?>" class="input <?php if ( $selected !== 'custom' ) echo 'hidden'; ?>" id="custom_date_range_end" name="custom_date_range_end" type="text" value="<?php if ( isset(  $_POST[ 'custom_date_range_end' ] ) ) echo $_POST['custom_date_range_end']; ?>">
                <?php submit_button( 'Refresh', 'secondary', 'change_reporting', false ); ?>
                <div class="onoffswitch">
                    <input type="checkbox" name="reporting_on" class="onoffswitch-checkbox" id="reporting-toggle" value="1" <?php if( isset( $_REQUEST[ 'change_reporting' ] ) ) echo 'checked'; ?> >
                    <label class="onoffswitch-label" for="reporting-toggle">
                        <span class="onoffswitch-inner"></span>
                        <span class="onoffswitch-switch"></span>
                    </label>
                </div>
            </div>
            <div class="status-options">
                <div id="add-contacts">
                    <a title="<?php _e( 'Add Contacts', 'groundhogg' ); ?>" href="#source=add-contact-modal&footer=false" class="button trigger-popup"><?php _e( 'Add Contacts', 'groundhogg' ) ?></a>
<!--                    <div class="hidden" id="add-contact-modal" style="">-->
                    <div class="hidden" id="add-contact-modal" style="display: none;">
                        <div>
                            <div class="add-contacts-response hidden"></div>
                            <table class="add-contact-form" style="width: 100%;">
                                <tbody>
                                <tr>
                                    <th>
                                        <?php _e( 'Select contacts to add into funnel:', 'groundhogg' ); ?>
                                    </th>

                                </tr>
                                <tr>
                                    <td>
                                        <?php

                                        echo WPGH()->html->tag_picker( array(
                                            'name'  => 'add_contacts_to_funnel_tag_picker[]',
                                            'id'    => 'add_contacts_to_funnel_tag_picker',
                                        ) );

                                        ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th>
                                        <?php _e( 'Select where to start:', 'groundhogg' ); ?>
                                    </th>
                                </tr>
                                <tr>
                                    <td>
                                        <?php

                                        $steps = WPGH()->steps->get_steps( array( 'funnel_id' => $funnel_id ) );
                                        $options = array();
                                        foreach ( $steps as $step ){
                                            $step = new WPGH_Step( $step->ID );
                                            if ($step->is_active() ){
                                                $options[ $step->ID ] = $step->title . ' (' . str_replace( '_', ' ', $step->type ) . ')';
                                            }
                                        }

                                        echo WPGH()->html->select2( array(
                                            'name'              => 'add_contacts_to_funnel_step_picker',
                                            'id'                => 'add_contacts_to_funnel_step_picker',
                                            'data'              => $options,
                                            'multiple'          => false,
                                        ) );

                                        ?>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                        <p class="submit">
                            <button type="button" id="add-contacts-button" class="button button-primary" style="float: left"><?php _e( 'Add Contacts' ); ?></button>
                            <span class="spinner" style="float: left"></span>
                        </p>
                    </div>
                </div>
                <div id="export">
                    <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'action', 'export' , $_SERVER['REQUEST_URI'] ), 'export' ) ); ?>" class="button button-secondary"><?php _e( 'Export Funnel', 'groundhogg'); ?></a>
                </div>
                <div id="status">
                    <div id="status-toggle-switch" class="onoffswitch" style="text-align: left">
                        <input type="checkbox" name="funnel_status" class="onoffswitch-checkbox" value="active" id="status-toggle" <?php if ( $funnel->status == 'active' ) echo 'checked'; ?>>
                        <label class="onoffswitch-label" for="status-toggle">
                            <span class="onoffswitch-inner"></span>
                            <span class="onoffswitch-switch"></span>
                        </label>
                    </div>
                </div>
                <div id="save">
                    <span class="spinner" style="float: left"></span>
                    <?php submit_button( 'Update', 'primary', 'update', false ) ?>
                </div>
            </div>
        </div>
    </div>
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2 main" style="clear: both">
            <!-- begin elements area -->
            <div id="postbox-container-1" class="postbox-container sidebar">
                                <!-- Begin Benckmark Icons-->
                <div id='benchmarks' class="postbox">
                    <h3 class="hndle"><?php echo __( 'Benchmarks', 'groundhogg' );?></h3>
                    <div class="elements-inner inside">
                        <?php do_action( 'wpgh_benchmark_icons_before' ); ?>
                        <table>
                            <tbody>
                            <?php $elements = WPGH()->elements->get_benchmarks();

                            $i = 0;

                            ?><tr><?php

                            foreach ( $elements as  $element => $args  ):

                                if ( ( $i % 3 ) == 0 ):
                                    ?></tr><tr><?php
                                endif;

                                ?><td><div id='<?php echo $element; ?>' title="<?php esc_attr_e( $args['desc'] ); ?>" class="wpgh-element ui-draggable"><div class="step-icon"><img width="60" src="<?php echo esc_url( $args['icon'] ); ?>"></div><p><?php echo $args['title']; ?></p></div></td><?php

                                $i++;

                            endforeach;

                            ?></tr><?php

                                ?>
                            </tbody>
                        </table>
                        <?php do_action( 'wpgh_benchmark_icons_after' ); ?>
                        <p>
                            <?php echo esc_html__( 'Benchmarks start and stop automation elements for a contact.','groundhogg' ); ?>
                        </p>
                    </div>
                </div>
                <!-- End Benchmark Icons-->
                <!-- Begin Action Icons-->
                <div id='actions' class="postbox">
                    <h2 class="hndle"><?php echo __( 'Actions', 'groundhogg' );?></h2>
                    <div class="inside">
                        <?php do_action( 'wpgh_action_icons_before' ); ?>
                        <table>
                            <tbody>
                            <?php $elements = WPGH()->elements->get_actions();

                            $i = 0;

                            ?><tr><?php

                            foreach ( $elements as  $element => $args  ):

                                if ( ( $i % 3 ) == 0 ):
                                ?></tr><tr><?php
                                endif;

                                ?><td><div id='<?php echo $element; ?>' title="<?php esc_attr_e( $args['desc'] ); ?>" class="wpgh-element ui-draggable"><div class="step-icon"><img width="60" src="<?php echo esc_url( $args['icon'] ); ?>"></div><p><?php echo $args['title']; ?></p></div></td><?php

                                $i++;

                            endforeach;

                            ?></tr><?php

                                ?>
                            </tbody>
                        </table>
                        <?php do_action( 'wpgh_action_icons_after' ); ?>

                        <p>
                            <?php esc_html_e( 'Actions are launched whenever a contact completes a benchmark.','groundhogg' ); ?>
                        </p>
                    </div>

                </div>
                <!-- End Action Icons-->
            </div>
            <!-- End elements area-->
            <!-- main funnel editing area -->
            <div id="notices">

            </div>
            <div  id="postbox-container-2" class="postbox-container funnel-editor">
                <div style="visibility: hidden" id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <?php do_action('wpgh_funnel_steps_before' ); ?>

                    <?php $steps = WPGH()->steps->get_steps( array( 'funnel_id' => $funnel_id ) );

                    if ( empty( $steps ) ): ?>
                        <div class="">
                            <?php esc_html_e( 'Drag in new steps to build the ultimate sales machine!' , 'groundhogg'); ?>
                        </div>
                    <?php else:

                        foreach ( $steps as $i => $step ):

                            $step = new WPGH_Step( $step->ID );

                            $step->html();
                            // echo $step;

                        endforeach;

                    endif; ?>
                    <?php do_action('wpgh_funnel_steps_after' ); ?>
                </div>
            </div>

            <script>
                jQuery(function($){$('#normal-sortables').css( 'visibility', 'visible' )})
            </script>
            <!-- end main funnel editing area -->
            <div style="clear: both;"></div>
        </div>
    </div>
</form>
<div>
    <div class="save-notification">
	    <?php _e( 'Auto Saved...', '' ); ?>
    </div>
</div>
