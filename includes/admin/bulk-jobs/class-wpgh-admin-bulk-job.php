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
    protected function add_additional_actions(){}

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
        if ( ! current_user_can( 'perform_bulk_actions' ) ){
            return;
        }

	    $bulk_action = $_POST[ 'bulk_action' ];

	    if ( ! wp_verify_nonce( $_POST[ '_wpnonce' ], $bulk_action ) ){
	        return;
        }

	    $action = "groundhogg/bulk_job/{$bulk_action}/ajax";

	    do_action( $action );
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

        WPGH()->notices->add( 'do_not_leave', __( 'Do not leave this page till the current process is complete!', 'groundhogg' ), 'warning' )

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
        $max_items = apply_filters( "groundhogg/bulk_job/{$this->get_action()}/max_items", 25, $items );

        echo WPGH()->html->progress_bar( [ 'id' => 'bulk-job', 'hidden' => false ] );

        ?>
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
                    size: <?php echo $max_items; ?>,
                    bar: null,
                    title: "",

                    init: function () {

                        this.items = items;
                        this.all = items.length;
                        this.bar = $( '#bulk-job' );
                        this.progress = $( '#bulk-job-percentage' );
                        this.title = document.title;

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

                        // for ( var i = 0; i < items.length; i++ ){
                        //     this.clean( items[ i ] )
                        // }
                        //
                        // return items;
                    },

                    isLastOfThem: function (){
                        return this.items.length === 0;
                    },

                    updateProgress: function() {

                        var p = Math.round( ( this.complete / this.all ) * 100 );

                        this.bar.animate( { 'width': p + '%' } );
                        this.progress.text( p + '%' );
                        document.title = '(' + p + '%) ' + this.title;

                        if ( this.complete === this.all ){
                            $( '#job-complete' ).removeClass( 'hidden' );
                            this.progress.removeClass( 'spinner' );
                        }

                    },

                    error: function( response ){
                        // console.log( response );
                        bp.bar.css( 'background-color', '#f70000' );
                        this.progress.removeClass( 'spinner' );
                        alert( 'Something went wrong...' );
                    },

                    clean: function( obj ){

                        if ( typeof obj !== 'object' || obj === null ){
                            return;
                        }

                        var propNames = Object.getOwnPropertyNames(obj);
                        for (var i = 0; i < propNames.length; i++) {
                            var propName = propNames[i];
                            if (obj[propName] === null || obj[propName] === undefined || obj[propName] === '' ) {
                                delete obj[propName];
                            }
                        }
                    },

                    send: function () {

                        $.ajax({
                            type: "post",
                            url: ajaxurl,
                            dataType: 'json',
                            data: { action: 'bulk_action_listener', bulk_action: '<?php echo $this->get_action(); ?>', items: bp.getItems(), _wpnonce: '<?php echo wp_create_nonce(  $this->get_action() ); ?>', the_end: bp.isLastOfThem() },
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
                                    bp.error( response );
                                }

                            },
                            error: function ( response ) {
                                bp.error();
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