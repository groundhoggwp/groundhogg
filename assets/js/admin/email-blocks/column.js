wpghEmailEditor = wpghEmailEditor || {};

var wpghColumnBlock;
( function( $, editor ) {

    wpghColumnBlock = {
        blockType: 'column',
        // height: null,
        init : function () {


            // this.height.on( 'change', function ( e ) {
            //     editor.getActive().find('.column').attr('height', $(this).val() );
            // });

            // $(document).on( 'madeActive', function (e, block, blockType ) {
            //
            //     if ( wpghColumnBlock.blockType === blockType ){
            //
            //         // wpghColumnBlock.createEditor();
            //         //console.log( {in:'text', blockType: blockType} );
            //         wpghColumnBlock.parse( block );
            //     }
            //
            // });
        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        // parse: function ( block ) {
        //
        //     this.height.val( block.find('.column').height() );
        //
        // }

    };

    $(function(){
        wpghColumnBlock.init();
    })

})( jQuery, wpghEmailEditor );