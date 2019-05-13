
if ( typeof $ === 'undefined' ){
    var $ = function () {
        return jQuery;
    };
}

/**
 * Send an ajax request to the WP admin.
 *
 * @param args [] list of args to send.
 * @param callback a callback function.
 * @param fail_callback
 */
function adminAjaxRequest( args, callback=null, fail_callback=null ) {
    var ajaxCall = jQuery.ajax({
        type: "post",
        url: ajaxurl,
        dataType: 'json',
        data: args,
        success: callback,
        error: fail_callback
    });
}

/**
 * Show a spinner
 */
function showSpinner() {
    $('.spinner').css( 'visibility', 'visible' );
}

/**
 * Hide a spinner
 */
function hideSpinner() {
    $('.spinner').css( 'visibility', 'hidden' );
}

/**
 * Add notices to the notices div.
 *
 * @param notices
 */
function handleNotices( notices ) {
    if ( typeof notices !== 'undefined' ){
        $( '#groundhogg-notices' ).html( notices );
    }

    makeDismissible();
}

/**
 * Make notices dismissible
 */
function makeDismissible() {

    $( "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss This Notice</span></button>" ).appendTo( '.is-dismissible' );
    $( '.notice-dismiss' ).on( 'click', function ( e ) {
        $(this).parent().fadeOut( 100, function () {
            $(this).remove();
        } );
    } )
}
