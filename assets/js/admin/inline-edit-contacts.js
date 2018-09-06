window.wp = window.wp || {};

/**
 * Manages the quick edit contact.
 */
var inlineEditContact;
( function( $, wp ) {

    inlineEditContact = {
        /**
         * @summary Initializes the inline editor.
         *
         * Binds event handlers to the escape key to close the inline editor
         * and to the save and close buttons. Changes DOM to be ready for inline
         * editing.
         *
         * @returns {void}
         */
        init : function(){
            var t = this, qeRow = $('#inline-edit');

            t.type = 'contact';
            t.what = '#contact-';

            /**
             * @summary Bind escape key to revert the changes and close the quick editor.
             *
             * @returns {boolean} The result of revert.
             */
            qeRow.keyup(function(e){
                // Revert changes if escape key is pressed.
                if ( e.which === 27 ) {
                    return inlineEditContact.revert();
                }
            });

            /**
             * @summary Revert changes and close the quick editor if the cancel button is clicked.
             *
             * @returns {boolean} The result of revert.
             */
            $( '.cancel', qeRow ).click( function() {
                return inlineEditContact.revert();
            });

            /**
             * @summary Save changes in the quick editor if the save(named: update) button is clicked.
             *
             * @returns {boolean} The result of save.
             */
            $( '.save', qeRow ).click( function() {
                return inlineEditContact.save(this);
            });

            /**
             * @summary If enter is pressed, and the target is not the cancel button, save the contact.
             *
             * @returns {boolean} The result of save.
             */
            $('td', qeRow).keydown(function(e){
                if ( e.which === 13 && ! $( e.target ).hasClass( 'cancel' ) ) {
                    return inlineEditContact.save(this);
                }
            });

            /**
             * @summary Bind click event to the .editinline link which opens the quick editor.
             */
            $('#the-list').on( 'click', 'a.editinline', function( e ) {
                e.preventDefault();
                inlineEditContact.edit(this);
            });
        },

        /**
         * @summary Creates a quick edit window for the contact that has been clicked.
         *
         * @param {number|Object} id The id of the clicked row
         * @returns {boolean} Always returns false at the end of execution.
         */
        edit : function(id) {
            var t = this, fields, editRow, rowData;
            t.revert();

            id = t.getId(id);

            fields = [ 'email', 'first_name', 'last_name', 'optin_status', 'owner' ];

            // Add the new edit row with an extra blank row underneath to maintain zebra striping.
            editRow = $('#inline-edit').clone(true);
            $( 'td', editRow ).attr( 'colspan', $( 'th:visible, td:visible', '.widefat:first thead' ).length );

            $(t.what+id).removeClass('is-expanded').hide().after(editRow).after('<tr class="hidden"></tr>');

            // Populate fields in the quick edit window.
            rowData = $('#inline_'+id);

            for ( f = 0; f < fields.length; f++ ) {
                val = $('.'+fields[f], rowData);
                $(':input[name="' + fields[f] + '"]', editRow).val( val.text() );
            }

            var tags = $( '#tags' );
            tags.select2({tags:true,tokenSeparators: ['/',',',';']});
            tags.val( JSON.parse( $('.tags', rowData ).html() ) );
            tags.trigger( 'change' );

            $(editRow).attr('id', 'edit-'+id).addClass('inline-editor').show();
            $('.cemail', editRow).focus();

            return false;
        },

        /**
         * @summary Saves the changes made in the quick edit window to the contact.
         *
         * @param   {int}     id The id for the contact that has been changed.
         * @returns {boolean}    false, so the form does not submit when pressing
         *                       Enter on a focused field.
         */
        save : function(id) {
            var params, fields;

            if ( typeof(id) === 'object' ) {
                id = this.getId(id);
            }

            $( 'table.widefat .spinner' ).addClass( 'is-active' );

            params = {
                action: 'wpgh_inline_save_contacts',
                ID: id,
            };

            fields = $('#edit-'+id).find(':input').serialize();
            params = fields + '&' + $.param(params);

            console.log(params);

            // Make ajax request.
            $.post( ajaxurl, params,
                function( r ) {
                    var $errorNotice = $( '#edit-' + id + ' .inline-edit-save .notice-error' ),
                        $error = $errorNotice.find( '.error' );

                    $( 'table.widefat .spinner' ).removeClass( 'is-active' );
                    $( '.ac_results' ).hide();

                    if ( r ) {
                        if ( -1 !== r.indexOf( '<tr' ) ) {
                            $(inlineEditContact.what+id).siblings('tr.hidden').addBack().remove();
                            $('#edit-'+id).before(r).remove();
                            $( inlineEditContact.what + id ).hide().fadeIn( 400, function() {
                                // Move focus back to the Quick Edit link. $( this ) is the row being animated.
                                $( this ).find( '.editinline' ).focus();
                                //wp.a11y.speak( inlineEditL10n.saved );
                            });
                        } else {
                            r = r.replace( /<.[^<>]*?>/g, '' );
                            $errorNotice.removeClass( 'hidden' );
                            $error.html( r );
                            //wp.a11y.speak( $error.text() );
                        }
                    } else {
                        $errorNotice.removeClass( 'hidden' );
                        $error.html( inlineEditL10n.error );
                       //wp.a11y.speak( inlineEditL10n.error );
                    }
                },
                'html');

            // Prevent submitting the form when pressing Enter on a focused field.
            return false;
        },

        /**
         * @summary Hides and empties the Quick Edit windows.
         *
         * @returns {boolean} Always returns false.
         */
        revert : function(){
            var $tableWideFat = $( '.widefat' ),
                id = $( '.inline-editor', $tableWideFat ).attr( 'id' );

            if ( id ) {
                $( '.spinner', $tableWideFat ).removeClass( 'is-active' );
                $( '.ac_results' ).hide();

                // Remove both the inline-editor and its hidden tr siblings.
                $('#'+id).siblings('tr.hidden').addBack().remove();
                id = id.substr( id.lastIndexOf('-') + 1 );

                // Show the row and move focus back to the Quick Edit link.
                $( this.what + id ).show().find( '.editinline' ).focus();
            }

            return false;
        },

        /**
         * @summary Gets the id for the row that you want to quick edit from the row
         * in the quick edit table.
         *
         * @param   {Object} o DOM row object to get the id for.
         * @returns {string}   The id extracted from the table row in the object.
         */
        getId : function(o) {
            var id = $(o).closest('tr').attr('id'),
                parts = id.split('-');
            return parts[parts.length - 1];
        }
    };

    $( document ).ready( function(){ inlineEditContact.init(); } );
})( jQuery, window.wp );
