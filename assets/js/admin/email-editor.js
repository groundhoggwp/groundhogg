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

( function( $, editor ) {

    $.extend( editor, {

        editor:     null,
        actions:    null,
        settings:   null,
        active:     null,
        alignment:  null,
        sidebar:    null,
        htmlCode:   null,

        /**
         * Initialize the editor
         */
        init: function () {

            var self = this;

            self.editor  = $( '#email-body' );
            self.actions = $( '#editor-panel' );
            self.settings = $( '#settings-panel' );

            $( '#update_and_test' ).click( function (e) {
                $( '#send-test' ).val( 'yes' );
            });

            self.editor.on( 'click', function (e) {
                e.preventDefault();
                self.feed( e.target );
            } );

            self.editor.on( 'click', 'span.dashicons-admin-page', function ( e ) {
                e.preventDefault();
                self.duplicateBlock( e.target );
            });

            self.editor.on( 'click', 'span.dashicons-trash', function ( e ) {
                e.preventDefault();
                self.deleteBlock( e.target );
                self.feed( null );
            });

            /* Activate Spinner */
            $('#email-form').on( 'submit', function( e ){
                e.preventDefault();
                self.save( $(this) );
            });

            $( '.row' ).wpghToolBar();

            self.alignment = $( '#email-align' );
            self.alignment.on( 'change', function () {
                var email =  $( '#email-inside' );
                if ( $( this ).val() === 'left' ){
                    email.css( 'margin-left', '0' );
                    email.css( 'margin-right', 'auto' );
                } else {
                    email.css( 'margin-left', 'auto' );
                    email.css( 'margin-right', 'auto' );
                }
            } );

            /* Size the editor to full screen if being views in an Iframe. */
            // TODO
            if ( self.inFrame() ){

                if ( typeof parent.EmailStep  != "undefined" ){
                    $( document ).on( 'change keydown keyup', function ( e ) {
                        parent.EmailStep.changesSaved = false;
                    } );

                    $(  parent.document ).on( 'click', '#popup-close-footer', function( e ){
                        self.save( $('#email-form') );
                    } );

                    parent.EmailStep.newEmailId = self.id;
                }

            }

            self.editorSizing();

            $( window ).resize(function() {
                self.editorSizing();
            });

            $('#editor-toggle').change(function(e){
                if ($(this).is(':checked')) {

                    if ( ! self.htmlCode ){
                        self.initCodeMirror();
                    }

                    $( 'body' ).addClass( 'html-view' );

                    $('#email-content').hide();
                    $('#html-editor').show();

                    self.prepareEmailHTML();
                    // self.htmlCode.doc.setValue( $( '#email-inside' ).html() );
                    self.htmlCode.doc.setValue( html_beautify( $('#email-inside').html(), { indent_with_tabs:true } ) );
                } else {
                    $( 'body' ).removeClass( 'html-view' );
                    $( '.row' ).wpghToolBar();
                    $('#html-editor').hide();
                    $('#email-content').show();
                }
            }).change();

            self.makeSortable();
            self.makeDraggable();
            self.makeResizable();

            this.sidebar = new StickySidebar( '#postbox-container-1' , {
                topSpacing: self.inFrame() ? 47 : 78,
                bottomSpacing: 0
            });
        },

        prepareEmailHTML : function()
        {
            var $email = $('#email-content');
            $('wpgh-toolbar').remove();
            $email.find('div').removeAttr( 'contenteditable' ).removeClass( 'active' );
        },

        /**
         * Code Mirror
         */
        initCodeMirror: function()
        {
            var self = this;

            var editorSettings = wp.codeEditor.defaultSettings ? _.clone( wp.codeEditor.defaultSettings ) : {};
            editorSettings.codemirror = _.extend(
                {},
                editorSettings.codemirror,
                {
                    indentUnit: 4,
                    tabSize: 4
                }
            );

            self.htmlCode = wp.codeEditor.initialize( $('#html-code'), editorSettings ).codemirror;
            // self.htmlCode = self.htmlCode.codemirror;

            self.htmlCode.on('change', function() {
                $('#email-inside').html(self.htmlCode.doc.getValue());
            });

            self.htmlCode.setSize( null, self.editor.height() );
        },

        inFrame: function () {
            try {
                return window.self !== window.top;
            } catch (e) {
                return true;
            }
        },

        save: function ( $form ) {

            var self = this;

            showSpinner();

            self.prepareEmailHTML();

            $('#content').val( $('#email-inside').html() );

            var fd = $form.serialize();

            fd = fd +  '&action=gh_update_email';

            adminAjaxRequest( fd, function ( response ) {

                handleNotices( response.data.notices );
                hideSpinner();

                var content = response.data.data.data.content;
                $('#email-inside').html( content );

                $( '.row' ).wpghToolBar();
                if ( self.inFrame() && typeof  parent.EmailStep != "undefined" ){
                    parent.EmailStep.changesSaved = true;
                }

                $( '#send-test' ).val( false );
            } );
        },

        editorSizing: function (){
            $('.editor-header').width( $('#poststuff').width() );
            $('#email-body').css( 'min-height', $('#postbox-container-1').height() );
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
                    var html = $( '.' + block_type + '-template' ).html();
                    $('#temp-html').html( html );
                },
                stop: function ( e, ui ) {
                    $('#email-content').find('.email-draggable').replaceWith( $('#temp-html').html() );
                }
            });
        },
        
        makeResizable: function()
        {

            var self = this;

            var listener = this.resizeListener = {};

            var $postbody = $( "#post-body" );
            var $sidebar = $( "#postbox-container-1" );
            var $innerSidebar = $( ".inner-wrapper-sticky" );

            listener.sidebarWidth = $sidebar.width();

            $postbody.resizable({
                handles: "w",
                resize: function (e,ui) {
                    listener.change = ui.position.left - ui.originalPosition.left;
                    $sidebar.width( listener.sidebarWidth + listener.change );
                    $innerSidebar.width( listener.sidebarWidth + listener.change );
                    $postbody.css( 'margin-left', ( listener.sidebarWidth + listener.change + 1 ) + 'px' );
                    $sidebar.css( 'margin-left',  -( listener.sidebarWidth + listener.change + 1 ) + 'px' );
                    $postbody.css( 'left', 0 );
                },
                stop:function (e,ui) {
                    listener.sidebarWidth = $sidebar.width();
                    self.sidebar.updateSticky();
                },
                start: function (e,ui) {
                    listener.sidebarWidth = $sidebar.width();
                },
                grid:10
            });
        },

        /**
         * Make the blocks draggable
         */
        makeClickable: function(){
            $( ".email-draggable" ).on( 'dblclick', function ( e ) {
                $('#email-content')
            });
        },

        hideBlockSettings: function (){
            this.editor.find( '.row' ).removeClass( 'active' );
            this.actions.find( '.postbox' ).addClass( 'hidden' );

            // Show regular settings
            this.settings.show();
            $(document).trigger( 'madeInactive' );
            this.sidebar.updateSticky();
        },

        /**
         * Show the block settings
         * Make the block active
         *
         * @param e
         */
        feed: function( e ) {

            if ( ! e ){
                this.hideBlockSettings();
                return;
            }

            /* Make Current Block Active*/
            if ( e.parentNode === null ){
                return;
            }

            var block = $( e ).closest( '.row' );

            /* check if already active */
            if ( block.hasClass( 'active' ) ){
                return;
            }

            if ( ! block.hasClass( 'row' ) ){
                this.hideBlockSettings();
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

            // Hide All Settings
            this.actions.find( '.postbox' ).addClass( 'hidden' );

            // Show block Settings
            this.actions.find( '#' + blockType + '-block-editor' ).removeClass( 'hidden' );

            // Hide Regular Settings Panel
            this.settings.hide();

            $(document).trigger( 'madeActive', [ block, blockType ] );

            this.sidebar.updateSticky();
        },

        /**
         * Delete a block
         *
         * @param e
         */
        deleteBlock: function( e ){
            $( e ).closest( '.row' ).remove();
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

    } );

    $(function () {
        editor.init();
    });

    var isResizing = false,
        lastDownX = 0;

    $(function () {
        var container = $('#post-body'),
            left = $('#postbox-container-1'),
            right = $('#post-body-content'),
            handle = $('#drag');

        handle.on('mousedown', function (e) {
            isResizing = true;
            lastDownX = e.clientX;
        });

        $(document).on('mousemove', function (e) {
            // we don't want to do anything if we aren't resizing.
            if (!isResizing)
                return;

            var offsetRight = container.width() - (e.clientX - container.offset().left);

            left.css('right', offsetRight);
            right.css('width', offsetRight);
        }).on('mouseup', function (e) {
            // stop resizing
            isResizing = false;
        });
    });


} )( jQuery, EmailEditor );