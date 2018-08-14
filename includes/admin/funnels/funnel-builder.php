<?php
/**
 * Funnel Builder
 *
 * Drag and drop builder for marketing automation
 *
 * @package     wp-funnels
 * @subpackage  Includes/Funnels
 * @copyright   Copyright (c) 2018, Adrian Tobey
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       0.1
 */


if ( ! isset( $_GET['funnel'] ) || ! is_numeric( $_GET['funnel'] ) )
{
    wp_die( __( 'Funnel ID not supplied. Please try again', 'wp-funnels' ), __( 'Error', 'wp-funnels' ) );
}

$funnel_id = intval( $_GET['funnel'] );


foreach ( glob( dirname( __FILE__ ) . "/elements/*/*.php" ) as $filename )
{
    include $filename;
}

wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui', 'http://code.jquery.com/ui/1.12.1/themes/smoothness/jquery-ui.css' );
wp_enqueue_script( 'funnel-editor', WPFN_ASSETS_FOLDER . '/js/admin/funnel-editor.js' );

do_action( 'wpfn_funnel_editor_before_everything', $funnel_id );

?>
<script type="text/javascript">
    document.body.className = document.body.className.replace('no-js','js');
</script>

<style>
    .wpfn-element div{
        box-sizing: border-box;
        display: inline-block;
        height: 70px;
        width: 100%;
        padding-top: 10px;
        /*padding-bottom: 70px;*/
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
        font-size: 60px;
        width: 120px;
        height: 120px;
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
        width: 600px;
        border-radius: 0px;
        margin-left: auto;
        margin-right: auto;
    }

    .funnel-editor .postbox.benchmark{
        /*background-color: rgb(255, 254, 218);*/
        width: 500px;
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

    .switch {
        position: relative;
        display: inline-block;
        width: 60px;
        height: 34px;
    }

    .switch input {display:none;}

    .slider {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 26px;
        width: 13px;
        left: 4px;
        bottom: 4px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #0088BC;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #0088BC;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(39px);
        -ms-transform: translateX(39px);
        transform: translateX(39px);
    }

    .step-reporting p,
    .report {
        text-align: center;
        font-size: 16px;
    }

</style>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo __('Edit Funnel', 'wp-funnels');?></h1>
    <form method="post">
        <div id='poststuff' class="wpfn-funnel-builder">
            <div id="post-body" class="metabox-holder columns-2">
                <div id="post-body-content">
                    <div id="titlediv">
                        <div id="titlewrap">
                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo __('Enter Funnel Name Here', 'wp-funnels');?></label>
                            <input placeholder="<?php echo __('Enter Funnel Name Here', 'wp-funnels');?>" type="text" name="funnel_title" size="30" value="<?php echo wpfn_get_funnel_name( $funnel_id ); ?>" id="title" spellcheck="true" autocomplete="off">
                        </div>
                    </div>
                    <div class="postbox">
                        <h2 class="hndle"><?php _e("Reporting", 'wp-funnels'); ?></h2>
                        <div class="inside">
                            <select class="input" name="date_range" id="date_range">
                                <?php $selected = ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_24' ; ?>
                                <option value="last_24" <?php if ( $selected == 'last_24' ) echo 'selected'; ?> ><?php _e( "Last 24 Hours", 'wp-funnels' );?></option>
                                <option value="last_7" <?php if ( $selected == 'last_7' ) echo 'selected'; ?>><?php _e( "Last 7 Days", 'wp-funnels' );?></option>
                                <option value="last_30" <?php if ( $selected == 'last_30' ) echo 'selected'; ?>><?php _e( "Last 30 Days", 'wp-funnels' );?></option>
                                <option value="custom" <?php if ( $selected == 'custom' ) echo 'selected'; ?>><?php _e( "Custom Range", 'wp-funnels' );?></option>
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
                            <div style="float: right; display: inline-block; border: 1px solid #e5e5e5; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
                                <label class="switch">
                                    <input type="checkbox" id="reporting-toggle">
                                    <span class="slider"></span>
                                </label>
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
                            <h3 class="hndle"><?php echo __( 'Funnel Status', 'wp-funnels' );?></h3>
                            <div class="inside">
                                <div class="submitbox">
                                    <div id="minor-publishing-actions">
                                        <?php do_action( 'wpfn_funnel_status_before' ); ?>
                                        <table class="form-table">
                                            <tbody>
                                            <tr>
                                                <th><label for="funnel_status"><?php echo __( 'Status', 'wp-funnels' );?></label></th>
                                                <td>
                                                    <select id="funnel_status" name="funnel_status">
                                                        <option value="inactive" <?php if ( wpfn_get_funnel_status( $funnel_id ) == 'inactive' ) echo 'selected="selected"'; ?>><?php echo __( 'Inactive', 'wp-funnels' );?></option>
                                                        <option value="active" <?php if ( wpfn_get_funnel_status( $funnel_id ) == 'active' ) echo 'selected="selected"'; ?>><?php echo __( 'Active', 'wp-funnels' );?></option>
                                                    </select>
                                                </td>
                                            </tr>
                                            </tbody>
                                        </table>
                                        <?php do_action( 'wpfn_funnel_status_after' ); ?>
                                        <div style="text-align: left" id="confirm" class="hidden">
                                            <p>
                                                <label for="confirm-inactive"><input type="checkbox" value="yes" id="confirm-inactive" name="confirm"><?php _e('Are you sure? Setting the status to inactive will stop any automation present or future from happening.', 'wp-funnels' ); ?></label></td>
                                            </p>
                                            <script>jQuery(function($){
                                                    $( '#funnel_status' ).change(function(){
                                                        if ( $(this).val() === 'inactive' ){
                                                            $('#confirm').removeClass('hidden');
                                                        } else {
                                                            $('#confirm').addClass('hidden');
                                                        }
                                                    });
                                                })</script>
                                        </div>
                                    </div>
                                    <div id="major-publishing-actions">
                                        <div id="delete-action">
                                            <a class="submitdelete deletion" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=funnels' ), 'delete_funnel', 'wpfn_nonce' ) ); ?>"><?php echo esc_html__( 'Delete Funnel', 'wp-funnels' ); ?></a>
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
                            <h3 class="hndle"><?php echo __( 'Benchmarks', 'wp-funnels' );?></h3>
                            <div class="elements-inner inside">
                                <?php do_action( 'wpfn_benchmark_icons_before' ); ?>
                                <table>
                                    <tbody>
                                    <?php $elements = wpfn_get_funnel_benchmark_icons();

                                    foreach ( $elements as $i => $element ):

                                        if ( ( $i % 2 ) == 0 ):
                                            ?><tr><?php
                                        endif;

                                        ?><td><div id='<?php echo $element; ?>' class="wpfn-element ui-draggable"><?php do_action( 'wpfn_benchmark_element_icon_html_' . $element ); ?></div></td><?php

                                        if ( $i & 1 ):
                                            ?></tr><?php
                                        endif;

                                    endforeach;
                                    ?>
                                    </tbody>
                                </table>
                                <?php do_action( 'wpfn_benchmark_icons_after' ); ?>
                                <p>
                                    <?php echo esc_html__( 'Benchmarks start and stop automation actions for a contact.','wp-funnels' ); ?>
                                </p>
                            </div>
                        </div>
                        <!-- End Benckmark Icons-->

                        <!-- Begin Action Icons-->
                        <div id='actions' class="postbox">
                            <h2 class="hndle"><?php echo __( 'Actions', 'wp-funnels' );?></h2>
                            <div class="inside">
                                <?php do_action( 'wpfn_action_icons_before' ); ?>
                                <table>
                                    <tbody>
                                    <?php $elements = wpfn_get_funnel_action_icons();

                                    foreach ( $elements as $i => $element ):

                                        if ( ( $i % 2 ) == 0 ):
                                            ?><tr><?php
                                        endif;

                                        ?><td><div id='<?php echo $element; ?>' class="wpfn-element ui-draggable"><?php do_action( 'wpfn_action_element_icon_html_' . $element ); ?></div></td><?php

                                        if ( $i & 1 ):
                                            ?></tr><?php
                                        endif;

                                    endforeach;
                                    ?>
                                    </tbody>
                                </table>
                                <?php do_action( 'wpfn_action_icons_after' ); ?>

                                <p>
                                    <?php echo esc_html__( 'Actions are launched whenever a contact completes a benchmark.','wp-funnels' ); ?>
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
