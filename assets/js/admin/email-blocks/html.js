wpghEmailEditor = wpghEmailEditor || {};

var wpghHTMLBlock;
( function( $, editor ) {

    wpghHTMLBlock = {

        blockType: 'html',

        content: null,

        init : function () {

            this.content  = $( '#html-content' );
            this.content.on( 'change', function ( e ) {
                editor.getActive().find('.content-inside').html( $(this).val().trim() );
            });

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
            this.content.val( block.find('.content-inside').html().trim() );

        }

    };

    $(function(){
        wpghHTMLBlock.init();
    })

})( jQuery, wpghEmailEditor );