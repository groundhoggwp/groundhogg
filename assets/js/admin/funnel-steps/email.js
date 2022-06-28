(function ($,step,modal) {
    $.extend( step, {

        ID: 0,
        newEmailId: 0,
        step: null,
        addingEmail: false,
        editingEmail: false,
        changesSaved: false,

        init: function(){

            var self = this;

            $( document ).on( 'click', '.edit-email', function ( e ) {
                self.step = $(this).closest('.step' );
                self.addingEmail  = false;
                self.editingEmail = true;

                modal.init( 'Edit Email', {
                    source: self.edit_email_path + '&email=' + self.getEmailId(),
                    width: 1500,
                    height: 900,
                    footertext: modal.defaults.footertext
                } );

            } );

            $( document ).on( 'click', '.add-email', function ( e ) {
                self.step = $(this).closest('.step' );
                self.addingEmail  = true;
                self.editingEmail = false;

                modal.init( 'Add Email', {
                    source: self.add_email_path,
                    width: 1500,
                    height: 900,
                    footertext: modal.defaults.footertext
                } );

            } );

            $( document ).on( 'modal-closed', function ( e ) {

                if ( ! self.addingEmail && ! self.editingEmail  ){
                    return;
                }

                if ( ! self.changesSaved ){
                    if ( ! confirm( self.save_changes_prompt ) ){
                        throw new Error("Unsaved changes!");
                    }
                }

                if ( self.addingEmail ){
                    self.step.find( '.add-email-override' ).val( self.newEmailId )
                }

                self.addingEmail = false;
                self.editingEmail = false;

                setTimeout( () => {
                    $(document).trigger( 'auto-save' );
                }, 50 )

            } );

        },

        getEmailId: function(){
            return this.step.find( '.gh-email-picker' ).val();
        },
    } );

    $( function () {
        step.init()
    } );

})( jQuery, EmailStep, GroundhoggModal );
