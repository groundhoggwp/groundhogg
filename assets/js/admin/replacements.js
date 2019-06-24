var ReplacementsInsertListener = {};

(function ($,replacements,modal) {

    function insertAtCursor(myField, myValue) {
        //IE support
        if (document.selection) {
            myField.focus();
            sel = document.selection.createRange();
            sel.text = myValue;
        }
        //MOZILLA and others
        else if (myField.selectionStart || myField.selectionStart == '0') {
            var startPos = myField.selectionStart;
            var endPos = myField.selectionEnd;
            myField.value = myField.value.substring(0, startPos)
                + myValue
                + myField.value.substring(endPos, myField.value.length);
        } else {
            myField.value += myValue;
        }

        $(myField).trigger( 'change' );
    }

    $.extend( replacements, {

        inserting: false,
        active: null,
        text: '',
        to_mce: false,

        init: function () {

            var self = this;

            $( document ).on( 'click', '.replacements-button', function () {
                self.inserting = true;
            } );

            // GO TO MCE
            $(document).on( 'to_mce', function () {
                self.to_mce = true;
            } );

            // NOPE, GO TO TEXT
            $(document).on( 'click', '#wpbody input, #wpbody textarea', function () {
                self.active = this;
                self.to_mce = false;
            } );

            $( document ).on( 'click', '.replacement-selector', function () {
                self.text = $(this).val();
            } );

            $( document ).on( 'dblclick', '.replacement-selector', function () {
                self.text = $(this).val();
                self.insert();
                modal.close();
            } );

            $( '#popup-close-footer' ).on( 'click', function () {

                if ( ! self.inserting ){
                    return;
                }
                self.insert();
                self.inserting = false;
            });

        },

        insert: function () {

            // CHECK TINY MCE
            if ( tinymce.activeEditor != undefined && this.to_mce ){
                tinymce.activeEditor.execCommand('mceInsertContent', false, this.text );
            // INSERT REGULAR TEXT INPUT.
            }

            if ( this.active != undefined && ! this.to_mce ) {
                insertAtCursor( this.active, this.text );
            }

            console.log( { text: this.text } );
        }

    } );

    $(function () {
        replacements.init();
    });

})(jQuery,ReplacementsInsertListener,GroundhoggModal);
