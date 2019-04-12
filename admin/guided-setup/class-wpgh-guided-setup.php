<?php
/**
 * Guided Setup
 *
 * An automated and simple experience that allows users to setup Groundhogg in a few steps.
 *
 * @package     Admin
 * @subpackage  Admin/Guided Setup
 * @author      Adrian Tobey <info@groundhogg.io>
 * @copyright   Copyright (c) 2018, Groundhogg Inc.
 * @license     https://opensource.org/licenses/GPL-3.0 GNU Public License v3
 * @since       File available since Release 0.9
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Guided_Setup
{

    /**
     * @var WPGH_Notices
     */
    public $notices;

    /**
     * @var WPGH_Guided_Setup_Step
     */
    private $current_step;

    /**
     * @var WPGH_Guided_Setup_Step[]
     */
    private $steps;

    function __construct()
    {

        /* Welcome page always comes first */
        add_action( 'admin_menu', array( $this, 'register' ), 1 );

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_guided_setup' ){
            $this->notices = WPGH()->notices;
            add_action( 'admin_enqueue_scripts', array( $this, 'scripts' ) );
            add_action( 'init', array( $this, 'register_steps' ) );
            add_action( 'admin_init', array( $this, 'load_dependencies' ) );
        }
    }

    /**
     * Add the page
     */
    public function register()
    {

        $sub_page = add_submenu_page(
            'options.php',
            _x( 'Guided Setup', 'page_title', 'groundhogg' ),
            _x( 'Guided Setup', 'page_title', 'groundhogg' ),
            'manage_options',
            'gh_guided_setup',
            array($this, 'page')
        );

        add_action("load-" . $sub_page, array($this, 'help'));

    }

    /**
     * Load dependencies for current step
     */
    public function load_dependencies()
    {
        if ( $this->get_current_step_id() ){
            $this->get_current_step()->load_dependencies();
        }
    }

    public function register_steps()
    {

        include_once dirname(__FILE__) . '/class-wpgh-guided-setup-step.php';
        include_once dirname(__FILE__) . '/steps/class-wpgh-guided-setup-step-business.php';
        include_once dirname(__FILE__) . '/steps/class-wpgh-guided-setup-step-compliance.php';
        include_once dirname(__FILE__) . '/steps/class-wpgh-guided-setup-step-email.php';
        include_once dirname(__FILE__) . '/steps/class-wpgh-guided-setup-step-import.php';
        include_once dirname(__FILE__) . '/steps/class-wpgh-guided-setup-step-other.php';
        include_once dirname(__FILE__) . '/steps/class-wpgh-guided-setup-step-finished.php';

        $this->steps = apply_filters( 'groundhogg/guided_setup/steps', [
            new WPGH_Guided_Setup_Step_Business(),
            new WPGH_Guided_Setup_Step_Compliance(),
            new WPGH_Guided_Setup_Step_Email(),
            new WPGH_Guided_Setup_Step_Import(),
            new WPGH_Guided_Setup_Step_Other(),
            new WPGH_Guided_Setup_Step_Finished(),
        ] );

    }

    /**
     * Get the number of steps
     *
     * @return int
     */
    public function get_step_count()
    {
        return count( $this->steps );
    }

    /**
     * Get the current step progression, or false if none defined.
     *
     * @return int
     */
    private function get_current_step_id()
    {
        if ( isset( $_GET[ 'step' ] ) ){
            return intval( $_GET[ 'step' ] );
        }

        return false;
    }

    /**
     * Gets the current step
     *
     * @return WPGH_Guided_Setup_Step
     */
    private function get_current_step()
    {
        return $this->steps[ $this->get_current_step_id() - 1 ];
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

    }

    /**
     * The main output
     */
    public function page()
    {
        ?>
        <div class="wrap">
        <?php if ( apply_filters( 'wpgh_show_phil_on_welcome_page', true ) ): ?>
            <img style="position: fixed;bottom: -80px;right: -80px;transform: rotate(-20deg);" class="phil" src="<?php echo WPGH_ASSETS_FOLDER . 'images/phil-340x340.png'; ?>" width="340" height="340">
        <?php endif; ?>
        <form action="" method="post">
            <?php wp_nonce_field(); ?>
        <?php
        /* Do step content */
        if ( $this->get_current_step_id() ){

            echo $this->get_current_step()->get_content();

        } else {
            /* Do Starting Content*/
            ?>
            <div style="max-width: 600px;margin: auto;">
                <div class="big-header" style="text-align: center;margin: 2.5em;">
                    <?php if ( apply_filters( 'wpgh_show_phil_on_welcome_page', true ) ): ?>
                        <img src="<?php echo WPGH_ASSETS_FOLDER . 'images/groundhogg-logo.png'; ?>" width="300">
                    <?php else: ?>
                        <h1 style="font-size: 40px;"><b><?php _ex( 'Guided Setup', 'guided_setup', 'groundhogg' ); ?></b></h1>
                    <?php endif; ?>
                </div>
                <div class="">
                    <div class="postbox">
                        <div class="inside" style="padding: 30px;">
                            <h2><b><?php _ex( 'Welcome to the Guided Setup', 'guided_setup', 'groundhogg' );?></b></h2>
                            <p><?php _ex( 'Follow these steps to quickly setup Groundhogg for your business. Setup usually takes around a few minutes. You can always change this information later in the settings page.', 'guided_setup', 'groundhogg' ); ?></p>
                            <?php if ( apply_filters( 'wpgh_show_phil_on_welcome_page', true ) ): ?>
                                <img width="100%" src="<?php echo WPGH_ASSETS_FOLDER . 'images/phil-pulling-lever.png'; ?>">
                            <?php endif; ?>
                            <p class="submit">
                                <a style="float: left" class="button button-primary" href="<?php printf( admin_url( 'admin.php?page=gh_guided_setup&step=%d' ), 1 ) ?>"><?php _ex( 'Get Started!', 'guided_setup', 'groundhogg' ); ?></a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <?php
        }

        ?></form></div><?php
    }

}