(function ($,step,modal) {
    $.extend( step, {

        ID: 0,
        step: null,
        addingEmail: false,
        editingEmail: false,
        changesSaved: false,

        init: function(){

            var self = this;

            $( document ).on( 'click', '.edit-email', function ( e ) {
                self.step = $(this).closest('.postbox' );
                self.addingEmail  = false;
                self.editingEmail = true;



            } );

        },

        init_old: function () {

            var self = this;

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
        step.init()
    } );

})( jQuery, EmailStep );