<?php
/**
 * Created by PhpStorm.
 * User: adria
 * Date: 2019-03-15
 * Time: 3:01 PM
 */

$config = get_transient(  'gh_get_broadcast_config' );

if ( ! is_array( $config ) ){

    wp_die( 'No broadcast was scheduled....' );

}

$query = new WPGH_Contact_Query();
$query_args = $config[ 'contact_query' ];
$contacts = $query->query( $query_args );

$id_list = [];

foreach ( $contacts as $contact ){
    $id_list[] = $contact->ID;
}

?>
<style>
    #progress-bar-wrap {
        width: 100%;
        background-color: #ddd;
        margin-top: 20px;
    }

    #progress-bar {
        width: 1%;
        padding: 7px;
        background-color: #4CAF50;
        box-sizing: border-box;
    }
    #progress{
        color: #FFFFFF;
        font-weight: 500;
    }
</style>
<div id="progress-bar-wrap">
    <div id="progress-bar"><span id="progress">0%</span></div>
</div>

<div id="broadcast-complete" class="hidden">
    <p><?php _e( "The scheduling process is now complete.", 'groundhogg' ); ?></p>
    <p class="submit">
        <a class="button button-primary" href="<?php echo admin_url( 'admin.php?page=gh_broadcasts' ); ?>">&larr;&nbsp;<?php _e( 'Return to broadcasts', 'groundhogg' ) ?></a>
    </p>
</div>

<script>
    var BroadcastScheduler = {};

    ( function ( $, bs, contacts ) {

        Object.assign( bs, {

            contacts: 0,
            complete: 0,
            all:0,
            size: 50,
            bar: null,

            init: function () {

                this.contacts = contacts;
                this.all = contacts.length;
                this.bar = $( '#progress-bar' );
                this.progress = $( '#progress' );

                this.send();

            },

            getContacts: function (){
                var end = this.size;

                if ( this.contacts.length < this.size ){
                    end  = this.contacts.length;
                }

                return this.contacts.splice( 0, end );
            },

            isLastOfThem: function (){
                return this.contacts.length === 0;
            },

            updateProgress: function() {

                this.bar.css( 'width', ( ( this.complete / this.all ) * 100 ) + '%' );
                this.progress.text( Math.round( ( this.complete / this.all ) * 100 ) + '%' );

                if ( this.complete === this.all ){
                    $( '#broadcast-complete' ).removeClass( 'hidden' );
                }

            },

            send: function () {

                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    dataType: 'json',
                    data: { action: 'gh_email_broadcast_schedule', contacts: bs.getContacts(), the_end: bs.isLastOfThem() },
                    success: function( response ){

                        if ( typeof response.complete !== "undefined" ){
                            bs.complete += response.complete;
                            bs.updateProgress();

                            if ( bs.contacts.length > 0 ){
                                bs.send();
                            }

                        } else {
                            console.log( response );
                            alert( response );
                            var $spinner = $( '.spinner-import' );
                            $spinner.css( 'visibility', 'hidden' );
                        }

                    },
                    error: function ( response ) {
                        console.log( response );
                        alert( response );
                        var $spinner = $( '.spinner-import' );
                        $spinner.css( 'visibility', 'hidden' );
                    }
                });

            }

        } );

        $(function () {
            bs.init();
        });

    })( jQuery, BroadcastScheduler, <?php echo json_encode( $id_list ); ?> );
</script>
