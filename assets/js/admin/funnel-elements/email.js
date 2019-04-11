var wpghEmailElement = {};

(function (e,$,base) {
    e = $.extend( e, {

        ID: 0,
        target: null,
        addingEmail: false,
        editingEmail: false,
        changesSaved: false,

        init: function () {
            $( document ).on( 'change', '.gh-email-picker', function ( e ) {
                var link = '#source=' + encodeURIComponent( base.path + '&action=edit&email=' + $(this).val() ) + '&height=900&width=1500' ;
                e.ID = $(this).val();
                e.target = $(this).closest('.postbox' );
                $(this).closest( '.form-table' ).find( '.edit-email' ).attr( 'href', link );
            } );

            $( document ).on( 'click', '.add-email', function ( e ) {
                e.target = $(this).closest('.postbox' );
                e.addingEmail = true;
                e.editingEmail = false;
            } );

            $( document ).on( 'click', '.edit-email', function ( e ) {
                e.target = $(this).closest('.postbox' );
                e.addingEmail = false;
                e.editingEmail = true;
            } );

            $( document ).on( 'click', '.popup-save', function ( e ) {

                if ( ! e.addingEmail && ! e.editingEmail  ){
                    return;
                }

                if ( ! e.changesSaved ){
                    if ( ! confirm( base.dontSaveChangesMsg ) ){
                        throw new Error("Unsaved changes!");
                    }
                }

                if ( e.addingEmail ){
                    e.target.find( '.add-email-override' ).val( e.ID )
                }

                e.addingEmail = false;
                e.editingEmail = false;

            } );
        }
    } );

    $( function () {
        e.init()
    } );

})(wpghEmailElement,jQuery,wpghEmailsBase);