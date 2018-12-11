<?php
/**
 * Add Funnel
 *
 * Similar to the Email add page, this allows one to select a funnel from some pre-installed defaults.
 * Or upload their own funnel if they purchased one from us or another provider
 *
 * @package     Admin
 * @subpackage  Admin/Funnels
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.1
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

do_action( 'wpgh_before_new_funnel' );

?>
<?php $active_tab = isset( $_GET[ 'tab' ] ) ?  $_GET[ 'tab' ] : 'templates'; ?>
<h2 class="nav-tab-wrapper">
    <a href="?page=gh_funnels&action=add&tab=templates" class="nav-tab <?php echo $active_tab == 'templates' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Funnel Templates', 'groundhogg'); ?></a>
    <a href="?page=gh_funnels&action=add&tab=marketplace" class="nav-tab <?php echo $active_tab == 'marketplace' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Marketplace', 'groundhogg'); ?></a>
    <a href="?page=gh_funnels&action=add&tab=import" class="nav-tab <?php echo $active_tab == 'import' ? 'nav-tab-active' : ''; ?>"><?php _e( 'Import Funnel', 'groundhogg'); ?></a>
</h2>
<!-- search form -->
<?php do_action('wpgh_add_new_funnel_form_before'); ?>

<?php if ( 'templates' === $active_tab ): ?>
    <form method="post" id="poststuff" >
        <?php wp_nonce_field(); ?>
        <?php include WPGH_PLUGIN_DIR . 'templates/funnel-templates.php'; ?>
        <?php foreach ( $funnel_templates as $id => $funnel_args ): ?>
            <div class="postbox" style="margin-right:20px;width: 400px;display: inline-block;">
                <h2 class="hndle"><?php echo $funnel_args['title']; ?></h2>
                <div class="inside">
                    <p><?php echo $funnel_args['description']; ?></p>
                    <!-- <div class="postbox">
                        <img src="<?php echo $funnel_args['src']; ?>" width="100%">
                    </div> -->
                    <button class="button-primary" name="funnel_template" value="<?php echo $id ?>"><?php _e('Start Building', 'groundhogg'); ?></button>
                </div>
            </div>

        <?php endforeach; ?>
    </form>
<?php elseif ( 'marketplace' === $active_tab ):
    ?>
    <form>
        <input type="text" id="search_funnel" placeholder="Search Here.."  class="" style="margin-top : 2% ; width: 100%; margin-bottom: 2%" />

    </form>
    <div style="text-align: center" id="spinner">
        <span class="spinner" style="float: none"></span>
    </div>
    <script type="text/javascript">
        (function ($) {

            //setup before functions
            var typingTimer;                //timer identifier
            var doneTypingInterval = 500;  //time in ms, 5 second for example
            var $input = $('#search_funnel');

            $("#search_funnel").keyup(function(){

                clearTimeout(typingTimer);
                typingTimer = setTimeout(ajaxCall, doneTypingInterval);
            });

            $("#search_funnel").keydown(function(){
                clearTimeout(typingTimer);
            });

            function ajaxCall() {
                $("#poststuff").hide();
                $(".spinner").css( 'visibility', 'visible' );
                var ajaxCall = $.ajax({
                    type: "post",
                    url: ajaxurl,
                    dataType: 'json',
                    data: { action: 'gh_get_templates', s: $( '#search_funnel' ).val()},
                    success: function ( response ) {
                        $(".spinner").css( 'visibility', 'hidden' );
                        $("#poststuff").show();
                        $("#poststuff").html( response.html ) ;

                    }
                });
            }
        })(jQuery);

    </script>
    <div id="poststuff">
        <?php WPGH()->menu->funnels_page->display_funnel_templates(); ?>
    </div>

<?php else: ?>
    <div class="show-upload-view">
        <div class="upload-pluing-wrap">
            <div class="upload-plugin">
                <p class="install-help"><?php _e( 'If you have a funnel import file (ends in .funnel) you can upload it here!', 'groundhogg' ); ?></p>
                <form method="post" enctype="multipart/form-data" class="wp-upload-form">
                    <?php wp_nonce_field(); ?>

                    <input type="file" name="funnel_template" id="funnel_template" accept=".funnel">

                    <button class="button-primary" name="funnel_inport" value="import"><?php _e('Import Funnels', 'groundhogg'); ?></button>

                </form>
            </div>
        </div>
    </div>
<?php endif;?>
<?php do_action('wpgh_add_new_funnel_form_after'); ?>
<?php


