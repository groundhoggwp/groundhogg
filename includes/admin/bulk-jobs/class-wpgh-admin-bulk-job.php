<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-28
 * Time: 2:45 PM
 */

if ( ! class_exists( 'WPGH_Admin_Page' ) ){
    include_once WPGH_PLUGIN_DIR . 'includes/class-wpgh-admin-page.php';
}

class WPGH_Admin_Bulk_Job extends WPGH_Admin_Page
{

    /* Unused Functions */
    public function get_order(){return 0;}
    public function scripts(){}
    public function help(){}

    /**
     * Listen for the bulk actions..
     *
     * @return void
     */
    public function add_ajax_actions(){
        add_action( 'wp_ajax_bulk_action_listener', [ $this, 'ajax_listener' ] );
    }

    /**
     * Listen for the bulk action and then perform it.
     */
    public function ajax_listener()
    {
        $bulk_action = $_POST[ 'bulk_action' ];
        do_action( $bulk_action );
    }

    protected function get_parent_slug()
    {
        return 'options.php';
    }

    /**
     * Get the slug
     *
     * @return string
     */
    public function get_slug()
    {
        return 'gh_bulk_jobs';
    }

    /**
     * default screen title
     *
     * @return string
     */
    public function get_name()
    {
        return __( 'Processing...', 'groundhogg' );
    }

    /**
     * Minimum access cap
     *
     * @return string
     */
    public function get_cap()
    {
        return 'manage_options';
    }

    /**
     * @return mixed|string
     */
    public function get_item_type()
    {
        return 'job';
    }

    protected function get_title_actions()
    {
        return [];
    }

    /**
     * Display the title and dependent action include the appropriate page content
     */
    public function page(){

        WPGH()->notices->add( 'do_not_leave', __( 'Do not leave this page till the job is complete!', 'groundhogg' ), 'warning' )

        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php echo $this->get_title(); ?></h1>
            <div id="notices">
                <?php $this->notices->notices(); ?>
            </div>
            <hr class="wp-header-end">
            <?php $this->view(); ?>
        </div>
        <?php
    }

    public function view()
    {

        $items = apply_filters( "groundhogg/bulk_job/{$this->get_action()}/query", [] );

        echo WPGH()->html->progress_bar( [ 'id' => 'bulk-job', 'hidden' => false ] );

        ?>
        <div id="spinner" class="">
            <span class="spinner" style="visibility: visible;"></span>
        </div>
        <div id="job-complete" class="hidden">
            <p><?php _e( "The process is now complete.", 'groundhogg' ); ?></p>
            <p class="submit">
                <a class="button button-primary" href="<?php echo admin_url( 'index.php' ); ?>">&larr;&nbsp;<?php _e( 'Return to dashboard.', 'groundhogg' ) ?></a>
            </p>
        </div>

        <script>
            var BulkProcessor = {};

            ( function ( $, bp, items ) {

                Object.assign( bp, {

                    items: 0,
                    complete: 0,
                    all:0,
                    size: 100,
                    bar: null,

                    init: function () {

                        this.items = items;
                        this.all = items.length;
                        this.bar = $( '#bulk-job' );
                        this.progress = $( '#bulk-job-percentage' );

                        if ( this.all < 400 ){
                            this.size = Math.ceil( this.all / 4 );
                        }

                        this.send();

                    },

                    getItems: function (){
                        var end = this.size;

                        if ( this.items.length < this.size ){
                            end  = this.items.length;
                        }

                        return this.items.splice( 0, end );
                    },

                    isLastOfThem: function (){
                        return this.items.length === 0;
                    },

                    updateProgress: function() {

                        var p = Math.round( ( this.complete / this.all ) * 100 );

                        this.bar.animate( { 'width': p + '%' } );
                        this.progress.text( p + '%' );

                        if ( this.complete === this.all ){
                            $( '#job-complete' ).removeClass( 'hidden' );
                            $( '#spinner' ).addClass( 'hidden' );
                        }

                    },

                    send: function () {

                        $.ajax({
                            type: "post",
                            url: ajaxurl,
                            dataType: 'json',
                            data: { action: 'bulk_action_listener', bulk_action: '<?php echo "groundhogg/bulk_job/{$this->get_action()}/ajax" ?>', items: bp.getItems(), the_end: bp.isLastOfThem() },
                            success: function( response ){

                                console.log(response);

                                if ( typeof response.complete !== "undefined" ){
                                    bp.complete += response.complete;
                                    bp.updateProgress();

                                    if ( bp.items.length > 0 ){
                                        bp.send();
                                    }

                                    if ( response.return_url !== undefined ){

                                        setTimeout( function () {
                                            window.location.replace(response.return_url);
                                        }, 1000 );
                                    }

                                } else {
                                    console.log( response );
                                    bp.bar.css( 'color', '#f70000' );
                                }

                            },
                            error: function ( response ) {
                                console.log( response );
                                bp.bar.css( 'color', '#f70000' );
                            }
                        });

                    }

                } );

                $(function () {
                    bp.init();
                });

            })( jQuery, BulkProcessor, <?php echo json_encode( $items ); ?> );
        </script>
        <?php
    }
}