<?php
/**
 * Welcome Page
 *
 * Introducing the welcome page, the user is directly brought here upon activating Groundhogg.
 * It includes links to tutorials and extensions.
 *
 * @package     Admin
 * @subpackage  Admin/Welcome
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Welcome_Page
{

    /**
     * @var WPGH_Notices
     */
    public $notices;

    function __construct()
    {

        add_action('admin_menu', array($this, 'register'));

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'groundhogg' ){

            $this->notices = WPGH()->notices;

            $this->notices->add(
              'wip', __( 'This page is currently a work in progress! You should move on from it for now. Complete videos, links, and extensions coming soon.' ), 'info'
            );

            add_action( 'admin_init', array( $this, 'status_check' ) );

            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );

        }
    }

    public function status_check()
    {
        $this->check_settings();
        $this->check_funnels();
        $this->check_settings();
    }


    /**
     * Check to see if the settings are complete
     */
    public function check_settings()
    {
        if ( ! get_option( 'gh_business_name' ) ){
            $this->notices->add(
                'incomplete_settings', __( 'It appears you have incomplete settings! Go to <a href="?page=gh_settings">the settings page</a> and fill out all your business information.' ), 'warning'
            );
        }
    }

    /**
     * Check to see if there are any active funnels
     */
    public function check_funnels()
    {
        $funnels = WPGH()->funnels->get_funnels();

        if ( empty( $funnels ) ){
            $this->notices->add(
                'no_active_funnels', __( 'You have no active funnels! Go to <a href="?page=gh_funnels&action=add">the funnels page</a> and create your first funnel!' ), 'warning'
            );
        }

    }

    public function check_contacts()
    {
        $contacts = WPGH()->contacts->count();

        if ( $contacts < 10 ){
            $this->notices->add(
                'no_contacts', __( 'Seams like you need some more contacts. Go to the <a href="?page=gh_settings&tab=tools">tools area</a> and import your mailing list!' ), 'warning'
            );
        }

    }

    /**
     * Add the page
     */
    public function register()
    {

        $page = add_menu_page(
            'Welcome',
            'Groundhogg',
            'view_contacts',
            'groundhogg',
            array( $this, 'page' ),
            'dashicons-email-alt',
            2
        );

        add_action("load-" . $page, array($this, 'help'));

    }

    /**
     * Add the help bar
     */
    public function help()
    {
        //todo
    }


    /* Enque JS or CSS */
    public function scripts()
    {
        wp_enqueue_style( 'welcome-page', WPGH_ASSETS_FOLDER . 'css/admin/welcome.css', filemtime( WPGH_PLUGIN_DIR . 'assets/css/admin/welcome.css' ) );
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
        $articles = array(
            array(
                'title' => __( 'What\'s Included?', 'groundhogg' ),
                'desc'  => __( 'Get a general overview of all Groundhogg\'s features.', 'groundhogg' ),
                'vidId' => '235215203',
                'link'  => 'https://groundhogg.io'
            ),
            array(
                'title' => __( "Managing Contacts", 'groundhogg' ),
                'desc'  => __( "Learn about managing and segmenting your contacts so you can keep things in order.", 'groundhogg' ),
                'vidId' => '235215203',
                'link'  => 'https://groundhogg.io'
            ),
            array(
                'title' => __( "Create Your First Funnel", 'groundhogg' ),
                'desc'  => __( "Dive into funnel building and using our suite of tools to build automated customer journeys.", 'groundhogg' ),
                'vidId' => '235215203',
                'link'  => 'https://groundhogg.io'
            ),
            array(
                'title' => __( "How To Build Forms", 'groundhogg' ),
                'desc'  => __( "Groundhogg comes with a fairly versatile, but not obvious shortcode powered form builder.", 'groundhogg' ),
                'vidId' => '235215203',
                'link'  => 'https://groundhogg.io'
            ),
        );

        $articles = apply_filters( 'wpgh_support_articles', $articles );

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
            <h2 class="hndle"><?php echo $article->title; ?></h2>
            <?php endif; ?>
            <div class="inside">
                <?php if ( $article->vidId ): ?>
                <div class="video-container">
                    <iframe src="https://player.vimeo.com/video/<?php echo $article->vidId; ?>" width="444" height="249" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
                </div>

                <hr/>
                <?php endif; ?>
                <?php if ( $article->desc ): ?>
                <div class="article-description">
                    <?php echo $article->desc; ?>
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
     * Get a list of extensions to promote on the welcome page
     *
     * @return array
     */
    public function get_extensions()
    {
        $extensions = array(
            array(
                'title' => 'Social Proof',
                'desc'  => 'Increase your conversion rate by showing how many people are engaging with your business. Show engagement by connecting your Proof to any funnel.',
                'img'   => 'https://www.groundhogg.io/wp-content/uploads/edd/2018/10/social-proof.png',
                'link'  => 'https://www.groundhogg.io/downloads/proof/'
            ),
            array(
                'title' => 'Email Countdown Timer',
                'desc'  => 'Create more engagement from emails by adding countdown timers to your emails.',
                'img'   => 'https://www.groundhogg.io/wp-content/uploads/edd/2018/10/countdown-timers.png',
                'link'  => 'https://www.groundhogg.io/downloads/countdown/'
            ),
            array(
                'title' => 'Contracts',
                'desc'  => 'Have your contacts sign legally binding contracts through Groundhogg. No third party apps required.',
                'img'   => 'https://www.groundhogg.io/wp-content/uploads/edd/2018/10/contracts.png',
                'link'  => 'https://www.groundhogg.io/downloads/contracts/'
            ),
            array(
                'title' => 'Contact Form 7',
                'desc'  => 'Start collecting lead information through Contact Form 7, no setup required. Works instantly!',
                'img'   => 'https://www.groundhogg.io/wp-content/uploads/edd/2018/10/contact-form-7.png',
                'link'  => 'https://www.groundhogg.io/downloads/contact-form-7/'
            ),

            array(
                'title' => 'Easy Digital Downloads',
                'desc'  => 'Connect Groundhogg to Easy Digital Downloads and increase your sales with abandonment funnels.',
                'img'   => 'https://www.groundhogg.io/wp-content/uploads/edd/2018/10/edd-722x361.png',
                'link'  => 'https://www.groundhogg.io/downloads/easy-digital-downloads/'
            ),
        );

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
            <?php if ( $extension->title ): ?>
                <h2 class="hndle"><?php echo $extension->title; ?></h2>
            <?php endif; ?>
            <div class="inside">
                <?php if ( $extension->img ): ?>
                    <div class="img-container">
                        <img src="<?php echo $extension->img; ?>" style="width: 100%;max-width: 100%;">
                    </div>
                <hr/>
                <?php endif; ?>
                <?php if ( $extension->desc ): ?>
                    <div class="article-description">
                        <?php echo $extension->desc; ?>
                    </div>
                <hr/>
                <?php endif; ?>
                <?php if ( $extension->link ): ?>
                    <p>
                        <a class="button button-primary" href="<?php echo esc_url_raw( $extension->link ); ?>" target="_blank"><?php _e( 'Buy Now!' ); ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <?php

    }

    /**
     * The main output
     */
    public function page()
    {

        $user = wp_get_current_user();

        ?>
        <img class="phil" src="<?php echo WPGH_ASSETS_FOLDER . 'images/phil-340x340.png'; ?>" width="340" height="340">
        <div id="welcome-page" class="welcome-page">
            <div id="poststuff">
                <div class="welcome-header">
                    <h1><?php echo sprintf( __( 'Welcome %s!', 'groundhogg' ), $user->first_name ); ?></h1>
                </div>
                <?php $this->notices->notices(); ?>

<!--                <div id="main">-->
<!--                    <div class="postbox support progress">-->
<!--                        <div class="inside">-->
<!--                            <h2>--><?php //_e( 'Setup Progress', 'Groundhogg' ); ?><!--</h2>-->
<!--                            <hr/>-->
<!---->
<!--                        </div>-->
<!--                    </div>-->
<!--                </div>-->
                <div class="left-col col">

                    <div id="support-articles">

                        <div class="postbox support">
                            <div class="inside">
                                <h3><?php _e( 'Support Articles', 'Groundhogg' ); ?></h3>
                                <p><?php _e( "Don't know where to start? Checkout these articles and learn how to make Groundhogg work for you.", 'groundhogg' ); ?></p>
                                <p style="text-align: center">
                                    <a class="button button-primary" href="https://www.groundhogg.io/category/support/" target="_blank"><?php _e( 'View All!' ); ?></a>
                                </p>
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
            </div>
        </div>
        <?php
    }

}