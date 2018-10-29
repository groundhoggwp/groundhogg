var wpghFormBuilder;

(function($){

    wpghFormBuilder = {

        activeEditor: null,

        init: function(){

            $(document).on( 'click', 'div.form-buttons', function ( e ) {

                wpghFormBuilder.setup( this );
                wpghFormBuilder.addField( e.target );

            } );

        },

        setup: function( dom  ){

            this.activeEditor = $(dom).closest( '.form-editor' ).find( '.code' )[0];

        },

        addField: function( button ){

            var type = button.className.replace( 'button button-secondary ', '' );
            //console.log( type );

            var code;

            switch ( type ) {
                case 'first':
                    code = '[first_name label="First Name *" placeholder="" required="true"]\n';
                    break;
                case 'last':
                    code = '[last_name label="Last Name*" placeholder="" required="true"]\n';
                    break;
                case 'email':
                    code = '[email label="Email *" placeholder="" required="true"]\n';
                    break;
                case 'phone':
                    code = '[phone label="Phone *" placeholder="" required="true"]\n';
                    break;
                case 'gdpr':
                    code = '[gdpr]\n';
                    break;
                case 'terms':
                    code = '[terms]\n';
                    break;
                case 'recaptcha':
                    code = '[recaptcha]\n';
                    break;
                case 'submit-button':
                    code = '[submit]Submit[/submit]\n';
                    break;
                case 'text':
                    code = '[text label="Pet Name *" placeholder="Pluto" required="true"]\n';
                    break;
                case 'textarea':
                    code = '[textarea label="Pet Description *" placeholder="" required="true"]\n';
                    break;
                case 'number':
                    code = '[number label="Pet Age *" placeholder="" required="true"]\n';
                    break;
                case 'dropdown':
                    code = '[select label="Pet Breed*" default="Please select One!" options="Terrier,Mastiff" required="true"]\n';
                    break;
                case 'radio':
                    code = '[radio label="Pet Gender *" options="Male,Female,Other" required="true"]\n';
                    break;
                case 'checkbox':
                    code = '[checkbox label="Likes Treats? *" value="Yes" required="false"]\n';
                    break;
                case 'address':
                    code = '[address label="Address *" required="true"]';
                    break;

            }

            this.insert( code );

        },

        insert: function ( myValue ){

            var myField = this.activeEditor;
            // console.log( myField );

            if (document.selection) {
                myField.focus();
                sel = document.selection.createRange();
                sel.text = myValue;
            }

            //MOZILLA and others
            else if ( myField.selectionStart ) {
                var startPos = myField.selectionStart;
                var endPos = myField.selectionEnd;
                myField.value = myField.value.substring(0, startPos)
                    + myValue
                    + myField.value.substring(endPos, myField.value.length);
                myField.selectionStart = startPos + myValue.length;
                myField.selectionEnd = startPos + myValue.length;
            } else {
                myField.value += myValue;
            }
        }

    };

    $(function () {
        wpghFormBuilder.init();
    });

})(jQuery);