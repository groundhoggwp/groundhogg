wpghEmailEditor = wpghEmailEditor || {};

var wpghSpacerBlock;
( function( $, editor ) {

    wpghSpacerBlock = {
        blockType: 'spacer',
        height: null,
        init : function () {

            this.height  = $( '#spacer-size' );
            this.height.on( 'change input', function ( e ) {
                editor.getActive().find('.spacer').attr('height', $(this).val() );
            });

            $(document).on( 'madeActive', function (e, block, blockType ) {

                if ( wpghSpacerBlock.blockType === blockType ){

                    // wpghSpacerBlock.createEditor();
                    // console.log( {in:'text', blockType: blockType} );
                    wpghSpacerBlock.parse( block );
                }

            });
        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        parse: function ( block ) {

            this.height.val( block.find('.spacer').height() );

        }

    };

    $(function(){
        wpghSpacerBlock.init();
    })

})( jQuery, wpghEmailEditor );