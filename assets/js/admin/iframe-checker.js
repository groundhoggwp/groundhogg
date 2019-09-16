var IsFrame = {};

(function ($,framer) {

    $.extend( framer, {

        inFrame: function () {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        },
    } );

    if ( framer.inFrame() ){
        $( 'html' ).addClass( 'iframed' );
    }

})(jQuery,IsFrame);