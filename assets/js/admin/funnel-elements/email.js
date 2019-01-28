var wpghEmailElement;

(function ($,base) {
    wpghEmailElement = {

        ID: 0,
        target: null,
        addingEmail: false,
        editingEmail: false,
        changesSaved: false,

        init: function () {
            $( document ).on( 'change', '.gh-email-picker', function ( e ) {
                var link = '#source=' + encodeURIComponent( base.path + '&action=edit&email=' + $(this).val() ) + '&height=900&width=1500' ;
                wpghEmailElement.ID = $(this).val();
                wpghEmailElement.target = $(this).closest('.postbox' );
                $(this).closest( '.form-table' ).find( '.edit-email' ).attr( 'href', link );
            } );

            $( document ).on( 'click', '.add-email', function ( e ) {
                wpghEmailElement.target = $(this).closest('.postbox' );
                wpghEmailElement.addingEmail = true;
                wpghEmailElement.editingEmail = false;
            } );

            $( document ).on( 'click', '.edit-email', function ( e ) {
                wpghEmailElement.target = $(this).closest('.postbox' );
                wpghEmailElement.addingEmail = false;
                wpghEmailElement.editingEmail = true;
            } );

            $( document ).on( 'click', '.popup-save', function ( e ) {

                if ( ! wpghEmailElement.addingEmail && ! wpghEmailElement.editingEmail  ){
                    return;
                }

                if ( ! wpghEmailElement.changesSaved ){
                    if ( ! confirm( base.dontSaveChangesMsg ) ){
                        throw new Error("Unsaved changes!");
                    }
                }

                if ( wpghEmailElement.addingEmail ){
                    wpghEmailElement.target.find( '.add-email-override' ).val( wpghEmailElement.ID )
                }

                wpghEmailElement.addingEmail = false;
                wpghEmailElement.editingEmail = false;

            } );
        }
    };

    $( function () {
        wpghEmailElement.init()
    } );
})(jQuery,wpghEmailsBase);