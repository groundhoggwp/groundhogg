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


if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Show a welcome screen which will help users find articles and extensions that will suit their needs.
 *
 * Class Page
 * @package Groundhogg\Admin\Welcome
 */
class Welcome_Page extends Admin_Page
{
    // UNUSED FUNCTIONS
    public function help() {}
    public function screen_options() {}
    protected function add_ajax_actions() {}
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
        return apply_filters( 'groundhogg/admin/welcome/name', 'Groundhogg' );
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
        add_action( 'admin_head', array( $this, 'status_check' ) );
        add_action( 'admin_footer', array( $this, 'bg_image' ) );
    }

    /**
     * Add the page todo
     */
    public function register()
    {

        if ( is_white_labeled() ) {
            $name = white_labeled_name() ;
        } else {
            $name = 'Groundhogg';
        }

        $page = add_menu_page(
            'Groundhogg',
            $name ,
            'view_contacts',
            'groundhogg',
            [ $this, 'page' ],
            'dashicons-email-alt',
            2

        );

        $sub_page = add_submenu_page(
            'groundhogg',
            _x( 'Welcome', 'page_title', 'groundhogg' ),
            _x( 'Welcome', 'page_title', 'groundhogg' ),
            'view_contacts',
            'groundhogg',
            array($this, 'page')
        );

        $this->screen_id = $page;

        /* White label compat */
        if ( is_white_labeled() ){
            remove_submenu_page( 'groundhogg', 'groundhogg' );
        }

        add_action("load-" . $page, array($this, 'help'));
    }

    /**
     * Check a bunch of stuff.
     */
    public function status_check()
    {
        $this->check_smtp_plugins();
        $this->check_settings();
        $this->other_notices();
    }

    /**
     * Show other notices
     */
    public function other_notices()
    {
        if ( ! get_option( 'gh_guided_setup_finished', false ) ){
            $this->add_notice(
                'guided_setup', sprintf( "<a href='%s'>%s</a>", admin_url( 'admin.php?page=gh_guided_setup' ), _x( 'You have yet to complete the guided setup process.', 'notice', 'groundhogg' ) ), 'info'
            );
        }
    }

    /**
     * Check to see if some plugins are active.
     */
    public function check_smtp_plugins()
    {

        $smtp_plugins =[
            'wp-mail-smtp/wp_mail_smtp.php',
            'wp-ses/wp-ses.php',
            'wp-amazon-ses-smtp/wp-amazon-ses.php',
            'easy-wp-smtp/easy-wp-smtp.php',
            'post-smtp/postman-smtp.php',
            'wp-mail-bank/wp-mail-bank.php',
            'gmail-smtp/main.php',
            'smtp-mailer/main.php',
        ];

        $has_smtp = false;

        foreach ( $smtp_plugins as $plugin ) {
            if ( is_plugin_active( $plugin ) ) {
                $has_smtp = true;
            }
        }

        if ( Plugin::$instance->settings->is_option_enabled( 'send_with_gh_api' ) ){
	        $has_smtp = true;
        }

        if ( ! $has_smtp ){
            $this->add_notice(
                'smtp', _x( 'We recommend sending email through an SMTP service. <a target="_blank" href="https://www.groundhogg.io/downloads/email-credits/">Try ours!</a> Or look for one in <a target="_blank" href="https://en-ca.wordpress.org/plugins/search/smtp/">the WP repository.</a>', 'notice', 'groundhogg' ), 'info'
            );
        }
    }

    /**
     * Check to see if the settings are complete
     */
    public function check_settings()
    {
        if ( ! Plugin::$instance->settings->get_option( 'business_name', false ) ){
            $this->add_notice(
                'incomplete_settings', _x( 'It appears you have incomplete settings! Go to <a href="?page=gh_settings">the settings page</a> and fill out all your business information.', 'notice', 'groundhogg' ), 'warning'
            );
        }
    }


    /* Enque JS or CSS */

    public function scripts()
    {
        wp_enqueue_style( 'groundhogg-admin-welcome' );
    }

    /**
     * Returns an array of support articles...
     * Has a link to a video
     * has a link to an article on groundhogg.io
     * has a short description
     *
     * @return array
     */
    public function get_articles()
    {

        $args = wp_parse_args( array(
            'include' => [
                208,
                184,
                182,
                196,
                197,
                155,
                157,
                153,
                154
            ]
        ) );

        $url = 'https://docs.groundhogg.io/wp-json/wp/v2/docs/';
        $response = wp_remote_get( add_query_arg( $args, $url ) );

        if ( is_wp_error( $response ) ){
            return $response->get_error_message();
        }

        $docs = json_decode( wp_remote_retrieve_body( $response ) );
        $articles = apply_filters( 'wpgh_support_articles', $docs );

        return $articles;
    }

    /**
     * Convert array to html article
     *
     * @param $args array
     */
    public function article_to_html( $args=array() )
    {
        /* I'm lazy so just covert it to an object*/
        $article = (object) $args;

        ?>
        <div class="postbox">
            <?php if ( $article->title ): ?>
                <h2 class="hndle"><?php echo $article->title->rendered; ?></h2>
            <?php endif; ?>
            <div class="inside">
                <?php if ( $article->content->rendered ): ?>
                    <div class="article-description">
                        <?php echo wp_trim_words( wp_strip_all_tags( $article->content->rendered ), 30 ); ?>
                    </div>
                    <hr/>
                <?php endif; ?>
                <?php if ( $article->link ): ?>
                    <p>
                        <?php echo html()->modal_link( [
                            'title'     => $article->title->rendered,
                            'text'      => __( 'Read More...', 'groundhogg' ),
                            'footer_button_text' => __( 'Close' ),
                            'id'        => '',
                            'source'    => $article->link,
                            'height'    => 800,
                            'width'     => 1000,
                            'footer'    => 'true',
                            'class'     => 'button button-primary',
                            'preventSave'    => 'true',
                        ] ); ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php
    }

    protected function get_pointers()
    {
        return [
            [
                'id' => 'search_support',
                'screen' => $this->get_screen_id(),
                'target' => '#support-articles',
                'title' => 'Need Help?',
                'show_next' => true,
                'content' => 'Search our library of support articles and guides by using the form or check out our most popular guides below.',
                'position' => [
                    'edge' => 'left', //top, bottom, left, right
                    'align' => 'top' //top, bottom, left, right, middle
                ]
            ],
            [
                'id' => 'search_extensions',
                'screen' => $this->get_screen_id(),
                'target' => '#extensions',
                'title' => 'Need More Functionality?',
                'show_next' => false,
                'content' => 'If you need to connect to additional plugins, then you need to have a look through our list of popular extensions!',
                'position' => [
                    'edge' => 'right', //top, bottom, left, right
                    'align' => 'top' //top, bottom, left, right, middle
                ]
            ],
        ];
    }

    /**
     * Whether or not we should show the stats collection prompt
     *
     * @return bool
     */
    public function should_show_stats_collection()
    {
        $show = false;
        if ( ! Plugin::$instance->settings->is_option_enabled( 'gh_opted_in_stats_collection' ) && current_user_can( 'manage_options' ) ){
            $show = true;
        }
        return apply_filters( 'groundhogg/stats_collection/show', $show );
    }

    /**
     * The main output
     */
    public function view()
    {
        // TODO revisit actions...

        $user = wp_get_current_user();

        if ( apply_filters( 'wpgh_show_phil_on_welcome_page', true ) ): ?>
        <img class="phil" src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png'; ?>" width="340" height="340">
        <?php endif; ?>
        <div id="welcome-page" class="welcome-page">
            <div id="poststuff">
                <div class="welcome-header">
                    <h1><?php echo sprintf( __( 'Welcome %s!', 'groundhogg' ), $user->display_name ); ?></h1>
                    <div class="powered-by"><p><?php _e( 'Powered by', 'groundhogg' ); ?>&nbsp;<?php groundhogg_logo( 'white', 150 ); ?></p></div>
                </div>
                <?php $this->notices(); ?>
                <hr class="wp-header-end">
                <?php do_action( 'wpgh_welcome_page_custom_content' ); ?>
                <?php if ( apply_filters( 'wpgh_show_main_welcome_page_content', true ) ): ?>

                    <div class="col">
                        <div class="postbox" id="ghmenu">
                            <div class="inside" style="padding: 0;margin: 0">
                                <ul>
                                    <?php

                                $links = [
                                    [
                                        'icon'     => 'admin-site',
                                        'display'  => __( 'Groundhogg.io' ),
                                        'url'      => 'https://www.groundhogg.io'
                                    ],
                                    [
                                        'icon'     => 'media-document',
                                        'display'  => __( 'Documentation' ),
                                        'url'      => 'https://docs.groundhogg.io'
                                    ],
                                    [
                                        'icon'     => 'store',
                                        'display'  => __( 'Store' ),
                                        'url'      => 'https://www.groundhogg.io/downloads/'
                                    ],
                                    [
                                        'icon'     => 'admin-post',
                                        'display'  => __( 'Blog' ),
                                        'url'      => 'https://www.groundhogg.io/blog/'
                                    ],
                                    [
                                        'icon'     => 'sos',
                                        'display'  => __( 'Support Group' ),
                                        'url'      => 'https://www.groundhogg.io/fb/'
                                    ],
                                    [
                                        'icon'     => 'admin-users',
                                        'display'  => __( 'My Account' ),
                                        'url'      => 'https://www.groundhogg.io/account/'
                                    ],
                                    [
                                        'icon'     => 'location-alt',
                                        'display'  => __( 'Find a Partner' ),
                                        'url'      => 'https://www.groundhogg.io/partner/certified-partner-directory/'
                                    ],
                                ];

                                foreach ( $links as $link ){

                                    echo html()->e( 'li', [], [
                                        html()->e( 'a', [
                                            'href' => add_query_arg( [
                                                'utm_source'    => get_bloginfo(),
                                                'utm_medium'    => 'welcome-page',
                                                'utm_campaign'  => 'admin-links',
                                                'utm_content'   => strtolower( $link[ 'display' ] ),
                                            ], $link[ 'url' ] ),
                                            'target' => '_blank'
                                        ], [
                                            dashicon( $link[ 'icon' ] ),
                                            '&nbsp;',
                                            $link[ 'display' ]
                                        ] )
                                    ] );

                                }

                                ?>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="left-col col">
                        <div id="support-articles">
                            <div class="postbox support">
                                <div class="inside">
                                    <h3><?php _e( 'Support Articles', 'Groundhogg' ); ?></h3>
                                    <p><?php _e( "Don't know where to start? Checkout these articles and learn how to make Groundhogg work for you.", 'groundhogg' ); ?></p>
                                    <form action="https://docs.groundhogg.io/" method="get" target="_blank">
                                        <input style="width: 250px" type="text" name="s" id="search" class="text" placeholder="Search Docs"><?php submit_button( 'Search', 'primary', 'search', false ); ?>
                                        <a style="float: right" class="button button-secondary" href="https://docs.groundhogg.io/" target="_blank"><?php _e( 'View All!' ); ?></a>
                                    </form>
                                </div>
                            </div>
                            <?php
                            foreach ( $this->get_articles() as $article ):
                                $this->article_to_html( $article );
                            endforeach;
                            ?>
                        </div>
                    </div>
                    <div class="right-col col">
                        <div id="extensions">
                            <div class="postbox support">
                                <div class="inside">
                                    <h3><?php _e( 'Awesome Extensions', 'Groundhogg' ); ?></h3>
                                    <p><?php _e( "Need more functionality? Need to connect Groundhogg to your store? We have an extension for that!", 'groundhogg' ); ?></p>
                                    <p style="text-align: center">
                                        <a class="button button-primary" href="https://groundhogg.io/downloads/" target="_blank"><?php _e( 'View All!' ); ?></a>
                                    </p>
                                </div>
                            </div>
                            <?php
                            foreach ( License_Manager::get_extensions() as $extension ):
                                License_Manager::extension_to_html( $extension );
                            endforeach;
                            ?>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
        <?php

    }

    public function page()
    {

        if ( method_exists( $this, $this->get_current_action() ) ){
            call_user_func( [ $this, $this->get_current_action() ] );
        } else if ( has_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_current_action()}" ) ) {
            do_action( "groundhogg/admin/{$this->get_slug()}/display/{$this->get_current_action()}", $this );
        } else {
            call_user_func( [ $this, 'view' ] );
        }

    }

    /**
     * Display background Image on Welcome Page
     */
    public function bg_image()
    {
        ?>
<style>
    #wpwrap {
        background: linear-gradient( rgba(219, 116, 26, 0.45), rgba(219, 116, 26, 0.45)), url( '<?php echo apply_filters( 'wpgh_welcome_bg_image', GROUNDHOGG_ASSETS_URL . 'images/groundhogg-bg.jpg' ); ?>' );
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        position: relative;
    }
</style>
<?php
    }

}