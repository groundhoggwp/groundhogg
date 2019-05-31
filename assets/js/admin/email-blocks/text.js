
var TextBlock = {};

( function( $, editor, block ) {

    $.extend( block, {

        blockType: 'text',
        pFont: null,
        pSize: null,
        h1Font: null,
        h1Size: null,
        h2Font: null,
        h2Size: null,
        editor: null,

        init : function () {

            var self = this;

            this.pFont  = $( '#p-font' );
            this.pFont.on( 'change', function ( e ) {
                editor.getActive().find( '.text_block' ).children().first().css('font-family', $(this).val() );
            });

            this.pSize  = $( '#p-size' );
            this.pSize.on( 'change', function ( e ) {
                editor.getActive().find( '.text_block' ).children().first().css('font-size', $(this).val() + 'px' );
            });

            this.h1Font = $( '#h1-font' );
            this.h1Font.on( 'change', function ( e ) {
                editor.getActive().find('h1').css('font-family', $(this).val() );
            });

            this.h1Size = $( '#h1-size' );
            this.h1Size.on( 'change', function ( e ) {
                editor.getActive().find('h1').css('font-size', $(this).val() + 'px' );
            });

            this.h2Font = $( '#h2-font' );
            this.h2Font.on( 'change', function ( e ) {
                editor.getActive().find('h2').css('font-family', $(this).val() );
            });

            this.h2Size = $( '#h2-size' );
            this.h2Size.on( 'change', function ( e ) {
                editor.getActive().find('h2').css('font-size', $(this).val() + 'px' );
            });

            $(document).on( 'madeActive', function (e, block, blockType ) {
                if ( self.blockType === blockType ){
                    self.parse( block );
                }
            });

        },

        setupEditor: function()
        {
            var self = this;

            self.editor = tinyMCE.get( 'text-content' );
            self.editor.on( 'change input', function () {
                editor.getActive().find( '.text_block' ).children().first().html( self.editor.getContent() );
                self.parse( editor.getActive(), false );
            });
        },


        /**
         * A jquery implement block.
         *
         * @param block $
         * @param parseContent
         */
        parse: function ( block, parseContent=true ) {

            var self = this;

            if ( ! self.editor ){
                this.setupEditor();
            }

            if ( parseContent ){
                self.editor.setContent( block.find( '.text_block' ).children().first().html() );
            }

            this.pFont.val( block.find( '.text_block' ).children().first().css( 'font-family' ).replace(/"/g, '') );
            this.pSize.val( block.find( '.text_block' ).children().first().css( 'font-size' ).replace('px', '') );
            try{ this.h1Font.val( block.find('h1').css( 'font-family' ).replace(/"/g, '') ); } catch (e){}
            try{ this.h1Size.val( block.find('h1').css( 'font-size' ).replace('px', '') ); } catch (e){}
            try{ this.h2Font.val( block.find('h2').css( 'font-family' ).replace(/"/g, '') ); } catch (e){}
            try{ this.h2Size.val( block.find('h2').css( 'font-size' ).replace('px', '') ); } catch (e){}
        }

    } );

    $(function(){
        block.init();
    });

})( jQuery, EmailEditor, TextBlock );