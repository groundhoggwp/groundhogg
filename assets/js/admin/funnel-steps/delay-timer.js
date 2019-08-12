var DelayTimer = {};

(function ($,fi) {

    $.extend( fi, {

        init: function () {

            $( document ).on( 'change', '.run_when', function(){

                var $when = $(this);
                var $step = $when.closest( '.step' );
                var $time = $step.find( '.run_time' );

                if ( $when.val() === 'now' ){
                    $time.addClass( 'hidden' );
                } else {
                    $time.removeClass( 'hidden' );
                }

            });

        }

    } );

    $(function () {
       fi.init();
    });

})( jQuery, DelayTimer );