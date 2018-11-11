var wpghEmailElement;

(function ($) {
    wpghEmailElement = {

        init: function () {

            $( document ).on( 'change', '.gh-email-picker', function ( e ) {

                var link = '#source=' + encodeURIComponent( wpghEmailsBase.path + '&action=edit&email=' + $(this).val() ) + '&height=900&width=1500' ;

                $(this).closest( '.form-table' ).find( '.edit-email' ).attr( 'href', link );

            } );



        },

    };

    $( function () {
        wpghEmailElement.init()
    } );
})(jQuery);