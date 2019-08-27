<?php

namespace Groundhogg;


class Upgrade_Notice
{

    public function __construct()
    {

        add_action('admin_notices', [$this, 'show_upgrade_notice_request']);
        add_action('wp_ajax_groundhogg_dismiss_upgrade_notice', [$this, 'dismiss_upgrade_notice']);

    }

    public function show_upgrade_notice_request()
    {
        if (!current_user_can('administrator')) {
            return;
        }

        if ( ! get_transient( 'groundhogg_upgrade_notice_request_active' ) ) {
            return;
        }

        $message = sprintf(
            esc_html__('You are now using Groundhogg 2.0! We promise better usability, performance, efficiency and business happiness with the newest upgrades. %s', 'groundhogg'),
            html()->e('a', ['class' => '', 'style' => ['color' => 'green'], 'href' => 'https://www.groundhogg.io/new-in-2-0', 'target' => '_blank'], __("What's new in 2.0?", 'groundhogg'))
        );

        $html_message = sprintf('<div class="upgrade-notice notice notice-info is-dismissible">%s</div>', wpautop($message));

        echo wp_kses_post($html_message);

        ?>
        <script>
            (function ($) {
                $(function () {

                    $('.notice-dismiss').click(function (e) {

                        var $notice = $(this).closest('.notice');

                        if ($notice.hasClass('upgrade-notice')) {
                            e.preventDefault();

                            adminAjaxRequest({action: 'groundhogg_dismiss_upgrade_notice'}, function (response) {
                                console.log(response)
                            });
                        }
                    })
                });
            })(jQuery)
        </script>
        <?php

    }

    public function dismiss_upgrade_notice()
    {

        if (!current_user_can('administrator')) {
            return;
        }

        delete_transient('groundhogg_upgrade_notice_request_active');

        wp_send_json_success();
    }

}