wpghEmailEditor = wpghEmailEditor || {};

var wpghHTMLBlock;
( function( $, editor ) {

    wpghHTMLBlock = {

        blockType: 'html',
        htmlCode: null,

        content: null,

        init : function () {

            this.content  = $( '#html-content' );

            // this.content.on( 'change', function ( e ) {
            //     editor.getActive().find('.content-inside').html( $(this).val().trim() );
            // });

            $(document).on( 'madeActive', function (e, block, blockType ) {
                if ( wpghHTMLBlock.blockType === blockType ){

                    // wpghHTMLBlock.createEditor();
                    // console.log( {in:'text', blockType: blockType} );
                    wpghHTMLBlock.parse( block );
                }
            });

        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        parse: function ( block ) {

            // console.log( {block: block} );

            if ( ! this.htmlCode ){

                this.htmlCode = CodeMirror.fromTextArea( document.getElementById("html-content"), {
                    lineNumbers: true,
                    lineWrapping: true,
                    mode: "text/html",
                    matchBrackets: true,
                    indentUnit: 4,
                    specialChars: /[\u0000-\u001f\u007f-\u009f\u00ad\u061c\u200b-\u200f\u2028\u2029\ufeff]/,
                    onChange: function ( cm ) {
                        editor.getActive().find('.content-inside').html( cm.getValue() );
                    }
                });

                this.htmlCode.on( 'change', function ( cm ) {
                    editor.getActive().find('.content-inside').html( cm.getValue() );
                } );

            }

            this.htmlCode.setSize( this.content.parent().width(), this.content.parent().height() );
            // this.htmlCode.setSize( this.content.parent().width(), null );
            // this.content.val( block.find('.content-inside').html().trim() );
            this.htmlCode.setValue( block.find('.content-inside').html().trim() );

        }

    };

    $(function(){
        wpghHTMLBlock.init();
    })

})( jQuery, wpghEmailEditor );