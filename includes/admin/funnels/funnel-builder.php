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


if ( ! isset( $_GET['funnel'] ) || ! is_numeric( $_GET['funnel'] ) )
{
    wp_die( __( 'Funnel ID not supplied. Please try again', 'groundhogg' ), __( 'Error', 'groundhogg' ) );
}

$funnel_id = intval( $_GET['funnel'] );


foreach ( glob( dirname( __FILE__ ) . "/elements/*/*.php" ) as $filename )
{
    include $filename;
}

//for link editor
wp_enqueue_editor();
wp_enqueue_script('wplink');
wp_enqueue_style('editor-buttons');

wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui', 'http://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
wp_enqueue_script( 'funnel-editor', WPFN_ASSETS_FOLDER . '/js/admin/funnel-editor.js' );
wp_enqueue_script( 'link-picker', WPFN_ASSETS_FOLDER . '/js/admin/link-picker.js' );

do_action( 'wpfn_funnel_editor_before_everything', $funnel_id );

?>
<script type="text/javascript">
    document.body.className = document.body.className.replace('no-js','js');
</script>

<style>
    .wpfn-element div{
        box-sizing: border-box;
        display: inline-block;
        width: 100%;
        /*padding-top: 10px;*/
        /*padding-bottom: 70px;*/
    }
    .wpfn-element p{
        margin: -12px 0 0 0;
    }

    .hndle label {
        margin: 0 10px 0 0;
    }

    .wpfn-element.ui-draggable-dragging .dashicons,
    #actions .dashicons,
    #benchmarks .dashicons{
        font-size: 60px;
    }

    #actions table,
    #benchmarks table{
        box-sizing: border-box;
        width: 100%;
        border-spacing: 7px;
    }

    #actions table td,
    #benchmarks table td{
        text-align: center;
        border: 1px solid #F1F1F1;

    }

    #actions table td .wpfn-element,
    #benchmarks table td .wpfn-element,
    .wpfn-element p{
        text-align: center;
        cursor: move;
    }

    .wpfn-element.ui-draggable-dragging {
        width: 120px;
        background: #FFFFFF;
        border: 1px solid #F1F1F1;
    }

    .funnel-editor .sortable-placeholder {
        box-sizing: border-box;
        margin-left: auto;
        margin-right: auto;
    }

    .funnel-editor .postbox.action .hndle{
        background-color: rgb(241, 253, 243);
    }

    .funnel-editor .postbox.benchmark .hndle{
        background-color: rgb(255, 254, 218);
        border-radius: 20px 20px 0 0;
    }

    .funnel-editor .postbox.action {
        /*background-color: rgb(241, 253, 243);*/
        width: 620px;
        border-radius: 0px;
        margin-left: auto;
        margin-right: auto;
    }

    .funnel-editor .postbox.benchmark{
        /*background-color: rgb(255, 254, 218);*/
        width: 580px;
        border-radius: 20px;
        margin-left: auto;
        margin-right: auto;
    }

    select {
        vertical-align: top;
    }

    #postbox-container-2{
        padding: 40px 0 40px 0;

        background-size: 40px 40px;
        background-image: linear-gradient(to right, #e5e5e5 1px, transparent 1px), linear-gradient(to bottom, #e5e5e5 1px, transparent 1px);
    }

    .onoffswitch {
        position: relative; width: 110px;
        -webkit-user-select:none; -moz-user-select:none; -ms-user-select: none;
    }
    .onoffswitch-checkbox {
        visibility: hidden;
        position: absolute;
    }
    .onoffswitch-label {
        display: block; overflow: hidden; cursor: pointer;
        border: 1px solid #999999; border-radius: 4px;
    }
    .onoffswitch-inner {
        display: block; width: 200%; margin-left: -100%;
        transition: margin 0.3s ease-in 0s;
    }
    .onoffswitch-inner:before, .onoffswitch-inner:after {
        display: block; float: left; width: 50%; height: 30px; padding: 0; line-height: 30px;
        font-size: 14px; color: white;
        box-sizing: border-box;
    }
    .onoffswitch-inner:before {
        text-shadow: 0 -1px 1px #006799, 1px 0 1px #006799, 0 1px 1px #006799, -1px 0 1px #006799;
        content: "Reporting";
        padding-left: 10px;
        background-color: #008ec2; color: #FFFFFF;
    }

    #status-toggle-switch .onoffswitch-inner:before{
        content: "Active";
    }

    .onoffswitch-inner:after {
        content: "Editing";
        padding-right: 10px;
        background-color: #EEEEEE; color: #999999;
        text-align: right;
    }

    #status-toggle-switch .onoffswitch-inner:after{
        content: "Inactive";
    }

    .onoffswitch-switch {
        display: block; width: 21px; margin: 4.5px;
        background: #FFFFFF;
        position: absolute; top: 0; bottom: 0;
        right: 78px;
        border: 1px solid #999999; border-radius: 4px;
        transition: all 0.3s ease-in 0s;
    }
    .onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-inner {
        margin-left: 0;
    }
    .onoffswitch-checkbox:checked + .onoffswitch-label .onoffswitch-switch {
        right: 0px;
    }

    .step-reporting p,
    .report {
        text-align: center;
        font-size: 16px;
    }

    .hndle input {
        vertical-align: top;
    }

    .hndle .hndle-icon{
        border-radius: 100%;
        background: #FFF;
        margin-left: -38px;
        margin-right: 10px;
        margin-top: 12px;
        margin-bottom: -37px;
        border: 1px solid #e5e5e5;
    }

</style>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo __('Edit Funnel', 'groundhogg');?></h1><a class="page-title-action aria-button-if-js" href="<?php echo admin_url( 'admin.php?page=gh_funnels&action=add' ); ?>"><?php _e( 'Add New' ); ?></a>
    <hr class="wp-header-end">
    <form method="post">
        <div id='poststuff' class="wpfn-funnel-builder">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo __('Enter Funnel Name Here', 'groundhogg');?></label>
                            <input placeholder="<?php echo __('Enter Funnel Name Here', 'groundhogg');?>" type="text" name="funnel_title" size="30" value="<?php echo wpfn_get_funnel_name( $funnel_id ); ?>" id="title" spellcheck="true" autocomplete="off">
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
                <div id="postbox-container-1" class="postbox-container sticky">
                    <div class="sticky-actions" id="sticky-actions">
                        <div id="submitdiv" class="postbox">
                            <h3 class="hndle"><?php echo __( 'Funnel Status', 'groundhogg' );?></h3>
                            <div class="inside">
                                <div class="submitbox">
                                    <div id="minor-publishing-actions">
                                        <?php do_action( 'wpfn_funnel_status_before' ); ?>
                                        <table class="form-table">
                                            <tbody>
                                            <tr>
                                                <th><label for="funnel_status"><?php echo __( 'Status', 'groundhogg' );?></label></th>
                                                <td>
                                                    <input type="hidden" name="funnel_status" id="funnel-status" value="<?php echo wpfn_get_funnel_status( $funnel_id );?> ">
                                                    <div id="status-toggle-switch" class="onoffswitch" style="text-align: left">
                                                        <input type="checkbox" name="onoffswitch" class="onoffswitch-checkbox" id="status-toggle" <?php if ( wpfn_get_funnel_status( $funnel_id ) == 'active' ) echo 'checked'; ?>>
                                                        <label class="onoffswitch-label" for="status-toggle">
                                                            <span class="onoffswitch-inner"></span>
                                                            <span class="onoffswitch-switch"></span>
                                                        </label>
                                                    </div>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <?php do_action( 'wpfn_funnel_status_after' ); ?>
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
                                <?php do_action( 'wpfn_benchmark_icons_before' ); ?>
                                <table>
                                    <tbody>
                                    <?php $elements = wpfn_get_funnel_benchmarks();

                                    $i = 0;

                                    foreach ( $elements as  $element => $args  ):

                                        if ( ( $i % 2 ) == 0 ):
                                            ?><tr><?php
                                        endif;

                                        ?><td><div id='<?php echo $element; ?>' class="wpfn-element ui-draggable"><div class="step-icon"><img width="100%" src="<?php echo esc_url( $args['icon'] ); ?>"></div><p><?php echo $args['title']; ?></p></div></td><?php

                                        if ( $i & 1 ):
                                            ?></tr><?php
                                        endif;

                                        $i++;

                                    endforeach;
                                    ?>
                                    </tbody>
                                </table>
                                <?php do_action( 'wpfn_benchmark_icons_after' ); ?>
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
                                <?php do_action( 'wpfn_action_icons_before' ); ?>
                                <table>
                                    <tbody>
                                    <?php $elements = wpfn_get_funnel_actions();

                                    $i = 0;

                                    foreach ( $elements as  $element => $args  ):

                                        if ( ( $i % 2 ) == 0 ):
                                            ?><tr><?php
                                        endif;

                                        ?><td><div id='<?php echo $element; ?>' class="wpfn-element ui-draggable"><div class="step-icon"><img width="100%" src="<?php echo esc_url( $args['icon'] ); ?>"></div><p><?php echo $args['title']; ?></p></div></td><?php

                                        if ( $i & 1 ):
                                            ?></tr><?php
                                        endif;

                                        $i++;

                                    endforeach;
                                    ?>
                                    </tbody>
                                </table>
                                <?php do_action( 'wpfn_action_icons_after' ); ?>

                                <p>
                                    <?php echo esc_html__( 'Actions are launched whenever a contact completes a benchmark.','groundhogg' ); ?>
                                </p>
                            </div>

                        </div>
                        <!-- End Action Icons-->
                    </div>
                </div>
                <!-- End elements area-->

                <!-- main funnel editing area -->
                <div id="postbox-container-2" class="postbox-container funnel-editor">
                    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                        <?php do_action('wpfn_funnel_steps_before' ); ?>

                        <?php $steps = wpfn_get_funnel_steps( $funnel_id );

                        if ( empty( $steps ) ): ?>
                            <div class="">
                                Drag in new steps to build the ultimate sales machine!
                            </div>
                        <?php else:

                            if ( wpfn_get_step_group( $steps[0] ) !== 'benchmark' ){
                                ?>
                                <div class="notice notice-error is-dismissible"><p>Funnels should start with benchmarks, otherwise actions cannot be triggered. Please use a benchmark to trigger automation.</p></div>
                                <?php
                            }

                            foreach ( $steps as $i => $step_id ):
                                wpfn_get_step_html( $step_id );
                            endforeach;

                        endif; ?>
                        <?php do_action('wpfn_funnel_steps_after' ); ?>
                    </div>
                </div>
                <!-- end main funnel editing area -->
            </div>
        </div>
    </form>
</div>
