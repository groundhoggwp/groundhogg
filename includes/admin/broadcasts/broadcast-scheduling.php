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


echo WPGH()->html->progress_bar( [ 'id' => 'scheduler', 'hidden' => false ] );

?>

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
                this.bar = $( '#scheduler' );
                this.progress = $( '#scheduler-percentage' );

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

                var p = Math.round( ( this.complete / this.all ) * 100 );

                this.bar.animate( { 'width': p + '%' }, 'slow' );
                this.progress.text( p + '%' );

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
                            bs.bar.css( 'color', '#f70000' );
                        }

                    },
                    error: function ( response ) {
                        console.log( response );
                        bs.bar.css( 'color', '#f70000' );
                    }
                });

            }

        } );

        $(function () {
            bs.init();
        });

    })( jQuery, BroadcastScheduler, <?php echo json_encode( $id_list ); ?> );
</script>
