<?php

namespace Groundhogg\Admin\Emails;


use Groundhogg\Email;
use Groundhogg\Plugin;
use function Groundhogg\get_db;
use function Groundhogg\get_request_var;
use function Groundhogg\html;
use function Groundhogg\managed_page_url;

add_action( 'admin_print_footer_scripts', function () {
    ?>
    <style>
        .email-container ul {
            list-style-type: disc;
            margin-left: 2em;
        }

        .email-container p {
            font-size: inherit;
        }

        .email-container h1 {
            font-weight: bold;
            padding: 0;
            margin: 0.67em 0 0.67em 0;
        }

        .email-container h2 {
            font-weight: bold;
            padding: 0;
            margin: 0.83em 0 0.83em 0;
        }
    </style><?php
} );

$tabs = [
    'new-email' => __( 'New Email' ),
    'my-emails' => __( 'My Emails' ),
];

$custom_templates = get_db( 'emails' )->query( [ 'is_template' => 1 ] );

if ( !empty( $custom_templates ) ) {
    $tabs[ 'my-templates' ] = __( 'Saved Templates' );
}

$tabs = apply_filters( 'groundhogg/admin/emails/add/tabs', $tabs );

html()->tabs( $tabs );

$tab_keys = array_keys( $tabs );
$default_tab = apply_filters( 'groundhogg/admin/emails/add/default_tab', array_shift( $tab_keys ) );

$tab = get_request_var( 'tab', $default_tab );

switch ( $tab ):

    case 'my-templates':
        ?>
        <form method="post" id="poststuff">
            <?php wp_nonce_field( 'add' ); ?>
            <div class="post-box-grid">
                <?php
                foreach ( $custom_templates as $id => $email ):
                    $email = new Email( absint( $email->ID ) ); ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php esc_html_e( $email->get_subject_line() ); ?></h2>
                        <div class="inside">
                            <p><?php
                                echo ( !empty( $email->get_pre_header() ) ) ? esc_html( $email->get_pre_header() ) : '&#x2014;';
                                ?></p>
                            <iframe class="email-container" style="margin-bottom: 10px;border: 1px solid #e5e5e5;"
                                    width="100%" height="500"
                                    src="<?php echo managed_page_url( 'emails/' . $email->get_id() ); ?>"></iframe>
                            <button class="choose-template button-primary" name="email_id"
                                    value="<?php echo $email->get_id(); ?>"><?php _e( 'Start Writing', 'groundhogg-pro' ); ?></button>
                            <a class="button-secondary"
                               href="<?php printf( admin_url( 'admin.php?page=gh_emails&action=edit&email=%d' ), $email->get_id() ); ?>"><?php _e( 'Edit Template', 'groundhogg-pro' ); ?></a>
                        </div>
                    </div>
                <?php endforeach;
                ?>
            </div>
        </form>
        <?php

        break;

    case 'my-emails':

        ?>
        <style>
            .wp-filter-search {
                box-sizing: border-box;
                width: 100%;
                font-size: 16px;
                padding: 6px;
            }
        </style>
    <p></p>
        <div class="postbox">
            <div class="inside">
                <p style="float: left"><?php _e( 'Search your previous emails and use them as a starting point.', 'groundhogg-pro' ); ?></p>
                <input type="text" id="search_emails"
                       placeholder="<?php esc_attr_e( 'Type in a search term like "Special"...', 'groundhogg-pro' ); ?>"
                       class="wp-filter-search"/>
            </div>
        </div>
        <div style="text-align: center;" id="spinner">
            <span class="spinner" style="visibility: visible;float: none;"></span>
        </div>
        <form method="post" id="poststuff">
            <?php wp_nonce_field( 'add' ); ?>
            <div id="emails" class="post-box-grid">
                <!-- Only retrieve previous 20 emails.. -->
                <?php
                $emails = get_db( 'emails' )->query( [ 'limit' => 10 ] );
                foreach ( $emails as $email ):
                    $email = new Email( absint( $email->ID ) ); ?>
                    <div class="postbox">
                        <h2 class="hndle"><?php echo $email->get_title(); ?></h2>
                        <div class="inside">
                            <p><?php echo __( 'Subject: ', 'groundhogg-pro' ) . $email->get_subject_line(); ?></p>
                            <p><?php echo __( 'Pre-Header: ', 'groundhogg-pro' ) . $email->get_pre_header(); ?></p>
                            <iframe class="email-container" style="margin-bottom: 10px; border: 1px solid #e5e5e5;"
                                    width="100%"
                                    height="500"
                                    src="<?php echo managed_page_url( 'emails/' . $email->get_id() ); ?>"></iframe>
                            <button class="choose-template button-primary" name="email_id"
                                    value="<?php echo $email->get_id(); ?>"><?php _e( 'Start Writing', 'groundhogg-pro' ); ?></button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </form>
        <script type="text/javascript">
            (function ($) {

                //setup before functions
                var typingTimer;                //timer identifier
                var doneTypingInterval = 500;  //time in ms, 5 second for example
                var $search = $('#search_emails');
                var $downloads = $('#emails');
                var $spinner = $('#spinner');

                $spinner.hide();

                $search.keyup(function () {
                    clearTimeout(typingTimer);
                    typingTimer = setTimeout(ajaxCall, doneTypingInterval);
                });

                $search.keydown(function () {
                    clearTimeout(typingTimer);
                });

                function ajaxCall() {
                    $downloads.hide();
                    $spinner.show();
                    var ajaxCall = $.ajax({
                        type: "post",
                        url: ajaxurl,
                        dataType: 'json',
                        data: {action: 'get_my_emails_search_results', s: $('#search_emails').val()},
                        success: function (response) {
                            $spinner.hide();
                            $downloads.show();
                            $downloads.html(response.html);
                        }
                    });
                }
            })(jQuery);
        </script>
        <?php
        break;

    case 'new-email':
        include 'add.php';
        break;

    default:
        do_action( "groundhogg/admin/emails/add/{$tab}" );
        break;
endswitch;