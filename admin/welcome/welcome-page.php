<?php

namespace Groundhogg\Admin\Welcome;

use Groundhogg\Admin\Admin_Page;
use function Groundhogg\dashicon;
use function Groundhogg\groundhogg_logo;
use function Groundhogg\html;
use function Groundhogg\is_white_labeled;
use Groundhogg\License_Manager;
use Groundhogg\Plugin;
use function Groundhogg\white_labeled_name;


if (!defined('ABSPATH')) exit;

/**
 * Show a welcome screen which will help users find articles and extensions that will suit their needs.
 *
 * Class Page
 * @package Groundhogg\Admin\Welcome
 */
class Welcome_Page extends Admin_Page
{
    // UNUSED FUNCTIONS
    public function help()
    {
    }

    public function screen_options()
    {
    }

    protected function add_ajax_actions()
    {
    }

    /**
     * Get the menu order between 1 - 99
     *
     * @return int
     */
    public function get_priority()
    {
        return 1;
    }

    /**
     * Get the page slug
     *
     * @return string
     */
    public function get_slug()
    {
        return 'groundhogg';
    }

    /**
     * Get the menu name
     *
     * @return string
     */
    public function get_name()
    {
        return apply_filters('groundhogg/admin/welcome/name', 'Groundhogg');
    }

    /**
     * The required minimum capability required to load the page
     *
     * @return string
     */
    public function get_cap()
    {
        return 'view_contacts';
    }

    /**
     * Get the item type for this page
     *
     * @return mixed
     */
    public function get_item_type()
    {
        return null;
    }

    /**
     * Adds additional actions.
     *
     * @return void
     */
    protected function add_additional_actions()
    {

    }

    /**
     * Add the page todo
     */
    public function register()
    {

        if (is_white_labeled()) {
            $name = white_labeled_name();
        } else {
            $name = 'Groundhogg';
        }

        $page = add_menu_page(
            'Groundhogg',
            $name,
            'view_contacts',
            'groundhogg',
            [$this, 'page'],
            'dashicons-email-alt',
            2

        );

        $sub_page = add_submenu_page(
            'groundhogg',
            _x('Welcome', 'page_title', 'groundhogg'),
            _x('Welcome', 'page_title', 'groundhogg'),
            'view_contacts',
            'groundhogg',
            array($this, 'page')
        );

        $this->screen_id = $page;

        /* White label compat */
        if (is_white_labeled()) {
            remove_submenu_page('groundhogg', 'groundhogg');
        }

        add_action("load-" . $page, array($this, 'help'));
    }

    /* Enque JS or CSS */
    public function scripts()
    {
        wp_enqueue_style('groundhogg-admin-welcome');
    }


    /**
     * The main output
     */
    public function view()
    {
        // TODO revisit actions...

        $user = wp_get_current_user();
        ?>
        <div id="welcome-page" class="welcome-page">
            <div id="poststuff">
                <div class="welcome-header">
                    <h1><?php echo sprintf(__('Welcome %s!', 'groundhogg'), $user->display_name); ?></h1>
                    <div class="powered-by"><p><?php _e('Powered by', 'groundhogg'); ?>
                            &nbsp;<?php groundhogg_logo('black', 150); ?></p></div>
                </div>
                <?php $this->notices(); ?>
                <hr class="wp-header-end">
                <div class="col">
                    <div class="postbox" id="ghmenu">
                        <div class="inside" style="padding: 0;margin: 0">
                            <ul>
                                <?php

                                $links = [
                                    [
                                        'icon' => 'admin-site',
                                        'display' => __('Groundhogg.io'),
                                        'url' => 'https://www.groundhogg.io'
                                    ],
                                    [
                                        'icon' => 'media-document',
                                        'display' => __('Documentation'),
                                        'url' => 'https://docs.groundhogg.io'
                                    ],
                                    [
                                        'icon' => 'store',
                                        'display' => __('Store'),
                                        'url' => 'https://www.groundhogg.io/downloads/'
                                    ],
                                    [
                                        'icon' => 'admin-post',
                                        'display' => __('Blog'),
                                        'url' => 'https://www.groundhogg.io/blog/'
                                    ],
                                    [
                                        'icon' => 'sos',
                                        'display' => __('Support Group'),
                                        'url' => 'https://www.groundhogg.io/fb/'
                                    ],
                                    [
                                        'icon' => 'admin-users',
                                        'display' => __('My Account'),
                                        'url' => 'https://www.groundhogg.io/account/'
                                    ],
                                    [
                                        'icon' => 'location-alt',
                                        'display' => __('Find a Partner'),
                                        'url' => 'https://www.groundhogg.io/partner/certified-partner-directory/'
                                    ],
                                ];

                                foreach ($links as $link) {

                                    echo html()->e('li', [], [
                                        html()->e('a', [
                                            'href' => add_query_arg([
                                                'utm_source' => get_bloginfo(),
                                                'utm_medium' => 'welcome-page',
                                                'utm_campaign' => 'admin-links',
                                                'utm_content' => strtolower($link['display']),
                                            ], $link['url']),
                                            'target' => '_blank'
                                        ], [
                                            dashicon($link['icon']),
                                            '&nbsp;',
                                            $link['display']
                                        ])
                                    ]);

                                }

                                ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="left-col col">
                    <div class="postbox">
                        <h3></h3>
                        <div class="inside">

                        </div>
                    </div>
                </div>
                <div class="right-col col">

                </div>
            </div>
        </div>
        <?php
    }

}