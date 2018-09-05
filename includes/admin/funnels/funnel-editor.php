<?php
/**
 * Funnel Builder
 *
 * Drag and drop builder for marketing automation
 *
 * @package     groundhogg
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */

$funnel_id = intval( $_GET['funnel'] );

//for link editor
wp_enqueue_editor();
wp_enqueue_script('wplink');
wp_enqueue_style('editor-buttons');

wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
wp_enqueue_script( 'link-picker', WPGH_ASSETS_FOLDER . '/js/admin/link-picker.js' );
//wp_enqueue_script( 'sticky-sidebar', WPGH_ASSETS_FOLDER . '/js/lib/sticky-admin-sidebar.js' );
wp_enqueue_script( 'sticky-sidebar', WPGH_ASSETS_FOLDER . '/js/lib/sticky-sidebar/sticky-sidebar.js' );
wp_enqueue_script( 'jquery-sticky-sidebar', WPGH_ASSETS_FOLDER . '/js/lib/sticky-sidebar/jquery.sticky-sidebar.js' );
wp_enqueue_script( 'funnel-editor', WPGH_ASSETS_FOLDER . '/js/admin/funnel-editor.js' );
wp_enqueue_style( 'funnel-editor', WPGH_ASSETS_FOLDER . '/css/admin/funnel-editor.css' );

do_action( 'wpgh_funnel_editor_before_everything', $funnel_id );

?>
<form method="post">
    <?php wp_nonce_field(); ?>
    <div id='poststuff' class="wpgh-funnel-builder" style="overflow: hidden">
        <div id="post-body" class="metabox-holder columns-2 main" style="clear: both">
            <div id="post-body-content">
                <div id="titlediv">
                    <div id="titlewrap">
                        <label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo __('Enter Funnel Name Here', 'groundhogg');?></label>
                        <input placeholder="<?php echo __('Enter Funnel Name Here', 'groundhogg');?>" type="text" name="funnel_title" size="30" value="<?php echo wpgh_get_funnel_name( $funnel_id ); ?>" id="title" spellcheck="true" autocomplete="off">
                    </div>
                </div>
                <div class="postbox">
                    <h2 class="hndle"><?php _e("Reporting", 'groundhogg'); ?></h2>
                    <div class="inside">
                        <select class="input" name="date_range" id="date_range">
                            <?php $selected = ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24' ; ?>
                            <option value="last_24" <?php if ( $selected == 'last_24' ) echo 'selected'; ?> ><?php _e( "Last 24 Hours", 'groundhogg' );?></option>
                            <option value="last_7" <?php if ( $selected == 'last_7' ) echo 'selected'; ?>><?php _e( "Last 7 Days", 'groundhogg' );?></option>
                            <option value="last_30" <?php if ( $selected == 'last_30' ) echo 'selected'; ?>><?php _e( "Last 30 Days", 'groundhogg' );?></option>
                            <option value="custom" <?php if ( $selected == 'custom' ) echo 'selected'; ?>><?php _e( "Custom Range", 'groundhogg' );?></option>
                        </select>
                        <input autocomplete="off" placeholder="<?php esc_attr_e('From:'); ?>" class="input <?php if ( $selected !== 'custom' ) echo 'hidden'; ?>" id="custom_date_range_start" name="custom_date_range_start" type="text" value="<?php if ( isset(  $_POST[ 'custom_date_range_start' ] ) ) echo $_POST['custom_date_range_start']; ?>">
                        <script>jQuery(function($){$('#custom_date_range_start').datepicker({
                                changeMonth: true,
                                changeYear: true,
                                maxDate:0,
                                dateFormat:'d-m-yy'
                            })});</script>
                        <input autocomplete="off" placeholder="<?php esc_attr_e('To:'); ?>" class="input <?php if ( $selected !== 'custom' ) echo 'hidden'; ?>" id="custom_date_range_end" name="custom_date_range_end" type="text" value="<?php if ( isset(  $_POST[ 'custom_date_range_end' ] ) ) echo $_POST['custom_date_range_end']; ?>">
                        <script>jQuery(function($){$('#custom_date_range_end').datepicker({
                                changeMonth: true,
                                changeYear: true,
                                maxDate:0,
                                dateFormat:'d-m-yy'
                            })});</script>

                        <script>jQuery(function($){$('#date_range').change(function(){
                            if($(this).val() === 'custom'){
                                $('#custom_date_range_end').removeClass('hidden');
                                $('#custom_date_range_start').removeClass('hidden');
                            } else {
                                $('#custom_date_range_end').addClass('hidden');
                                $('#custom_date_range_start').addClass('hidden');
                            }})});
                        </script>
                        <?php submit_button( 'Filter', 'secondary', 'change_reporting', false ); ?>
                        <?php do_action( 'funnel_sate_range_filters_after' ); ?>
                        <div style="float: right; display: inline-block;">
                            <div class="onoffswitch">
                                <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="reporting-toggle">
                                <label class="onoffswitch-label" for="reporting-toggle">
                                    <span class="onoffswitch-inner"></span>
                                    <span class="onoffswitch-switch"></span>
                                </label>
                            </div>
                        </div>
                        <script>
                            jQuery(function($){$("#reporting-toggle").on( 'input', function(){
                                if ( $(this).is(':checked')){
                                    $('.step-reporting').removeClass('hidden');
                                    $('.step-edit').addClass('hidden');
                                } else {
                                    $('.step-reporting').addClass('hidden');
                                    $('.step-edit').removeClass('hidden');
                                }
                            })});
                        </script>
                    </div>
                </div>
            </div>
            <!-- begin elements area -->
            <div id="postbox-container-1" class="postbox-container sidebar">
                <div id="submitdiv" class="postbox">
                    <h3 class="hndle"><?php echo __( 'Funnel Status', 'groundhogg' );?></h3>
                    <div class="inside">
                        <div class="submitbox">
                            <div id="minor-publishing-actions">
                                <?php do_action( 'wpgh_funnel_status_before' ); ?>
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th><label for="funnel_export"><?php echo __( 'Export', 'groundhogg' );?></label></th>
                                        <td>
                                            <a href="<?php echo esc_url( add_query_arg( 'export', 1 , $_SERVER['REQUEST_URI'] ) ); ?>" class="button button-secondary"><?php _e( 'Export Funnel', 'groundhogg'); ?></a>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th><label for="funnel_status"><?php echo __( 'Status', 'groundhogg' );?></label></th>
                                        <td>
                                            <input type="hidden" name="funnel_status" id="funnel-status" value="<?php echo wpgh_get_funnel_status( $funnel_id );?> ">
                                            <div id="status-toggle-switch" class="onoffswitch" style="text-align: left">
                                                <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="status-toggle" <?php if ( wpgh_get_funnel_status( $funnel_id ) == 'active' ) echo 'checked'; ?>>
                                                <label class="onoffswitch-label" for="status-toggle">
                                                    <span class="onoffswitch-inner"></span>
                                                    <span class="onoffswitch-switch"></span>
                                                </label>
                                            </div>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                                <?php do_action( 'wpgh_funnel_status_after' ); ?>
                                <div style="text-align: left" id="confirm" class="hidden">
                                    <p>
                                        <label for="confirm-inactive"><input type="checkbox" value="yes" id="confirm-inactive" name="confirm"><?php _e('Are you sure? Setting the status to inactive will stop any automation present or future from happening.', 'groundhogg' ); ?></label></td>
                                    </p>
                                    <script>jQuery(function($){
                                            $( '#status-toggle' ).change(function(){
                                                if ( ! $(this).is(':checked') ){
                                                    $('#confirm').removeClass('hidden');
                                                    $('#funnel-status').val( 'inactive' )
                                                } else {
                                                    $('#confirm').addClass('hidden');
                                                    $('#funnel-status').val( 'active' )
                                                }
                                            });
                                        })</script>
                                </div>
                            </div>
                            <div id="major-publishing-actions">
                                <div id="delete-action">
                                    <a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=gh_funnels&action=archive&funnel=' . $funnel_id ), 'archive' ) ); ?>"><?php echo esc_html__( 'Archive Funnel', 'groundhogg' ); ?></a>
                                </div>
                                <div id="publishing-action">
                                    <span class="spinner"></span>
                                    <input name="original_publish" type="hidden" id="original_publish" value="Update">
                                    <input name="save" type="submit" class="button button-primary button-large" id="publish" value="Update">
                                </div>
                                <div class="clear"></div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Begin Benckmark Icons-->
                <div id='benchmarks' class="postbox">
                    <h3 class="hndle"><?php echo __( 'Benchmarks', 'groundhogg' );?></h3>
                    <div class="elements-inner inside">
                        <?php do_action( 'wpgh_benchmark_icons_before' ); ?>
                        <table>
                            <tbody>
                            <?php $elements = wpgh_get_funnel_benchmarks();

                            $i = 0;

                            ?><tr><?php

                            foreach ( $elements as  $element => $args  ):

                                if ( ( $i % 3 ) == 0 ):
                                    ?></tr><tr><?php
                                endif;

                                ?><td><div id='<?php echo $element; ?>' class="wpgh-element ui-draggable"><div class="step-icon"><img width="60" src="<?php echo esc_url( $args['icon'] ); ?>"></div><p><?php echo $args['title']; ?></p></div></td><?php

                                $i++;

                            endforeach;

                            ?></tr><?php

                                ?>
                            </tbody>
                        </table>
                        <?php do_action( 'wpgh_benchmark_icons_after' ); ?>
                        <p>
                            <?php echo esc_html__( 'Benchmarks start and stop automation actions for a contact.','groundhogg' ); ?>
                        </p>
                    </div>
                </div>
                <!-- End Benckmark Icons-->
                <!-- Begin Action Icons-->
                <div id='actions' class="postbox">
                    <h2 class="hndle"><?php echo __( 'Actions', 'groundhogg' );?></h2>
                    <div class="inside">
                        <?php do_action( 'wpgh_action_icons_before' ); ?>
                        <table>
                            <tbody>
                            <?php $elements = wpgh_get_funnel_actions();

                            $i = 0;

                            ?><tr><?php

                            foreach ( $elements as  $element => $args  ):

                                if ( ( $i % 3 ) == 0 ):
                                ?></tr><tr><?php
                                endif;

                                ?><td><div id='<?php echo $element; ?>' class="wpgh-element ui-draggable"><div class="step-icon"><img width="60" src="<?php echo esc_url( $args['icon'] ); ?>"></div><p><?php echo $args['title']; ?></p></div></td><?php

                                $i++;

                            endforeach;

                            ?></tr><?php

                                ?>
                            </tbody>
                        </table>
                        <?php do_action( 'wpgh_action_icons_after' ); ?>

                        <p>
                            <?php echo esc_html__( 'Actions are launched whenever a contact completes a benchmark.','groundhogg' ); ?>
                        </p>
                    </div>

                </div>
                <!-- End Action Icons-->
            </div>
            <!-- End elements area-->
            <!-- main funnel editing area -->
            <div  id="postbox-container-2" class="postbox-container funnel-editor">
                <div style="visibility: hidden" id="normal-sortables" class="meta-box-sortables ui-sortable">
                    <?php do_action('wpgh_funnel_steps_before' ); ?>

                    <?php $steps = wpgh_get_funnel_steps( $funnel_id );

                    if ( empty( $steps ) ): ?>
                        <div class="">
                            Drag in new steps to build the ultimate sales machine!
                        </div>
                    <?php else:

                        if ( wpgh_get_step_group( $steps[0] ) !== 'benchmark' ){
                            ?>
                            <div class="notice notice-error is-dismissible"><p>Funnels should start with benchmarks, otherwise actions cannot be triggered. Please use a benchmark to trigger automation.</p></div>
                            <?php
                        }

                        foreach ( $steps as $i => $step_id ):
                            wpgh_get_step_html( $step_id );
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
