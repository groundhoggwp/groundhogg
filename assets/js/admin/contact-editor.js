var ContactEditor = {};

(function($,editor){

    $.extend( editor, {

        init: function () {

            $( '#meta-table' ).click(function( e ){
                if ( $( e.target ).closest( '.deletemeta' ).length ){
                    $( e.target ).closest( 'tr' ).remove();
                }
            });

            $( '.addmeta' ).click(function(){

                var $newMeta = "<tr>" +
                    "<th>" +
                    "<input type='text' class='input' name='newmetakey[]' placeholder='" + $('.metakeyplaceholder').text() + "'>" +
                    "</th>" +
                    "<td>" +
                    "<input type='text' class='regular-text' name='newmetavalue[]' placeholder='" + $('.metavalueplaceholder').text() + "'>" +
                    " <span class=\"row-actions\"><span class=\"delete\"><a style=\"text-decoration: none\" href=\"javascript:void(0)\" class=\"deletemeta\"><span class=\"dashicons dashicons-trash\"></span></a></span></span>\n" +
                    "</td>" +
                    "</tr>";
                $('#meta-table').find( 'tbody' ).prepend( $newMeta );

            });

            $( '.create-user-account' ).click( function () {
                $( '#create-user-form' ).submit();
            });


            $( '.nav-tab' ).click(function (e) {

                var $tab = $(this);

                $( '.nav-tab' ).removeClass( 'nav-tab-active' );
                $tab.addClass( 'nav-tab-active' );

                $( '.tab-content-wrapper' ).addClass( 'hidden' );
                $( '#' + $tab.attr( 'id' ) + '_content' ).removeClass( 'hidden' );

                $( '#active-tab' ).val( $tab.attr( 'id' ).replace( 'tab_', '' ) );
                document.cookie = "gh_contact_tab=" + $tab.attr( 'id' ) + ";path=/;";

            });

            // $( '#manual_form_submission' ).on( 'change', function (e) {
            //     var formId = $( '#manual_form_submission' ).val();
            //     $( '#form-submit-link' ).attr( 'href', WPGHFormSubmitBaseUrl + formId );
            // });
        }
    } );

    $(function () {
        editor.init();
    });

})(jQuery,ContactEditor);