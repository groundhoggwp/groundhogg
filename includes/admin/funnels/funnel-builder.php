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


if ( ! isset( $_GET['ID'] ) || ! is_numeric( $_GET['ID'] ) )
{
    wp_die( __( 'Funnel ID not supplied. Please try again', 'wp-funnels' ), __( 'Error', 'wp-funnels' ) );
}

$funnel_id = intval( $_GET['ID'] );


foreach ( glob( dirname( __FILE__ ) . "/elements/*/*.php" ) as $filename )
{
    include $filename;
}

wp_enqueue_script( 'jquery-ui-sortable' );
wp_enqueue_script( 'jquery-ui-draggable' );
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
        margin-right: 0;
    }

    .funnel-editor .postbox.action {
        width: 90%;
        margin-left: auto;
        margin-right: 0;
    }

    .funnel-editor .hndle{
        background-color: rgba(157, 157, 157, 0.11);
    }

    .funnel-editor .postbox.action {
        background-color: rgb(241, 253, 243);
    }

    .funnel-editor .postbox.benchmark{
        background-color: rgb(255, 254, 218);
    }

    select {
        vertical-align: top;
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
                </div>
                <!-- begin elements area -->
                <div id="postbox-container-1" class="postbox-container sticky">
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

                            foreach ( $steps as $i => $step_id ): ?>
                                <div id="<?php echo $step_id; ?>" class="postbox <?php echo wpfn_get_step_group( $step_id ); ?>">
                                    <button type="button" class="handlediv delete-step-<?php echo $step_id;?>">
                                        <span class="dashicons dashicons-trash"></span>
                                        <script>
                                            jQuery(document).ready(function(){jQuery('.delete-step-<?php echo $step_id;?>').click( wpfn_delete_funnel_step )})
                                        </script>
                                    </button>
                                    <h2 class="hndle ui-sortable-handle"><label for="<?php echo $step_id; ?>_title"><span class="dashicons <?php echo esc_attr( wpfn_get_step_dashicon_by_id( $step_id ) ); ?>"></span></label><input title="step title" type="text" id="<?php echo $step_id; ?>_title" name="<?php echo $step_id; ?>_title" class="regular-text" value="<?php echo __( wpfn_get_step_hndle( $step_id ), 'wp-funnels' ); ?>"></h2>
                                    <div class="inside">
                                        <input type="hidden" name="<?php echo wpfn_prefix_step_meta( $step_id, 'order' ); ?>" value="<?php echo $i + 1; ?>" >
                                        <input type="hidden" name="steps[]" value="<?php echo $step_id; ?>">
                                        <?php do_action( 'wpfn_step_settings_before' ); ?>
                                        <?php do_action( 'wpfn_get_step_settings_' . wpfn_get_step_type( $step_id ), $step_id ); ?>
                                        <?php do_action( 'wpfn_step_settings_after' ); ?>
                                    </div>
                                </div>
                            <?php endforeach;
                        endif; ?>
                        <?php do_action('wpfn_funnel_steps_after' ); ?>
                    </div>
                </div>
                <!-- end main funnel editing area -->
            </div>
        </div>
    </form>
</div>
