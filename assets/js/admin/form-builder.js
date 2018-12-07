var wpghFormBuilder;

(function($){

    wpghFormBuilder = {

        activeEditor: null,
        active: false,
        currentType: null,

        init: function () {

            $(document).on('click', 'div.form-buttons', function (e) {

                wpghFormBuilder.setup(this);
                wpghFormBuilder.getForm(e.target);

            });

            $( '.popup-save' ).click( function (e) {
                if ( wpghFormBuilder.active ){
                    wpghFormBuilder.makeField();
                }
            });

            $( '#field-name' ).change( function () {
                $(this).val( wpghFormBuilder.sanitizeKey( $(this).val() ) );
            });
            $( '#field-id' ).change( function () {
                $(this).val( wpghFormBuilder.sanitizeKey( $(this).val() ) );
            });

        },

        sanitizeKey: function( key )
        {
            return key.toLowerCase().replace( /[^a-z0-9\-_]/g, '' );
        },

        setup: function (dom) {

            this.activeEditor = $(dom).closest('.form-editor').find('.code')[0];

        },

        getForm: function (button) {

            wpghModal.args.preventSave = true;
            this.active = true;

            var type = button.className.split(' ')[2];
            this.currentType = type;

            var code;
            var fields = [];

            switch (type) {
                case 'first':
                    fields = [
                        'required',
                        'label',
                        'placeholder',
                        'id',
                        'class'
                    ];
                    break;
                case 'last':
                    fields = [
                        'required',
                        'label',
                        'placeholder',
                        'id',
                        'class'
                    ];
                    break;
                case 'email':
                    fields = [
                        'required',
                        'label',
                        'placeholder',
                        'id',
                        'class'
                    ];
                    break;
                case 'phone':
                    fields = [
                        'required',
                        'label',
                        'placeholder',
                        'id',
                        'class'
                    ];
                    break;
                case 'gdpr':
                    fields = [
                        'label',
                        'id',
                        'class'
                    ];
                    break;
                case 'terms':
                    fields = [
                        'label',
                        'id',
                        'class'
                    ];
                    break;
                case 'recaptcha':
                    fields = [
                        'captcha-theme',
                        'captcha-size',
                        'id',
                        'class'
                    ];
                    break;
                case 'submit':
                    fields = [
                        'text',
                        'id',
                        'class'
                    ];
                    break;
                case 'text':
                    fields = [
                        'required',
                        'label',
                        'placeholder',
                        'name',
                        'id',
                        'class'
                    ];
                    break;
                case 'textarea':
                    fields = [
                        'required',
                        'label',
                        'placeholder',
                        'name',
                        'id',
                        'class'
                    ];
                    break;
                case 'number':
                    fields = [
                        'required',
                        'label',
                        'name',
                        'min',
                        'max',
                        'id',
                        'class'
                    ];
                    break;
                case 'dropdown':
                    fields = [
                        'required',
                        'label',
                        'name',
                        'default',
                        'options',
                        'multiple',
                        'id',
                        'class'
                    ];
                    break;
                case 'radio':
                    fields = [
                        'required',
                        'label',
                        'name',
                        'options',
                        'id',
                        'class'
                    ];
                    break;
                case 'checkbox':
                    fields = [
                        'required',
                        'label',
                        'name',
                        'value',
                        'id',
                        'class'
                    ];
                    break;
                case 'address':
                    fields = [
                        'required',
                        'label',
                        'id',
                        'class'
                    ];
                    break;
                case 'row':
                    fields = [
                        'id',
                        'class'
                    ];
                    break;
                case 'col':
                    fields = [
                        'width',
                        'id',
                        'class'
                    ];
                    break;
            }

            this.hideFields();
            this.showFields( fields );

        },

        hideFields: function () {
            $('.form-field-form').find( 'tr' ).addClass('hidden');
        },

        showFields: function (fields){
            for (var i = 0; i < fields.length; i++) {
                $('#gh-field-' + fields[i]).removeClass('hidden');
            }
        },

        buildCode: function()
        {

            var $form = $( '#form-field-form' );

            var attrs = $form.serializeArray();

            var code = '[' + this.currentType;
            var ignore = [
                'col',
                'row',
                'gdpr',
                'recaptcha',
                'submit',
            ];

            for( var i=0;i<attrs.length;i++){

                if ( attrs[i].value !== "" ){
                    code += ' ' + attrs[i].name + '="' + attrs[i].value.replace(/\r\n/g, '\n').replace(/\r/g, '\n').replace(/\n/g, ',') + '"'
                } else if ( attrs[i].name === "label" && ignore.indexOf( this.currentType ) === -1 ){
                    code += ' label=""';
                }

            }

            if ( code.search( 'required' ) === -1 && ignore.indexOf( this.currentType ) === -1 ){
                code += ' required="false"';
            }

            code += ']';

            if ( this.currentType === 'col' ){
                code += '[/col]';
            } else if ( this.currentType === 'row' ){
                code += '[/row]';
            }

            $form.trigger( 'reset' );

            return code;

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
        },

        makeField: function () {
            var code = this.buildCode();
            this.insert( code );
            this.active = false;
        }

    };

    $(function () {
        wpghFormBuilder.init();
    });

})(jQuery);