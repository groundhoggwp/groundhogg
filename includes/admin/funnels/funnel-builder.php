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

foreach ( glob( dirname( __FILE__ ) . "/elements/*.php" ) as $filename )
{
    include $filename;
}

wp_enqueue_script( 'jquery-ui-sortable' );

?>
<script type="text/javascript">
    document.body.className = document.body.className.replace('no-js','js');
</script>

<style>
    .wpfn-element{
        display: inline-block;
        height: 70px;
        width: 100%;
        padding-top: 10px;
        padding-left: 25px;
        cursor: move;
    }

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
    #benchmarks table td .wpfn-element{
        text-align: left;
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
                            <input type="text" name="post_title" size="30" value="<?php echo wpfn_get_funnel_name( $funnel_id ); ?>" id="title" spellcheck="true" autocomplete="off">
                        </div>
                    </div>
                </div>
                <!-- begin elements area -->
                <div id="postbox-container-1" class="postbox-container">
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

                                    ?><td><div id='<?php echo $element; ?>' class="wpfn-element"><?php do_action( 'wpfn_benckmark_element_icon_html_' . $element ); ?></div></td><?php

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

                                    ?><td><div id='<?php echo $element; ?>' class="wpfn-element"><?php do_action( 'wpfn_action_element_icon_html_' . $element ); ?></div></td><?php

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
                <div id="postbox-container-2" class="postbox-container">

                    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                        <?php do_action('wpfn_funnel_steps_before' ); ?>

                        <?php $steps = wpfn_get_funnel_steps( $funnel_id );

                        foreach ( $steps as $step_id ): ?>

                            <div id="<?php echo $step_id; ?>" class="postbox">
                                <h2 class="hndle ui-sortable-handle"><?php echo __( wpfn_get_step_hndle( $step_id ), 'wp-funnels' ); ?></h2>
                                <div class="inside">
                                    <?php do_action( 'wpfn_step_settings_before' ); ?>
                                    <?php do_action( 'wpfn_get_step_settings_' . wpfn_get_step_type( $step_id ) ); ?>
                                    <?php do_action( 'wpfn_step_settings_after' ); ?>
                                </div>
                            </div>

                        <?php endforeach;?>
                        <?php do_action('wpfn_funnel_steps_after' ); ?>
                    </div>
                </div>
                <script>
                    jQuery(document).ready( function() {
                        jQuery( ".ui-sortable" ).sortable(
                            {
                                placeholder: "sortable-placeholder",
                                connectWith: ".ui-sortable",
                                start: function(e, ui){
                                    ui.placeholder.height(ui.item.height());
                                }
                            }
                        );
                        jQuery( ".ui-sortable" ).disableSelection();
                    });
                </script>
                <!-- end main funnel editing area -->
            </div>
        </div>
    </form>
</div>
