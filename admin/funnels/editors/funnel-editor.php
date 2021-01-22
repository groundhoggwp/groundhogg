<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use function Groundhogg\is_option_enabled;
use function Groundhogg\key_to_words;
use Groundhogg\Plugin;
use function Groundhogg\get_request_var;
use function Groundhogg\html;

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

$funnel_id = absint( get_request_var( 'funnel' ) );

$funnel = new Funnel( $funnel_id );

$edit_url = add_query_arg( [
    'page' => 'gh_funnels',
    'action' => 'edit',
    'funnel' => $funnel_id,
    'version' => '2'
], admin_url( 'admin.php' ) );

if ( ! is_option_enabled( 'gh_use_builder_version_2' ) ){
    Plugin::$instance->notices->add( 'edit-v2', sprintf( '%s <a href="%s">%s</a>', __( 'The new and improved Funnel Builder has Arrived!' ), $edit_url, __( 'Launch V2 Now!' ) ) );
}


?>
<form method="post" id="funnel-form">
    <?php wp_nonce_field(); ?>
    <?php $args = array(
        'type' => 'hidden',
        'name' => 'funnel',
        'id'    => 'funnel',
        'value' => $funnel_id
    ); echo Plugin::$instance->utils->html->input( $args );

    echo html()->input( [ 'type' => 'hidden', 'value' => 1, 'name' => 'version' ] );

    ?>
    <div class="header-wrap">
        <div class="funnel-editor-header">
            <div class="title alignleft">
                <input class="title" placeholder="<?php echo __('Enter Funnel Name Here', 'groundhogg');?>" type="text" name="funnel_title" size="30" value="<?php esc_attr_e( $funnel->get_title() ); ?>" id="title" spellcheck="true" autocomplete="off">
            </div>
            <div id="reporting" class="alignleft">
                <?php $args = array(
                    'name'      => 'range',
                    'id'        => 'date_range',
                    'class'     => 'alignleft',
                    'options'   => Plugin::$instance->reporting->get_reporting_ranges(),
                    'selected' => get_request_var( 'range', 'this_week' ), //todo
                ); echo Plugin::$instance->utils->html->dropdown( $args );

                $class = get_request_var( 'date_range' ) === 'custom' ? '' : 'hidden'; //todo

                ?><div class="custom-range <?php echo $class ?> alignleft"><?php
                    echo Plugin::$instance->utils->html->date_picker(array(
                        'name'  => 'custom_date_range_start',
                        'id'    => 'custom_date_range_start',
                        'class' => 'input',
                        'value' => get_request_var( 'custom_date_range_start' ),
                        'attributes' => '',
                        'placeholder' => 'YYY-MM-DD',
                        'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                        'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                        'format' => 'yy-mm-dd'
                    ));
                    echo Plugin::$instance->utils->html->date_picker(array(
                        'name'  => 'custom_date_range_end',
                        'id'    => 'custom_date_range_end',
                        'class' => 'input',
                        'value' => get_request_var( 'custom_date_range_end' ), //todo
                        'attributes' => '',
                        'placeholder' => 'YYY-MM-DD',
                        'min-date' => date( 'Y-m-d', strtotime( '-100 years' ) ),
                        'max-date' => date( 'Y-m-d', strtotime( '+100 years' ) ),
                        'format' => 'yy-mm-dd'
                    )); ?>
                </div>
                <script>
                    jQuery(function($){$('#date_range').change(function(){
                        if($(this).val() === 'custom'){
                            $('.custom-range').removeClass('hidden');
                        } else {
                            $('.custom-range').addClass('hidden');
                        }})});
                </script>
                <?php submit_button( _x( 'Refresh', 'action', 'groundhogg' ), 'secondary', 'change_reporting', false ); ?>

                <?php echo Plugin::$instance->utils->html->toggle( [
                    'name'          => 'reporting_on',
                    'id'            => 'reporting-toggle',
                    'value'         => 'ready',
                    'checked'       => isset( $_REQUEST[ 'change_reporting' ] ),
                    'on'            => 'Reporting',
                    'off'           => 'Editing',
                ]); ?>
            </div>
            <div class="status-options">
                <div id="full-screen">
                    <a id="enter-full-screen" style="text-decoration: none; display: inline-block" href="#"><span style="padding: 5px;" title="<?php esc_attr_e( 'Distraction Free Mode', 'groundhogg' ) ?>" class="dashicons dashicons-editor-expand"></span></a>
                </div>
                <div>
                    <?php echo Plugin::$instance->utils->html->modal_link( array(
                        'title'     => __( 'Replacements', 'groundhogg' ),
                        'text'      => '<span style="padding: 5px;" class="dashicons dashicons-admin-users"></span>',
                        'footer_button_text' => __( 'Insert' ),
                        'id'        => 'replacements',
                        'class'     => 'no-padding replacements replacements-button',
                        'source'    => 'footer-replacement-codes',
                        'height'    => 900,
                        'width'     => 700,
                    ) ); ?>
                </div>
                <div id="add-contacts" class="<?php if ( ! $funnel->is_active() ) echo 'hidden'; ?>">
                    <?php echo  html()->modal_link( [
                        'title'     => __( 'Add Contacts', 'groundhogg' ),
                        'text'      => '<span style="padding: 5px;" class="dashicons dashicons-plus-alt"></span>',
                        'footer_button_text' => __( 'Close' ),
                        'id'        => '',
                        'class'     => 'add-contacts-link',
                        'source'    => 'add-contact-modal',
                        'height'    => 500,
                        'width'     => 500,
                        'footer'    => 'ture',
                    ] ); ?>
                </div>
                <div id="export">
                    <a id="copy-share-link" style="text-decoration: none; display: inline-block" href="#"><span style="padding: 5px;" title="<?php esc_attr_e( 'Copy share link', 'groundhogg' ) ?>" class="dashicons dashicons-share"></span></a>
                    <input id="share-link" type="hidden" value="<?php echo esc_attr( $funnel->export_url() ); ?>">
                    <a style="text-decoration: none; display: inline-block" href="<?php echo esc_url( $funnel->export_url() ); ?>"><span title="<?php esc_attr_e( 'Export', 'groundhogg' ) ?>"  style="padding: 5px;" class="dashicons dashicons-download"></span></a>
                </div>
                <div id="status">
                    <?php echo Plugin::$instance->utils->html->toggle( [
                        'name'          => 'funnel_status',
                        'id'            => 'status-toggle',
                        'value'         => 'active',
                        'checked'       => $funnel->is_active(),
                        'on'            => 'Active',
                        'off'           => 'Inactive',
                    ]); ?>
                </div>
                <div id="save">
                    <span class="spinner" style="float: left"></span>
                    <?php submit_button( __( 'Update', 'groundhogg' ), 'primary', 'update', false ) ?>
                </div>
            </div>
        </div>
    </div>
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2 main" style="clear: both">
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id='benchmarks' class="postbox">
                    <button title="Help" type="button" class="handlediv help">
		                <?php echo html()->help_icon( 'https://docs.groundhogg.io/docs/builder/benchmarks/' ); ?>
                    </button>
                    <h2 class="hndle"><?php echo __( 'Benchmarks', 'groundhogg' );?></h2>
                    <div class="elements-inner inside">
                        <table>
                            <tbody>
                            <tr><?php
                                $benchmarks = Plugin::$instance->step_manager->get_benchmarks();
                                $i = 0;
                                foreach ( $benchmarks as $benchmark ):
                                    if ( ( $i % 3 ) == 0 ):
                                        ?></tr><tr><?php
                                    endif;
                                    ?><td><div id='<?php echo $benchmark->get_type(); ?>' title="<?php esc_attr_e( $benchmark->get_description() ); ?>" class="wpgh-element ui-draggable"><div class="step-icon"><img width="60" src="<?php echo esc_url( $benchmark->get_icon() ); ?>"></div><p><?php echo $benchmark->get_name()  ?></p></div></td><?php
                                    $i++;

                                endforeach;

                            ?></tr>
                            </tbody>
                        </table>
                        <p>
                            <?php echo esc_html__( 'Benchmarks start and stop automation steps for a contact.','groundhogg' ); ?>
                        </p>
                    </div>
                </div>
                <!-- End Benchmark Icons-->
                <!-- Begin Action Icons-->
                <div id='actions' class="postbox">
                    <button title="Help" type="button" class="handlediv help">
		                <?php echo html()->help_icon( 'https://docs.groundhogg.io/docs/builder/actions/' ); ?>
                    </button>
                    <h2 class="hndle"><?php echo __( 'Actions', 'groundhogg' );?></h2>
                    <div class="inside">
                        <table>
                            <tbody>
                            <tr><?php
                                $actions = Plugin::$instance->step_manager->get_actions();
                                $i = 0;
                                foreach ( $actions as $action ):
                                if ( ( $i % 3 ) == 0 ):
                                ?></tr><tr><?php
                                endif;
                                ?><td><div id='<?php echo $action->get_type(); ?>' title="<?php esc_attr_e( $action->get_description() ); ?>" class="wpgh-element ui-draggable"><div class="step-icon"><img width="60" src="<?php echo esc_url( $action->get_icon() ); ?>"></div><p><?php echo $action->get_name()  ?></p></div></td><?php
                                $i++;

                                endforeach;

                                ?></tr>
                            </tbody>
                        </table>
                        <p>
                            <?php esc_html_e( 'Actions are launched whenever a contact completes a benchmark.','groundhogg' ); ?>
                        </p>
                    </div>

                </div>
                <!-- End Action Icons-->
            </div>
            <?php Plugin::$instance->notices->print_notices(); ?>
            <div style="width: 100%">
                <?php include_once __DIR__ . '/reporting.php'; ?>
            </div>
            <div class="funnel-saving hidden"></div>
            <div  id="postbox-container-2" class="postbox-container funnel-editor">
                <div style="visibility: hidden" id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <?php $steps = $funnel->get_steps();
                        foreach ( $steps as $i => $step ):
                            $step->html();
                            // echo $step;
                        endforeach;?>
                </div>
            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</form>
<div class="hidden" id="add-contact-modal" style="display: none;">
    <form method="post">
        <?php wp_nonce_field(); ?>
        <?php wp_nonce_field( 'add_contacts_to_funnel', 'add_contacts_nonce' ); ?>
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

                        echo Plugin::$instance->utils->html->tag_picker( array(
                            'name'  => 'include_tags[]',
                            'id'    => 'include_tags',
                        ) );

                        ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e( 'Exclude these contacts:', 'groundhogg' ); ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <?php

                        echo Plugin::$instance->utils->html->tag_picker( array(
                            'name'  => 'exclude_tags[]',
                            'id'    => 'exclude_tags',
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

                        $options = [];

                        $steps = $funnel->get_steps();
                        foreach ( $steps as $step ){
                            $options[ $step->get_id() ] = sprintf( "%d. %s (%s)", $step->get_order(), $step->get_title(), key_to_words( $step->get_type() ) );
                        }

                        echo Plugin::$instance->utils->html->select2( array(
                            'name'              => 'which_step',
                            'id'                => 'which_step',
                            'data'              => $options,
                            'multiple'          => false,
                        ) );

                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php submit_button( __( 'Add Contacts to Funnel', 'groundhogg' ) ); ?>
        </div>
    </form>
</div>
