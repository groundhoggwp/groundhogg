<?php
namespace Groundhogg\Admin\Welcome;

use Groundhogg\Admin\Admin_Page;
use Groundhogg\Plugin;


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
        add_action( 'admin_init', array( $this, 'status_check' ) );
        add_action( 'admin_footer', array( $this, 'bg_image' ) );
    }

    /**
     * Add the page todo
     */
    public function register()
    {
        $page = add_menu_page(
            'Groundhogg',
            'Groundhogg',
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

        /* White label compat */
        if ( apply_filters( 'wpgh_remove_welcome_page', false ) ){
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
                        <a class="button button-primary" href="<?php echo esc_url_raw( $article->link ); ?>" target="_blank"><?php _e( 'Read More...' ); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php

    }

    /**
     * @param array $args
     * @return array|mixed|object
     */
    public function get_store_products( $args=array() )
    {
        $args = wp_parse_args( $args, array(
            //'category' => 'templates',
            'category' => '',
            'tag'      => '',
            's'        => '',
            'page'     => '',
            'number'   => '-1'
        ) );
        $url = 'https://www.groundhogg.io/edd-api/v2/products/';
        $response = wp_remote_get( add_query_arg( $args, $url ) );
        if ( is_wp_error( $response ) ){
            return $response->get_error_message();
        }
        $products = json_decode( wp_remote_retrieve_body( $response ) );
        return $products;
    }

    /**
     * Get a list of extensions to promote on the welcome page
     *
     * @return array
     */
    public function get_extensions()
    {
        $products = $this->get_store_products( array(
            'category' => [ 16, 9 ],
        ) );

        $products = $products->products;
        shuffle( $products );
        $rands = array_rand( $products, 4 );
        $extensions = [];

        foreach ( $rands as $rand ){
            $extensions[] = $products[ $rand ];
        }

        $extensions = apply_filters( 'wpgh_extension_ads', $extensions );
        return $extensions;
    }

    /**
     * Convert array to html article
     *
     * @param $args array
     */
    public function extension_to_html( $args=array() )
    {
        /* I'm lazy so just covert it to an object*/
        $extension = (object) $args;

        ?>
        <div class="postbox">
            <?php if ( $extension->info->title ): ?>
                <h2 class="hndle"><?php echo $extension->info->title; ?></h2>
            <?php endif; ?>
            <div class="inside">
                <?php if ( $extension->info->thumbnail ): ?>
                    <div class="img-container">
                        <a href="<?php echo $extension->info->link; ?>" target="_blank">
                            <img src="<?php echo $extension->info->thumbnail; ?>" style="width: 100%;max-width: 100%;">
                        </a>
                    </div>
                <hr/>
                <?php endif; ?>
                <?php if ( $extension->info->excerpt ): ?>
                    <div class="article-description">
                        <?php echo $extension->info->excerpt; ?>
                    </div>
                <hr/>
                <?php endif; ?>
                <?php if ( $extension->info->link ): ?>
                    <p>
                        <?php $pricing = (array) $extension->pricing;
                        if (count($pricing) > 1) {

                            $price1 = min($pricing);
                            $price2 = max($pricing);

                            ?>
                            <a class="button-primary" target="_blank"
                               href="<?php echo $extension->info->link; ?>"> <?php printf( _x('Buy Now ($%s - $%s)', 'action', 'groundhogg'), $price1, $price2 ); ?></a>
                            <?php
                        } else {

                            $price = array_pop($pricing);

                            if ($price > 0.00) {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $extension->info->link; ?>"> <?php printf( _x( 'Buy Now ($%s)', 'action','groundhogg' ), $price ); ?></a>
                                <?php
                            } else {
                                ?>
                                <a class="button-primary" target="_blank"
                                   href="<?php echo $extension->info->link; ?>"> <?php _ex('Download', 'action', 'groundhogg'); ?></a>
                                <?php
                            }
                        }

                        ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php

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

        $user = wp_get_current_user();
        ?>

        <?php if ( apply_filters( 'wpgh_show_phil_on_welcome_page', true ) ): ?>
        <img class="phil" src="<?php echo GROUNDHOGG_ASSETS_URL . 'images/phil-340x340.png'; ?>" width="340" height="340">
    <?php endif; ?>
        <div id="welcome-page" class="welcome-page">
            <div id="poststuff">
                <div class="welcome-header">
                    <h1><?php echo sprintf( __( 'Welcome %s!', 'groundhogg' ), $user->display_name ); ?></h1>
                </div>
                <?php $this->notices(); ?>
                <?php do_action( 'wpgh_welcome_page_custom_content' ); ?>
                <?php if ( apply_filters( 'wpgh_show_main_welcome_page_content', true ) ): ?>
                    <?php if ( $this->should_show_stats_collection() ): ?>
                        <div class="col">
                            <div class="postbox stats-collection">
                                <div class="inside">
                                    <h3><?php _e( 'GET 30% OFF WHEN YOU HELP US MAKE GROUNDHOGG BETTER', 'Groundhogg' ); ?></h3>
                                    <p><?php _e( "Want sweet discounts and to help us make Groundhogg even better? When you optin to our stats collection you will get a <b>30% discount off</b> any premium extension or subscription in our store by sharing <b>anonymous</b> data about your site. You can opt out any time from the settings page. Your email address & display name will be collected so we can send you the discount code.", 'groundhogg' ); ?></p>
                                    <p style="text-align: center">
                                        <a class="button button-primary" href="<?php echo wp_nonce_url( admin_url( 'admin.php?page=groundhogg&action=opt_in_to_stats' ), 'opt_in_to_stats' ); ?>" ><?php _e( 'Yes, I want to help make Groundhogg better!' ); ?></a>
                                    </p>
                                    <p style="text-align: center">
                                        <a href="https://www.groundhogg.io/privacy-policy/#usage-tracking" target="_blank"><?php _e( 'Learn more', 'groundhogg' ); ?></a>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
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
                            foreach ( $this->get_extensions() as $extension ):
                                $this->extension_to_html( $extension );
                            endforeach;
                            ?>
                        </div>
                    </div>

                <?php endif; ?>

            </div>
        </div>
        <?php

    }

    /**
     * Display background Image on Welcome Page
     */
    public function bg_image()
    {
        ?>
<style>
    #wpwrap {
        background-image: url( '<?php echo apply_filters( 'wpgh_welcome_bg_image', GROUNDHOGG_ASSETS_URL . 'images/groundhogg-bg.jpg' ); ?>' );
        background-position: center;
        background-repeat: no-repeat;
        background-size: cover;
        position: relative;
    }
</style>
<?php
    }

}