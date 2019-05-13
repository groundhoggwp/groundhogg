
var ImageBlock = {};

( function( $, editor, block ) {

    $.extend( block, {

        blockType: 'image',

        src: null,
        alt: null,
        title: null,
        width: null,
        align: null,
        link: null,

        init : function () {

            var self = this;

            this.src  = $( '#image-src' );
            this.src.on( 'change', function ( e ) {
                editor.getActive().find('img').attr('src', $(this).val());
            });

            this.alt  = $( '#image-alt' );
            this.alt.on( 'change', function ( e ) {
                editor.getActive().find('img').attr('alt', $(this).val());
            });

            this.width = $( '#image-width' );
            this.width.on( 'change input', function ( e ) {
                editor.getActive().find('img').css('width',$(this).val() + '%' );
                editor.getActive().find('img').attr('width', editor.getActive().find('img').width() );
            });

            this.title = $( '#image-title' );
            this.title.on( 'change', function ( e ) {
                editor.getActive().find('img').attr('title', $(this).val());
            });

            this.align = $( '#image-align' );
            this.align.on( 'change', function ( e ) {
                editor.getActive().find('.image-wrapper').css('text-align', $(this).val() );
            });

            this.link = $( '#image-link' );
            this.link.on( 'change', function ( e ) {
                editor.getActive().find('a').attr('href', $(this).val() );
            });

            $(document).on( 'madeActive', function (e, block, blockType ) {

                if ( self.blockType === blockType ){

                    // wpghImageBlock.createEditor();
                    // console.log( {in:'text', blockType: blockType} );
                    self.parse( block );
                }
            });
        },

        /**
         * A jquery implement block.
         *
         * @param block $
         */
        parse: function ( block ) {

            this.src.val( block.find('img').attr('src') );
            this.alt.val( block.find('img').attr('alt') );
            this.title.val( block.find('img').attr('title') );
            this.align.val( block.find('.image-wrapper').css('text-align') );
            this.width.val( Math.ceil( ( block.find('img').width() / block.find('img').closest('div').width() ) * 100 ) );
            this.link.val( block.find('a').attr('href') );

        }

    } );

    $(function(){
        block.init();
    })

})( jQuery, EmailEditor, ImageBlock );