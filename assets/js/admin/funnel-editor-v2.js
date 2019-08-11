( function( $, funnel ) {

    $.extend( funnel, {

        sortables: null,
        reportData: null,

        init: function () {

            var self = this;

            var $document = $( document );
            var $form = $('#funnel-form');
            var $steps = $( '#postbox-container-1' );
            var $settings = $( '.step-settings' );

            $document.on( 'click', '#postbox-container-1 .step', function ( e ) {
                $settings.find( '.step' ).addClass( 'hidden' );
                $settings.find( '.step' ).removeClass( 'active' );

                $steps.find( '.step' ).removeClass( 'active' );

                var $postbox = $(this);
                $postbox.addClass( 'active' );

                var id = '#settings-' + $postbox.attr( 'id' );
                var $step_settings = $( id );

                $step_settings.removeClass( 'hidden' );
                $step_settings.addClass( 'active' );
            } );

            $document.on( 'click', '.add-step', function ( e ) {
                var $button = $(this);
                var $step = $button.closest( '.step' );
            });

            $document.on( 'click', 'td.step-icon', function ( e ) {
                var $icon   = $(this);
                var $type   = $icon.find( '.wpgh-element' );
                var type    = $type.attr( 'id' );
                var order = $('#postbox-container-1.step').index($('#temp-step')) + 1;
                var data = {
                    action:     "wpgh_get_step_html",
                    step_type:  type,
                    step_order: order,
                    funnel_id:  self.id
                };

                // this.getStepHtml( data );

            } );

            /* Bind Delete */
            $document.on( 'click', 'button.delete-step', function ( e ) {
                self.deleteStep( this );
            } );

            /* Bind Duplicate */
            $document.on( 'click', 'button.duplicate-step', function ( e ) {
                self.duplicateStep( this );
            } );

            /* Activate Spinner */
            $form.on('submit', function( e ){
                e.preventDefault();
                self.save( $form );
            });

            $document.on( 'change', '.auto-save', function( e ){
                e.preventDefault();
                self.save( $('#funnel-form') );
            });

            $document.on( 'click', '#enter-full-screen', function(e){
                $( 'html' ).toggleClass( 'full-screen' );
                self.editorSizing();
            } );

            if ( window.innerWidth > 600 ){
                this.makeSortable();
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

        },

        editorSizing: function (){
            $( '.funnel-editor-header').width( $('#poststuff').width() );
            $( '#postbox-container-2').height( $('#wpbody').height() - 80 );
            // $( '#postbox-container-1' ).height( $(window).height() - (32 - 56));

            // this.sidebar = new StickySidebar( '#postbox-container-1' , {
            //     // topSpacing: $( 'html' ).hasClass( 'full-screen' ) ? 47 : 78,
            //     bottomSpacing: 0
            // });

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
    } );

    $(function(){
        funnel.init();
    });

})( jQuery, Funnel );