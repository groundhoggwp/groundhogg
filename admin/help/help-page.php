<?php

namespace Groundhogg\Admin\Help;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Admin\Tabbed_Admin_Page;
use Groundhogg\Plugin;
use function Groundhogg\dashicon;
use function Groundhogg\dashicon_e;
use function Groundhogg\get_request_var;
use function Groundhogg\get_store_products;
use function Groundhogg\html;
use function Groundhogg\is_pro_features_active;

class Help_Page extends Tabbed_Admin_Page
{

    /**
     * Add Ajax actions...
     */
    protected function add_ajax_actions()
    {
        add_action('wp_ajax_groundhogg_get_docs', [$this, 'get_docs_ajax']);
    }

    /**
     * Adds additional actions.
     *
     * @return mixed
     */
    protected function add_additional_actions()
    {
        if (!is_pro_features_active()) {
            add_action('admin_print_styles', function () {
                ?>
                <style>
                    .nav-tab-wrapper a[href="?page=gh_help&tab=support"]{
                        color: #DB741A;
                    }
                    .nav-tab-wrapper a[href="?page=gh_help&tab=support"] .dashicons{
                        margin-right: 4px;
                    }
                </style>
                <?php
            });
        }
    }

    /**
     * Get the page slug
     *
     * @return string
     */
    public function get_slug()
    {
        return 'gh_help';
    }

    /**
     * Get the menu name
     *
     * @return string
     */
    public function get_name()
    {
        return __('Help');
    }

    /**
     * The required minimum capability required to load the page
     *
     * @return string
     */
    public function get_cap()
    {
        return 'edit_contacts';
    }

    public function get_priority()
    {
        return 998;
    }

    /**
     * Get the item type for this page
     *
     * @return mixed
     */
    public function get_item_type()
    {
        // TODO: Implement get_item_type() method.
    }

    /**
     * Enqueue any scripts
     */
    public function scripts()
    {
        wp_enqueue_style('groundhogg-admin');
    }

    /**
     * Add any help items
     *
     * @return mixed
     */
    public function help()
    {
        // TODO: Implement help() method.
    }

    /**
     * array of [ 'name', 'slug' ]
     *
     * @return array[]
     */
    protected function get_tabs()
    {
        $tabs = [
            [
                'name' => __('Documentation', 'groundhogg'),
                'slug' => 'docs'
            ],
            [
                'name' => __('Support Ticket', 'groundhogg'),
                'slug' => 'support'
            ],
//            [
//                'name' => __('Support Group', 'groundhogg'),
//                'slug' => 'fb'
//            ]
        ];

        if (!is_pro_features_active()) {
            $tabs[1]['name'] = dashicon('star-filled') . $tabs[1]['name'];
        }

        return $tabs;
    }

    public function get_docs()
    {

        $args = ['per_page' => 15];

        $search = urlencode(get_request_var('search'));

        if (!empty($search)) {
            $args['search'] = $search;
        }

        $url = 'https://docs.groundhogg.io/wp-json/wp/v2/docs/';
        $response = wp_remote_get(add_query_arg($args, $url));

        if (is_wp_error($response)) {
            return $response->get_error_message();
        }

        $docs = json_decode(wp_remote_retrieve_body($response));
        $articles = apply_filters('groundhogg/admin/help/support', $docs);

        return $articles;
    }

    public function display_docs()
    {

        $docs = $this->get_docs();

        if (empty($docs)) {
            ?>
            <p style="text-align: center;font-size: 24px;"><?php _ex('Sorry, no docs were found.', 'notice', 'groundhogg'); ?></p> <?php
            return;
        }

        foreach ($docs as $doc) {
            $this->doc_to_html($doc);
        }
    }

    public function get_docs_ajax()
    {

        ob_start();

        $this->display_docs();

        $html = ob_get_clean();

        wp_send_json(['html' => $html]);
    }

    /**
     * Convert array to html article
     *
     * @param $args array
     */
    public function doc_to_html($args = array())
    {
        /* I'm lazy so just covert it to an object*/
        $article = (object)$args;

        ?>
        <div class="postbox">
            <?php if ($article->title): ?>
                <h2 class="hndle"><?php echo $article->title->rendered; ?></h2>
            <?php endif; ?>
            <div class="inside">
                <?php if ($article->content->rendered): ?>
                    <div class="article-description">
                        <?php echo wp_trim_words(wp_strip_all_tags($article->content->rendered), 30); ?>
                    </div>
                    <hr/>
                <?php endif; ?>
                <?php if ($article->link): ?>
                    <p>
                        <?php echo html()->modal_link([
                            'title' => $article->title->rendered,
                            'text' => __('Read More...', 'groundhogg'),
                            'footer_button_text' => __('Close'),
                            'id' => '',
                            'source' => $article->link,
                            'height' => 800,
                            'width' => 1000,
                            'footer' => 'true',
                            'class' => 'button button-primary',
                            'preventSave' => 'true',
                        ]); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php
    }

    public function docs_view()
    {
        ?>
        <style>
            .wp-filter-search {
                box-sizing: border-box;
                width: 100%;
                font-size: 16px;
                padding: 6px;
            }
        </style>
        <div id="poststuff">
            <div class="postbox">
                <div class="inside">
                    <p style="float: left"><?php _e('Search the documentation for answers and tutorials.', 'groundhogg'); ?></p>
                    <form class="search-form" method="get">
                        <input type="text" id="search"
                               placeholder="<?php esc_attr_e('Type in a search term like "How to..."', 'groundhogg'); ?>"
                               class="wp-filter-search"/>
                    </form>
                </div>
            </div>
            <div style="text-align: center;" id="spinner">
                <span class="spinner" style="float: none; visibility: visible"></span>
            </div>

            <style>

            </style>
            <div id="docs" class="post-box-grid">
                <?php $this->display_docs(); ?>
            </div>
        </div>
        <script type="text/javascript">
            (function ($) {

                //setup before functions
                var typingTimer;                //timer identifier
                var doneTypingInterval = 500;  //time in ms, 5 second for example
                var $search = $('#search');
                var $docs = $('#docs');
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
                    $docs.hide();
                    $spinner.show();
                    var ajaxCall = $.ajax({
                        type: "post",
                        url: ajaxurl,
                        dataType: 'json',
                        data: {action: 'groundhogg_get_docs', search: $search.val()},
                        success: function (response) {
                            $spinner.hide();
                            $docs.show();
                            $docs.html(response.html);
                        }
                    });
                }
            })(jQuery);
        </script>
        <?php
    }


    public function support_view()
    {

        if ( ! is_pro_features_active() ):

            $pricing_url = add_query_arg([
                'utm_source' => 'wp-dash',
                'utm_medium' => 'support',
                'utm_campaign' => 'go-pro',
                'utm_content' => 'button',
            ], 'https://www.groundhogg.io/pricing/');

            $discount = get_user_meta(wp_get_current_user()->ID, 'gh_free_extension_discount', true);

            if ($discount) {
                $pricing_url = add_query_arg(['discount' => $discount], $pricing_url);
            }

            ?>
            <style>
                .support-ad {
                    display: block;
                    max-width: 500px;
                    margin: 60px auto;
                    background: #FFF;
                    padding: 30px;
                    box-sizing: border-box;
                    border: 1px solid #e5e5e5;
                }

                .support-ad h1 {
                    text-align: center;
                    font-size: 32px;
                }

                .support-ad p {
                    font-size: 16px;
                }

            </style>
            <div class="support-ad">

                <h1><b>Need Support?</b></h1>
                <p>Unlock <b>premium technical support</b> when you upgrade to any premium plan.</p>
                <p>There are many benefits to upgrading, like advanced integrations with your favorite tools, more
                    features, and of course premium support.</p>
                <p style="text-align: center">
                    <a id="pricing-button" class="button-primary big-button" href="<?php echo esc_url($pricing_url); ?>"
                       target="_blank"><?php dashicon_e('star-filled');
                        _e('Yes, I Want To Upgrade!'); ?></a>
                </p>
                <p style="text-align: center">
                    <a href="https://www.groundhogg.io/fb/" target="_blank"><?php _e( 'Post to Facebook.', 'groundhogg' ); ?></a>
                </p>
            </div>
        <?php

        endif;

        do_action( 'groundhogg/support_ticket_form' );
    }

    /**
     * @var int
     */
    protected $ticket_id = 0;

    /**
     * Create a support ticket
     */
    public function process_support_submit_ticket()
    {
        add_action( 'groundhogg/create_support_ticket/failed', [ $this, 'listen_for_support_error' ] );

        do_action( 'groundhogg/create_support_ticket' );

        if ( $this->has_errors() ){
            return $this->get_last_error();
        }

        return false;
    }

    /**
     * @param $error \WP_Error
     */
    public function listen_for_support_error( $error )
    {
        $this->add_error( $error );
    }

    /**
     * Output the basic view.
     *
     * @return mixed
     */
    public function view()
    {
        // TODO: Implement view() method.
    }
}
