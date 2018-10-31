(function( $ ) {

    $.fn.wpghToolBar = function() {

        var html =
        '<wpgh-toolbar class="action-icons">' +
            '<div>' +
                '<span class="dashicons dashicons-admin-page"></span>' +
                '<span class="dashicons dashicons-move handle"></span>' +
                '<span class="dashicons dashicons-trash"></span>' +
            '</div>' +
        '</wpgh-toolbar>';

        this.each(function() {

            var row = $( this );

            if ( row.find( 'wpgh-toolbar' ).length === 0 )
                row.prepend( html );

        });

        return this;

    };

}( jQuery ));


var wpghEmailEditor;

( function( $, editor ) {

    wpghEmailEditor = {

        editor:     null,
        actions:    null,
        active:     null,
        alignment: null,

        /**
         * Initialize the editor
         */
        init: function () {


            this.editor  = $( '#email-body' );
            this.actions = $( '#editor-actions' );

            this.editor.on( 'click', function (e) {
                e.preventDefault();
                wpghEmailEditor.feed( e.target );
            } );

            this.editor.on( 'click', 'span.dashicons-admin-page', function ( e ) {
                e.preventDefault();
                wpghEmailEditor.duplicateBlock( e.target );
            });

            this.editor.on( 'click', 'span.dashicons-trash', function ( e ) {
                e.preventDefault();
                wpghEmailEditor.deleteBlock( e.target );
            });

            this.makeSortable();
            this.makeDraggable();

            /* Activate Spinner */
            $('form').on( 'submit', function( e ){
                e.preventDefault();
                $('.spinner').css('visibility','visible');
                jQuery('.row').removeClass('active');
                jQuery('wpgh-toolbar').remove();
                wpghTextBlock.destroyEditor();
                $('#content').val( $('#email-inside').html() );
                $(this).unbind( 'submit' ).submit();
            });

            $('.sidebar').stickySidebar({
                topSpacing: 40,
                bottomSpacing: 40
            });

            $('.editor-actions-inner').stickySidebar({
                topSpacing: 32,
                bottomSpacing: 0
            });

            $( '.row' ).wpghToolBar();

            this.alignment = $( '#email-align' );
            this.alignment.on( 'change', function () {
                var email =  $( '#email-inside' );
                if ( $( this ).val() === 'left' ){
                    email.css( 'margin-left', '0' );
                    email.css( 'margin-right', 'auto' );
                } else {
                    email.css( 'margin-left', 'auto' );
                    email.css( 'margin-right', 'auto' );
                }
            } )

        },

        /**
         * Make the blocks sortable
         */
        makeSortable: function(){
            $( ".email-sortable" ).sortable({
                placeholder: "sortable-placeholder",
                axis: 'y',
                start: function(e, ui){
                    ui.placeholder.height(ui.item.height());
                },
                handle: '.handle',
                stop: function (e, ui) {
                }
            });
        },

        /**
         * Make the blocks draggable
         */
        makeDraggable: function(){
            $( ".email-draggable" ).draggable({
                connectToSortable: ".email-sortable",
                helper: "clone",
                start: function ( e, ui ) {
                    var el = this;
                    var block_type = el.id.replace( '-block', '' );
                    var html = $( '.' + block_type + '-template' ).children().first().clone();
                    $('#temp-html').html( html );
                },
                stop: function ( e, ui ) {
                    $('#email-content').find('.email-draggable').replaceWith( $('#temp-html').html() );
                }
            });
        },

        /**
         * Show the block settings
         * Make the block active
         *
         * @param e
         */
        feed: function( e ) {

            // console.log( {e: e} );

            /* Make Current Block Active*/
            if ( e.parentNode === null ){
                return;
            }

            var block = $( e ).closest( '.row' );

            /* check if already active */
            if ( block.hasClass( 'active' ) ){
                return;
            }

            // console.log( {e: block} );

            if ( ! block.hasClass( 'row' ) ){

                this.editor.find( '.row' ).removeClass( 'active' );
                this.actions.find( '.postbox' ).addClass( 'hidden' );
                this.actions.find( '#email-editor' ).removeClass( 'hidden' );

                $(document).trigger( 'madeInactive' );

                return;

            }

            this.active = block;

            /* Make all blocks inactive */
            this.editor.find( '.row' ).removeClass( 'active' );
            block.addClass( 'active' );
            var blockType = block.attr( 'data-block' );

            if ( typeof blockType === 'undefined' && typeof block !== 'undefined' ){

                /* backwards compat */
                var $content = block.find( '.content-wrapper' );
                var classes = $content.attr( 'class' );
                blockType = /\w+_block/.exec( classes )[0];
                blockType = blockType.replace( '_block', '' );

            }

            /* Hide All Settings */
            this.actions.find( '.postbox' ).addClass( 'hidden' );
            /* Show block Settings */
            this.actions.find( '#' + blockType + '-block-editor' ).removeClass( 'hidden' );

            $(document).trigger( 'madeActive', [ block, blockType ] );

            // console.log( { block_type: blockType, block: block });

        },

        /**
         * Delete a block
         *
         * @param e
         */
        deleteBlock: function( e ){
            $( e ).closest( '.row' ).remove()
        },

        /**
         * Duplicate a block
         *
         * @param e
         */
        duplicateBlock: function( e ){
            $(document).trigger( 'duplicateBlock' );
            $(e).closest('.row').removeClass('active');
            $(e).closest('.row').clone().insertAfter( $(e).closest('.row') );
        },

        getActive: function () {
            return this.active;
        }

    };

    $(function () {
        wpghEmailEditor.init();
    })

} )( jQuery );


//
// var WPGHEmailEditor = {};
//
// WPGHEmailEditor.init = function () {
//     this.content = jQuery( '#email-body' );
//     this.actions = jQuery( '#editor-actions' );
//     this.contentInside = jQuery( '#email-inside' );
//     this.textarea = jQuery( '#content' );
//     this.form = jQuery('form');
//     this.form.on('submit', WPGHEmailEditor.switchContent );
// };
//
// WPGHEmailEditor.switchContent = function( e ){
//     e.preventDefault();
//     jQuery('.spinner').css('visibility','visible');
//     jQuery('.row').removeClass('active');
//     jQuery('wpgh-toolbar').remove();
//     if ( WPGHEmailEditor.richText ){
//         WPGHEmailEditor.richText.simpleEditor().destroy();
//     }
//     WPGHEmailEditor.textarea.val( WPGHEmailEditor.contentInside.html() );
//     WPGHEmailEditor.form.unbind( 'submit' ).submit();
// };
//
// WPGHEmailEditor.getActive = function () {
//   return this.content.find( '.active' );
// };
//
// WPGHEmailEditor.hideActions = function (){
//   this.actions.find( '.postbox' ).addClass( 'hidden' );
// };
//
// WPGHEmailEditor.emailAlignment = jQuery( '#email-align' );
// WPGHEmailEditor.emailAlignment.apply = function(){
//
//     if ( jQuery( this ).val() === 'left' ){
//         jQuery( '#email-inside' ).css( 'margin-left', '0' );
//         jQuery( '#email-inside' ).css( 'margin-right', 'auto' );
//     } else {
//         jQuery( '#email-inside' ).css( 'margin-left', 'auto' );
//         jQuery( '#email-inside' ).css( 'margin-right', 'auto' );
//     }
//
// };
// WPGHEmailEditor.emailAlignment.change( WPGHEmailEditor.emailAlignment.apply );
//
//
// WPGHEmailEditor.emailOptions = jQuery( '#email-editor' );
// WPGHEmailEditor.showEmailOptions = function () {
//     this.showOptions( this.emailOptions );
// };
//
// WPGHEmailEditor.showOptions = function( el ){
//     this.actions.find( '.postbox' ).addClass( 'hidden' );
//     el.removeClass( 'hidden' );
// };
//
// WPGHEmailEditor.makeActive = function ( el ) {
//     if ( el.closest('#email-body').length ){
//         jQuery('.row').removeClass("active");
//     }
//
//     el.closest('.row').addClass('active');
//     this.active = el.closest('.row');
// };
//
// WPGHEmailEditor.richText = null;
// WPGHEmailEditor.active = null;
