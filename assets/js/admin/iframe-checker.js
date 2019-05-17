var IsFrame = {};

(function ($,framer) {

    $.extend( framer, {

        init: function () {
            /* Size the editor to full screen if being views in an Iframe. */

            // if ( this.inFrame() ){
            //     // $( 'body' ).html( $( '#wpbody' ) );
            //     // $( '#screen-meta-links' ).remove();
            //     $( 'html' ).css( 'padding-top', 0 );
            //     $( '#wpcontent' ).css( 'margin', 0 );
            //     $( '#wpadminbar' ).addClass( 'hidden' );
            //     $( '#adminmenuwrap' ).addClass( 'hidden' );
            //     $( '#adminmenuback' ).addClass( 'hidden' );
            //     $( '#wpfooter' ).addClass( 'hidden' );
            //     // $( '.title-wrap' ).css( 'display', 'none' );
            //     // $( '.funnel-editor-header' ).css( 'top', 0 );
            // }
        },

        inFrame: function () {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        },
    } );

    $(function () {
        framer.init();
    });

    if ( framer.inFrame() ){
        $( 'html' ).addClass( 'iframed' );
    }

})(jQuery,IsFrame);