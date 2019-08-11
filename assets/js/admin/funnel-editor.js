( function( $, funnel ) {

    $.extend( funnel, {

        editorID: '#normal-sortables',
        editor: null,
        sortables: null,
        draggables: null,
        curStep: null,
        curHTML: null,
        curOrder: 0,
        reportData: null,
        sidebar: null,

        init: function () {

            var self = this;

            /* Create Editor */
            this.editor = $( this.editorID );

            /* Bind Delete */
            this.editor.on( 'click', 'button.delete-step', function ( e ) {
                self.deleteStep( this );
            } );

            /* Bind Duplicate */
            this.editor.on( 'click', 'button.duplicate-step', function ( e ) {
                self.duplicateStep( this );
            } );

            /* Activate Spinner */
            $('#funnel-form').on('submit', function( e ){
                e.preventDefault();
                self.save( $(this) );
            });

            $( document ).on( 'change', '.auto-save', function( e ){
                e.preventDefault();
                self.save( $('#funnel-form') );
            });

            $( document ).on('GroundhoggModalClosed', function( e ){
                e.preventDefault();
                self.save( $('#funnel-form') );
            });

            $( document ).on('GroundhoggModalClosed', function( e ){
                e.preventDefault();
                self.save( $('#funnel-form') );
            });

            $(document).on( 'click', '#enter-full-screen', function(e){
                $( 'html' ).toggleClass( 'full-screen' );
                self.editorSizing();
            } );

            if ( window.innerWidth > 600 ){
                this.makeSortable();
                this.makeDraggable();
            }

            this.initReporting();

            this.editorSizing();

            $( window ).resize(function() {
                self.editorSizing();
            });

            $( '#add-contacts-button' ).click( function(){
                self.addContacts();
            });

            $( '#copy-share-link' ).click( function ( e ) {
                e.preventDefault();
                prompt( "Copy this link.", $('#share-link').val() );
            });

            $( document ).on( 'click', '.postbox .collapse', function ( e ) {
                var $step = $( this.parentNode );
                if ( $step.hasClass( 'closed' ) ){
                    self.expandStep( $step );
                } else {
                    self.collapseStep( $step );
                }
            } );

            $( '#postbox-container-1 .hndle' ).click( function ( e ) {
                var $metabox = $( this.parentNode );
                $metabox.toggleClass( 'closed' );
                self.sidebar.updateSticky();
            })

        },

        editorSizing: function (){
            $( '.funnel-editor-header').width( $('#poststuff').width() );
            $( '#postbox-container-2').height( $('#wpbody').height() - 80 );
            // $( '#postbox-container-1' ).height( $(window).height() - (32 - 56));

            this.sidebar = new StickySidebar( '#postbox-container-1' , {
                topSpacing: $( 'html' ).hasClass( 'full-screen' ) ? 47 : 78,
                bottomSpacing: 0
            });

            $( '#normal-sortables' ).css( 'visibility', 'visible' );
        },

        initReporting: function(){

            var $reporting = $("#reporting-toggle");

            $reporting.on( 'input', function(){
                if ( $(this).is(':checked')){
                    $('.step-reporting').removeClass('hidden');
                    $('.step-edit').addClass('hidden');
                } else {
                    $('.step-reporting').addClass('hidden');
                    $('.step-edit').removeClass('hidden');
                }
            });

            if($reporting.is( ':checked')){
                $('.step-reporting').removeClass('hidden');
                $('.step-reporting').removeClass('hidden');
                $('.step-edit').addClass('hidden');
            }

            $('#custom_date_range_start').datepicker({
                changeMonth: true,
                changeYear: true,
                maxDate:0,
                dateFormat:'d-m-yy'
            });

            $('#custom_date_range_end').datepicker({
                changeMonth: true,
                changeYear: true,
                maxDate:0,
                dateFormat:'d-m-yy'
            });

            $('#date_range').change(function(){
                if($(this).val() === 'custom'){
                    $('#custom_date_range_end').removeClass('hidden');
                    $('#custom_date_range_start').removeClass('hidden');
                } else {
                    $('#custom_date_range_end').addClass('hidden');
                    $('#custom_date_range_start').addClass('hidden');
                }});
        },

        save: function ( $form ) {

            var self = this;

            showSpinner();

            var fd = $form.serialize();
            fd = fd +  '&action=gh_save_funnel_via_ajax';

            adminAjaxRequest( fd, function ( response ) {

                handleNotices( response.data.notices );
                // console.log( response.data.notices );

                hideSpinner();

                $( '#normal-sortables' ).html( response.data.data.steps );

                FunnelChart.data = response.data.data.chartData;
                if( ! $( '#funnel-chart' ).hasClass( 'hidden' ) ){
                    FunnelChart.draw();
                }

                $( document ).trigger( 'new-step' );
            } );
        },

        /**
         * Inserts a dummy step wherever the given class is
         *
         * @param e string class name
         */
        insertDummyStep: function (e) {
            /* Check if we actually dropped it in */
            if ( this.editor.find(e).length > 0 ){

                this.editor.find(e).replaceWith(
                    '<div id="temp-step" class="postbox step replace-me" style="width: 500px;margin-right: auto;margin-left: auto;"><h3 class="hndle">Please Wait...</h3><div class="inside">Loading content...</div></div>'
                );

                return true;
            }

            return false
        },

        /**
         * Replaces the dummy step with the given html
         *
         * @param html
         */
        replaceDummyStep: function (html) {
            this.editor.find('.replace-me').replaceWith(html);
            $(document).trigger('new-step');
        },

        /**
         * The callback when the draggable event is finished. Dragging in a new step
         *
         * @param e
         */
        convertDraggableToStep: function ( e ) {

            var step_type = e.id;

            if ( this.insertDummyStep('.ui-draggable') ){

                var order = $('.step').index($('#temp-step')) + 1;
                var data = {action: "wpgh_get_step_html", step_type: step_type, step_order: order, funnel_id:funnel.id};
                this.getStepHtml( data );
            }
        },

        /**
         * Initializes the draggable state of the steps
         */
        makeDraggable: function () {
            var self=this;

            this.draggables = $(".ui-draggable").draggable({
                connectToSortable: ".ui-sortable",
                helper: "clone",
                stop: function ( e, ui ) {
                    /* double check we dropped in a step... */
                    if ( ui.helper.closest( '#normal-sortables' ).length > 0 ){
                        console.log( ui.helper.parent() );
                        self.convertDraggableToStep( this )
                    }

                }
            });
        },

        makeSortable: function () {
            this.sortables = $(".ui-sortable").sortable({
                placeholder: "sortable-placeholder",
                connectWith: ".ui-sortable",
                axis: 'y',
                start: function (e, ui) {
                    ui.helper.css('left', (ui.item.parent().width() - ui.item.width()) / 2);
                    ui.placeholder.height(ui.item.height());
                    ui.placeholder.width(ui.item.width());
                }
            });

            this.sortables.disableSelection();
        },

        /**
         * Given an element delete it
         *
         * @param e node
         */
        deleteStep: function (e) {

            showSpinner();

            var step = $(e).closest('.step');

            var result = confirm( "Are you sure you want to delete this step? Any contacts currently waiting will be moved to the next action." );

            if (result) {

                adminAjaxRequest(
                    {action: "wpgh_delete_funnel_step", step_id: step.attr( 'id' ) },
                    function ( result ) {
                        hideSpinner();
                        step.remove();
                    }
                );
            } else {
                hideSpinner();
            }
        },

        /**
         * Given an element, duplicate the step and
         * Add it to the funnel
         *
         * @param e node
         */
        duplicateStep: function ( e ) {
            var step = $(e).closest('.step');

            $('<div class="replace-me"></div>').insertAfter( step );
            this.insertDummyStep( '.replace-me' );

            var data = {action: "wpgh_duplicate_funnel_step", step_id: step.attr( 'id' ) };
            this.getStepHtml( data )

        },

        /**
         * Performs an ajax call and replaces
         *
         * @param obj
         */
        getStepHtml: function (obj) {

            var self = this;

            adminAjaxRequest( obj, function ( response ) {
                self.curHTML = response.data.data.html;
                self.replaceDummyStep(self.curHTML);
            } );
        },

        addContacts: function () {

            var tags    = $( '#add_contacts_to_funnel_tag_picker' ).val();

            if ( ! tags ){
                alert( 'Please select at least 1 tag.' );
                return;
            }

            var stepId = $( '#add_contacts_to_funnel_step_picker' ).val();

            if ( ! stepId ){
                alert( 'Please select at funnel step.' );
                return;
            }

            showSpinner();
            adminAjaxRequest( { action: 'gh_add_contacts_to_funnel', tags: tags, step: stepId }, function ( response ) {
                hideSpinner();
            } );
        },

        /**
         * Collapse a step
         *
         * @param $step jQuery object
         */
        collapseStep: function( $step ) {
            console.log( $step );
            $step.addClass( 'closed' );
            $step.find( '.collapse-input' ).val( '1' )
        },

        /**
         * Expand a step
         *
         * @param $step jQuery object
         */
        expandStep: function( $step ) {
            $step.removeClass( 'closed' );
            $step.find( '.collapse-input' ).val( '' )
        }
    } );

    $(function(){
        funnel.init();
    });

})( jQuery, Funnel );