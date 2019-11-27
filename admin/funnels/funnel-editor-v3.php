<?php

namespace Groundhogg\Admin\Funnels;

use Groundhogg\Funnel;
use function Groundhogg\admin_page_url;
use function Groundhogg\dashicon;
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

if (!defined('ABSPATH')) exit;

$funnel_id = absint( get_request_var( 'funnel' ) );

$funnel = new Funnel($funnel_id);

?>
<form method="post" id="funnel-form">
    <?php wp_nonce_field(); ?>
    <?php $args = array(
        'type' => 'hidden',
        'name' => 'funnel',
        'id' => 'funnel',
        'value' => $funnel_id
    );
    echo Plugin::$instance->utils->html->input($args); ?>
    <div class="header-wrap">
        <div class="funnel-editor-header">
            <div class="title alignleft">
                <input class="title" placeholder="<?php echo __('Enter Funnel Name Here', 'groundhogg'); ?>" type="text"
                       name="funnel_title" size="30" value="<?php esc_attr_e($funnel->get_title()); ?>" id="title"
                       spellcheck="true" autocomplete="off">
            </div>
            <div id="reporting" class="alignleft">
                <?php $args = array(
                    'name' => 'range',
                    'id' => 'date_range',
                    'class' => 'alignleft',
                    'options' => Plugin::$instance->reporting->get_reporting_ranges(),
                    'selected' => get_request_var('range', 'this_week'), //todo
                );
                echo Plugin::$instance->utils->html->dropdown($args);

                $class = get_request_var('date_range') === 'custom' ? '' : 'hidden'; //todo

                ?>
                <div class="custom-range <?php echo $class ?> alignleft"><?php
                    echo Plugin::$instance->utils->html->date_picker(array(
                        'name' => 'custom_date_range_start',
                        'id' => 'custom_date_range_start',
                        'class' => 'input',
                        'value' => get_request_var('custom_date_range_start'),
                        'attributes' => '',
                        'placeholder' => 'YYY-MM-DD',
                        'min-date' => date('Y-m-d', strtotime('-100 years')),
                        'max-date' => date('Y-m-d', strtotime('+100 years')),
                        'format' => 'yy-mm-dd'
                    ));
                    echo Plugin::$instance->utils->html->date_picker(array(
                        'name' => 'custom_date_range_end',
                        'id' => 'custom_date_range_end',
                        'class' => 'input',
                        'value' => get_request_var('custom_date_range_end'), //todo
                        'attributes' => '',
                        'placeholder' => 'YYY-MM-DD',
                        'min-date' => date('Y-m-d', strtotime('-100 years')),
                        'max-date' => date('Y-m-d', strtotime('+100 years')),
                        'format' => 'yy-mm-dd'
                    )); ?>
                </div>
                <script>
                    jQuery(function ($) {
                        $('#date_range').change(function () {
                            if ($(this).val() === 'custom') {
                                $('.custom-range').removeClass('hidden');
                            } else {
                                $('.custom-range').addClass('hidden');
                            }
                        })
                    });
                </script>
                <?php submit_button(_x('Refresh', 'action', 'groundhogg'), 'secondary', 'change_reporting', false); ?>

                <?php echo Plugin::$instance->utils->html->toggle([
                    'name' => 'reporting_on',
                    'id' => 'reporting-toggle',
                    'value' => 'ready',
                    'checked' => isset($_REQUEST['change_reporting']),
                    'on' => 'Reporting',
                    'off' => 'Editing',
                ]); ?>
            </div>
            <div class="status-options">
                <div>
                    <?php echo Plugin::$instance->utils->html->modal_link(array(
                        'title' => __('Replacements', 'groundhogg'),
                        'text' => '<span style="padding: 5px;" class="dashicons dashicons-admin-users"></span>',
                        'footer_button_text' => __('Insert'),
                        'id' => 'replacements',
                        'class' => 'no-padding replacements replacements-button',
                        'source' => 'footer-replacement-codes',
                        'height' => 900,
                        'width' => 700,
                    )); ?>
                </div>
                <div id="add-contacts" class="<?php if (!$funnel->is_active()) echo 'hidden'; ?>">
                    <?php echo html()->modal_link([
                        'title' => __('Add Contacts', 'groundhogg'),
                        'text' => '<span style="padding: 5px;" class="dashicons dashicons-plus-alt"></span>',
                        'footer_button_text' => __('Close'),
                        'id' => '',
                        'class' => 'add-contacts-link',
                        'source' => 'add-contact-modal',
                        'height' => 500,
                        'width' => 600,
                        'footer' => 'true',
                    ]); ?>
                </div>
                <div id="export">
                    <a id="copy-share-link" style="text-decoration: none; display: inline-block" href="#"><span
                                style="padding: 5px;" title="<?php esc_attr_e('Copy share link', 'groundhogg') ?>"
                                class="dashicons dashicons-share"></span></a>
                    <input id="share-link" type="hidden" value="<?php echo esc_attr($funnel->export_url()); ?>">
                    <a style="text-decoration: none; display: inline-block"
                       href="<?php echo esc_url($funnel->export_url()); ?>"><span
                                title="<?php esc_attr_e('Export', 'groundhogg') ?>" style="padding: 5px;"
                                class="dashicons dashicons-download"></span></a>
                </div>
                <div id="status">
                    <?php echo Plugin::$instance->utils->html->toggle([
                        'name' => 'funnel_status',
                        'id' => 'status-toggle',
                        'value' => 'active',
                        'checked' => $funnel->is_active(),
                        'on' => 'Active',
                        'off' => 'Inactive',
                    ]); ?>
                </div>
                <div id="save">
                    <span class="spinner" style="float: left"></span>
                    <?php submit_button(__('Update', 'groundhogg'), 'primary', 'update', false) ?>
                </div>
            </div>
        </div>
    </div>
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2 main" style="clear: both">
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="step-sortable" class=" ui-sortable">
                    <?php foreach ($funnel->get_steps() as $step): ?>
                        <?php $step->sortable_item(); ?>
                    <?php endforeach; ?>
                </div>
                <div class="add-step-bottom-wrap">
                    <?php
                    echo html()->modal_link( [
                        'title'     => __( 'Add Step', 'groundhogg' ),
                        'text'      => dashicon( 'plus' ) . __( 'Add Step', 'groundhogg' ),
                        'footer_button_text' => __( 'Cancel' ),
                        'class'     => 'add-step button button-secondary no-padding',
                        'id'        => 'add-step-bottom',
                        'source'    => 'steps',
                        'height'    => 700,
                        'width'     => 500,
                        'footer'    => 'true',
                        'preventSave'    => 'true',
                    ] );
                    ?>
                </div>
            </div>
            <div id="postbox-container-2" class="postbox-container">
                <div id="postbox-container-2-inner">
                    <?php Plugin::$instance->notices->print_notices(); ?>
                    <div style="width: 100%" id="reporting-wrap">
                        <?php include_once dirname(__FILE__) . '/reporting.php'; ?>
                        <div class="reporting-view-wrap">
                            <?php

                            $chart_data = Plugin::$instance->admin->get_page( 'funnels' )->get_chart_data();

                            $rows = [];

                            if ( ! empty( $chart_data ) ){
                                $complete = $chart_data[0][ 'data' ];
                                $waiting = $chart_data[1][ 'data' ];

                                foreach ( $complete as $i => $data ){

                                    $rows[] = [
                                        $data[ 0 ],
                                        html()->e( 'a', [ 'href' => $data[ 2 ] ], $data[ 1 ], false ),
                                        html()->e( 'a', [ 'href' => $waiting[ $i ][ 2 ] ], $waiting[ $i ][ 1 ], false ),
                                    ];

                                }
                            }

                            html()->list_table( [], [
                                __( 'Step', 'groundhogg' ),
                                __( 'Complete', 'groundhogg' ),
                                __( 'Waiting', 'groundhogg' )
                            ],
                                $rows
                            );

                            ?>
                        </div>
                    </div>
                    <div id="intro" class="postbox" style="margin: 20px 20px;">
                        <div class="inside">
                            <h1><?php _e('Funnel Builder V2 (BETA)', 'groundhogg'); ?></h1>
                            <p><?php _e('Welcome to version 2 of the Funnel Builder!'); ?></p>
                            <p><?php _e('Our newest iteration of the funnel builder has been designed to provide a superior editing experience on both large and small screens.', 'groundhogg'); ?></p>
                            <div style="position:relative;padding-top:56.25%;">
                                <iframe src="https://player.vimeo.com/video/353449484?title=0&byline=0&portrait=0" frameborder="0" allowfullscreen
                                        style="position:absolute;top:0;left:0;width:100%;height:100%;"></iframe>
                            </div>
                        </div>
                    </div>
                    <div class="step-settings">
                        <?php

                        $step_active = false;

                        foreach ($funnel->get_steps() as $step):
                            $step->html_v2();

                            if ( $step->get_meta( 'is_active' ) ){
                                $step_active = true;
                            }

                        endforeach; ?>
                    </div>
                </div>

            </div>
            <div style="clear: both;"></div>
        </div>
    </div>
</form>
<?php if ( $step_active ): ?>
<script>jQuery('html').addClass( 'active-step' );</script>
<?php endif; ?>
<div class="hidden" id="steps">
    <div class="steps-select">
        <?php

        html()->tabs( [
            'benchmarks-tab'    => __( 'Benchmarks', 'groundhogg' ),
            'actions-tab'       => __( 'Actions', 'groundhogg' )
        ], 'benchmarks-tab' );

        ?>
        <div id='benchmarks'>
            <?php echo html()->help_icon('https://docs.groundhogg.io/docs/builder/benchmarks/'); ?>
            <div class="elements-inner inside">
                <p class="description"><?php echo esc_html__('Benchmarks start and stop automation steps for a contact.', 'groundhogg'); ?></p>
                <table>
                    <tbody>
                    <tr><?php
                        $benchmarks = Plugin::$instance->step_manager->get_benchmarks();
                        $i = 0;
                        foreach ($benchmarks

                        as $benchmark):
                        if (($i % 4) == 0):
                        ?></tr>
                    <tr><?php
                        endif;
                        ?>
                        <td class="step-icon">
                            <div id='<?php echo $benchmark->get_type(); ?>'
                                 title="<?php esc_attr_e($benchmark->get_description()); ?>"
                                 class="wpgh-element ui-draggable">
                                <div class="step-icon"><img width="60" src="<?php echo esc_url($benchmark->get_icon()); ?>">
                                </div>
                                <p><?php echo $benchmark->get_name() ?></p></div>
                        </td><?php
                        $i++;

                        endforeach;

                        ?></tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div id='actions' class="hidden">
            <?php echo html()->help_icon('https://docs.groundhogg.io/docs/builder/actions/'); ?>
            <div class="inside">
                <p class="description"><?php esc_html_e('Actions are launched whenever a contact completes a benchmark.', 'groundhogg'); ?></p>
                <table>
                    <tbody>
                    <tr><?php
                        $actions = Plugin::$instance->step_manager->get_actions();
                        $i = 0;
                        foreach ($actions

                        as $action):
                        if (($i % 4) == 0):
                        ?></tr>
                    <tr><?php
                        endif;
                        ?>
                        <td class="step-icon">
                            <div id='<?php echo $action->get_type(); ?>'
                                 title="<?php esc_attr_e($action->get_description()); ?>" class="wpgh-element ui-draggable">
                                <div class="step-icon"><img width="60" src="<?php echo esc_url($action->get_icon()); ?>">
                                </div>
                                <p><?php echo $action->get_name() ?></p></div>
                        </td><?php
                        $i++;

                        endforeach;

                        ?></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- End Action Icons-->
</div>
<script>
    jQuery(function ($) {

        var $benchmarks = $( '#benchmarks' );
        var $actions =  $( '#actions' );
        var $tabs = $( '.nav-tab' );

        $( '#actions-tab' ).on( 'click', function (e) {
            e.preventDefault();

            $tabs.removeClass( 'nav-tab-active' );
            $(this).addClass( 'nav-tab-active' );

            $benchmarks.addClass( 'hidden' );
            $actions.removeClass( 'hidden' );
        } );

        $( '#benchmarks-tab' ).on( 'click', function (e) {
            e.preventDefault();

            $tabs.removeClass( 'nav-tab-active' );
            $(this).addClass( 'nav-tab-active' );

            $actions.addClass( 'hidden' );
            $benchmarks.removeClass( 'hidden' );
        } );

    });
</script>
<div class="hidden" id="add-contact-modal" style="display: none;">
    <form method="post">
        <?php wp_nonce_field(); ?>
        <?php wp_nonce_field('add_contacts_to_funnel', 'add_contacts_nonce'); ?>
        <div>
            <div class="add-contacts-response hidden"></div>
            <table class="add-contact-form" style="width: 100%;">
                <tbody>
                <tr>
                    <th>
                        <?php _e('Select contacts to add into funnel:', 'groundhogg'); ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <?php

                        echo html()->dropdown([
                            'name' => 'tags_include_needs_all',
                            'id' => 'tags_include_needs_all',
                            'class' => '',
                            'options' => array(
                                0 => __('Any', 'groundhogg'),
                                1 => __('All', 'groundhogg')
                            ),
                            'option_none' => false
                        ]);

                        _e( ' of ', 'groundhogg' );

                        echo Plugin::$instance->utils->html->tag_picker(array(
                            'name' => 'include_tags[]',
                            'id' => 'include_tags',
                        ));

                        ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Exclude these contacts:', 'groundhogg'); ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <?php

                        echo html()->dropdown([
                            'name' => 'tags_exclude_needs_all',
                            'id' => 'tags_exclude_needs_all',
                            'class' => '',
                            'options' => array(
                                0 => __('Any', 'groundhogg'),
                                1 => __('All', 'groundhogg')
                            ),
                            'option_none' => false
                        ]);

                        _e( ' of ', 'groundhogg' );

                        echo Plugin::$instance->utils->html->tag_picker(array(
                            'name' => 'exclude_tags[]',
                            'id' => 'exclude_tags',
                        ));

                        ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php _e('Select where to start:', 'groundhogg'); ?>
                    </th>
                </tr>
                <tr>
                    <td>
                        <?php

                        $options = [];

                        $steps = $funnel->get_steps();
                        foreach ($steps as $step) {
                            $options[$step->get_id()] = sprintf("%d. %s (%s)", $step->get_order(), $step->get_title(), key_to_words($step->get_type()));
                        }

                        echo Plugin::$instance->utils->html->select2(array(
                            'name' => 'which_step',
                            'id' => 'which_step',
                            'data' => $options,
                            'multiple' => false,
                        ));

                        ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <?php submit_button(__('Add Contacts to Funnel', 'groundhogg')); ?>
        </div>
    </form>
</div>
<?php

echo html()->e( 'a', [ 'class' => 'button back-to-admin', 'href' => admin_page_url( 'gh_funnels', [] ) ], __( '&larr; Back to Admin', 'groundhogg' ) );

?>

