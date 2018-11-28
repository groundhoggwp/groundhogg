<?php
/**
 * Created by PhpStorm.
 * User: atty
 * Date: 11/27/2018
 * Time: 9:13 AM
 */
if ( ! defined( 'ABSPATH' ) ) exit;

class WPGH_Dashboard_Page
{

    /**
     * @var int
     */
    public $reporting_start_time;

    /**
     * @var int
     */
    public $reporting_end_time;

    function __construct()
    {
        add_action( 'admin_menu', array( $this, 'register' ) );

        $this->setup_reporting();

        if ( isset( $_GET['page'] ) && $_GET[ 'page' ] === 'gh_dashboard' ){

            add_action( 'admin_enqueue_scripts' , array( $this, 'scripts' )  );

        }
    }


    /**
     * enqueue editor scripts
     */
    public function scripts()
    {
        wp_enqueue_style( 'dashboard', WPGH_ASSETS_FOLDER . '/css/admin/dashboard.css', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/css/admin/dashboard.css') );
        wp_enqueue_script( 'wpgh-flot-chart', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.min.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.min.js') );
        wp_enqueue_script( 'wpgh-flot-chart-categories', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.categories.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.categories.js') );
        wp_enqueue_script( 'wpgh-flot-chart-time', WPGH_ASSETS_FOLDER . '/lib/flot/jquery.flot.time.js', array(), filemtime(WPGH_PLUGIN_DIR . 'assets/lib/flot/jquery.flot.time.js') );
    }



    public function register()
    {

        $page = add_submenu_page(
            'groundhogg',
            'Dashboard',
            'Dashboard',
            'administrator',
            'gh_dashboard',
            array($this, 'page')
        );

    }


    public function get_reporting_range()
    {
        return ( isset( $_POST[ 'date_range' ] ) )? $_POST[ 'date_range' ] : 'last_30' ;
    }


    private function setup_reporting(){

        switch ( $this->get_reporting_range() ):
            case 'last_24';
                $this->reporting_start_time = strtotime( '1 day ago' );
                $this->reporting_end_time   = time();
                break;
            case 'last_7';
                $this->reporting_start_time = strtotime( '7 days ago' );
                $this->reporting_end_time   = time();

                break;
            case 'last_30';
                $this->reporting_start_time = strtotime( '30 days ago' );
                $this->reporting_end_time   = time();

                break;
            case 'custom';
                $this->reporting_start_time = strtotime( $_POST['custom_date_range_start'] );
                $this->reporting_end_time   = strtotime( $_POST['custom_date_range_end'] );
                break;
            default:
                $this->reporting_start_time = strtotime( '1 day ago' );
                $this->reporting_end_time   = time();
                break;
        endswitch;
    }


    private function include_reports()
    {
        include_once dirname( __FILE__ ) . '/class-wpgh-report.php';
        include_once dirname( __FILE__ ) . '/reports/class-wpgh-report-optins.php';
        include_once dirname(__FILE__) . '/reports/class-wpgh-report-send-emails.php';
    }

    private function create_reports()
    {
        $new_contact = new WPGH_Report_Optins('New Contacts' , 'new_contacts' );
        $send_emails =  new WPGH_Report_Send_Emails( 'Send Emails', 'send_email' );
    }

    public function page()
    {

        $this->include_reports();
        $this->create_reports();

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Dashboard' ); ?></h1>
            <?php wp_enqueue_style(  'jquery-ui' );
            wp_enqueue_script( 'jquery-ui-datepicker' ); ?>
            <form action="" method="post">
                <div class="actions">
                    <?php $args = array(
                        'name'      => 'date_range',
                        'id'        => 'date_range',
                        'options'   => array(
                            'last_24'   => __( 'Last 24 Hours' ),
                            'last_7'    => __( 'Last 7 Days' ),
                            'last_30'   => __( 'Last 30 Days' ),
                            'custom'    => __( 'Custom Range' ),
                        ),
                        'selected' => WPGH()->menu->funnels_page->get_reporting_range(),
                    ); echo WPGH()->html->dropdown( $args ); ?>

                    <?php $selected = WPGH()->menu->funnels_page->get_reporting_range(); ?>
                    <input autocomplete="off" placeholder="<?php esc_attr_e('From:'); ?>" class="input <?php if ( $selected !== 'custom' ) echo 'hidden'; ?>" id="custom_date_range_start" name="custom_date_range_start" type="text" value="<?php if ( isset(  $_POST[ 'custom_date_range_start' ] ) ) echo $_POST['custom_date_range_start']; ?>">
                    <script>jQuery(function($){$('#custom_date_range_start').datepicker({
                            changeMonth: true,
                            changeYear: true,
                            maxDate:0,
                            dateFormat:'d-m-yy'
                        })});</script>
                    <input autocomplete="off" placeholder="<?php esc_attr_e('To:'); ?>" class="input <?php if ( $selected !== 'custom' ) echo 'hidden'; ?>" id="custom_date_range_end" name="custom_date_range_end" type="text" value="<?php if ( isset(  $_POST[ 'custom_date_range_end' ] ) ) echo $_POST['custom_date_range_end']; ?>">
                    <script>jQuery(function($){$('#custom_date_range_end').datepicker({
                            changeMonth: true,
                            changeYear: true,
                            maxDate:0,
                            dateFormat:'d-m-yy'
                        })});</script>

                    <script>jQuery(function($){$('#date_range').change(function(){
                            if($(this).val() === 'custom'){
                                $('#custom_date_range_end').removeClass('hidden');
                                $('#custom_date_range_start').removeClass('hidden');
                            } else {
                                $('#custom_date_range_end').addClass('hidden');
                                $('#custom_date_range_start').addClass('hidden');
                            }})});
                    </script>
                    <?php submit_button( 'Refresh', 'secondary', 'change_reporting', false ); ?>
                </div>
            </form>
            <hr class="wp-header-end">
            <div id="poststuff">
                <?php $graphs = apply_filters( 'wpgh_dashboard_graphs', array() );

                /**
                 * @var $graphs WPGH_Report[]
                 *
                 */
                foreach ( $graphs as $graph ){
                    $graph->display();
                }

                ?>
            </div>

        </div>
        <?php



    }

}