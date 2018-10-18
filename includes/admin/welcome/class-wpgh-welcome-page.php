<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2018-10-18
 * Time: 3:30 PM
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

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_welcome' ){

            $this->notices = WPGH()->notices;

            add_action( 'wp_enqueue_scripts', array( $this, 'scripts' ) );

        }
    }

    /**
     * Add the page
     */
    public function register()
    {

        $page_title = 'welcome';
        $menu_title = 'Groundhogg';
        $capability = 'view_contacts';
        $slug = 'groundhogg';
        $callback = array( $this, 'page' );
        $icon = 'dashicons-email-alt';
        $position = 2;

        $page = add_menu_page(
            $page_title,
            $menu_title,
            $capability,
            $slug,
            $callback,
            $icon,
            $position
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
        //todo
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
                'vidId' => '',
                'link'  => 'https://groundhogg.io'
            ),
            array(
                'title' => __( "Managing Contacts", 'groundhogg' ),
                'desc'  => __( "Learn about managing and segmenting your contacts so you can keep things in order.", 'groundhogg' ),
                'vidId' => '',
                'link'  => ''
            ),
            array(
                'title' => __( "Create Your First Funnel", 'groundhogg' ),
                'desc'  => __( "Dive into funnel building and using our suite of tools to build automated customer journeys.", 'groundhogg' ),
                'vidId' => '',
                'link'  => ''
            ),
            array(
                'title' => __( "", 'groundhogg' ),
                'desc'  => __( "", 'groundhogg' ),
                'vidId' => '',
                'link'  => ''
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
                    <iframe src="https://player.vimeo.com/video/<?php echo $article->vidId; ?>" width="640" height="360" frameborder="0" webkitallowfullscreen mozallowfullscreen allowfullscreen></iframe>
                </div>
                <?php endif; ?>
                <?php if ( $article->desc ): ?>
                <div class="article-description">
                    <?php echo $article->desc; ?>
                </div>
                <?php endif; ?>
                <?php if ( $article->link ): ?>
                <p class="submit">
                    <a class="button" href="<?php echo esc_url_raw( $article->link ); ?>" target="_blank"><?php _e( 'Read More...' ); ?></a>
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
                'title' => '',
                'desc'  => '',
                'img'   => '',
                'link'  => ''
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
                <?php endif; ?>
                <?php if ( $extension->desc ): ?>
                    <div class="article-description">
                        <?php echo $extension->desc; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $extension->link ): ?>
                    <p class="submit">
                        <a class="button" href="<?php echo esc_url_raw( $extension->link ); ?>" target="_blank"><?php _e( 'Buy Now!' ); ?></a>
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

        <div id="poststuff">
            <div class="welcome-header">
                <?php echo sprintf( __( 'Welcome %s', 'groundhogg' ), $user->first_name ); ?>
            </div>
            <div class="left-col">

                <div id="support-articles">

                    <?php

                    foreach ( $this->get_articles() as $article ):

                        $this->article_to_html( $article );

                    endforeach;

                    ?>

                </div>
            </div>
            <div class="right-col">
                <div id="extensions">

                    <?php

                    foreach ( $this->get_extensions() as $extension ):

                        $this->extension_to_html( $extension );

                    endforeach;

                    ?>

                </div>
            </div>
        </div>
        <?php
    }

}