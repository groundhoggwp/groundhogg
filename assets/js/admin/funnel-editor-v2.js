( function( $, funnel, modal, charts ) {

    $.extend( funnel, {

        sortables: null,
        reportData: null,

        getSteps: function(){
            return $( '#postbox-container-1' );
        },

        getSettings: function() {
            return $( '.step-settings' );
        },

        init: function () {

            var self = this;

            var $document = $( document );
            var $form = $('#funnel-form');
            var $steps = self.getSteps();
            var $settings = self.getSettings();

            $document.on( 'change input', '.step-title-large', function () {
                var $title = $(this);
                var id = $title.attr( 'data-id' );
                var $step = $( '#' + id );
                $step.find( '.step-title' ).text( $title.val() )

            } );

            $document.on( 'click', '#postbox-container-1 .step', function ( e ) {
                self.makeActive( this.id );
            } );

            $document.on( 'click', 'td.step-icon', function ( e ) {

                var $activeStep = $steps.find( '.active' );
                $( '<div class="replace-me"></div>' ).insertAfter( $activeStep );

                var $icon   = $(this);
                var $type   = $icon.find( '.wpgh-element' );
                var type    = $type.attr( 'id' );
                var order = $steps.index( $activeStep ) + 1;

                var data = {
                    action:     "wpgh_get_step_html",
                    step_type:  type,
                    after_step: $activeStep.attr( 'id' ),
                    funnel_id:  self.id,
                    version: 2
                };

                self.getStepHtml( data );

            } );

            /* Bind Delete */
            $document.on( 'click', 'button.delete-step', function ( e ) {
                self.deleteStep( this.parentNode.parentNode.id );
            } );

            /* Bind Duplicate */
            $document.on( 'click', 'button.duplicate-step', function ( e ) {
                self.duplicateStep( this.parentNode.parentNode.id );
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
            } );

            if ( window.innerWidth > 600 ){
                this.makeSortable();
            }

            this.initReporting();

            $( '#add-contacts-button' ).click( function(){
                self.addContacts();
            });

            $( '#copy-share-link' ).click( function ( e ) {
                e.preventDefault();
                prompt( "Copy this link.", $('#share-link').val() );
            });

        },

        initReporting: function(){

            var $reporting = $("#reporting-toggle");

            $reporting.on( 'input', function(){
                if ( $(this).is(':checked')){
                    $( 'html' ).addClass( 'reporting-enabled' );
                } else {
                    $( 'html' ).removeClass( 'reporting-enabled' );
                }
            });

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
            fd = fd +  '&action=gh_save_funnel_via_ajax&version=2';

            adminAjaxRequest( fd, function ( response ) {
                handleNotices( response.data.notices );
                hideSpinner();
                self.getSettings().html( response.data.data.settings );
                self.getSteps().html( response.data.data.sortable );

                console.log(response);

                charts.data = response.data.data.chartData;

                if( ! $( '#funnel-chart' ).hasClass( 'hidden' ) ){
                    charts.draw();
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
         * @param id int
         */
        deleteStep: function ( id ) {

            showSpinner();

            var $step = $( '#' + id );

            var result = confirm( "Are you sure you want to delete this step? Any contacts currently waiting will be moved to the next action." );

            if (result) {
                adminAjaxRequest(
                    { action: "wpgh_delete_funnel_step", step_id: id },
                    function ( result ) {
                        hideSpinner();
                        $step.remove();
                        var sid = '#settings-' + id;
                        var $step_settings = $( sid );
                        $step_settings.remove();
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
         * @param id int
         */
        duplicateStep: function ( id ) {
            var $step = $( '#' + id );
            $('<div class="replace-me"></div>').insertAfter( $step );
            var data = { action: "wpgh_duplicate_funnel_step", step_id: id, version: 2 };
            this.getStepHtml( data )
        },

        /**
         * Performs an ajax call and replaces
         *
         * @param obj
         */
        getStepHtml: function (obj) {
            var self = this;
            var $steps = self.getSteps();
            var $settings = self.getSettings();
            showSpinner();
            adminAjaxRequest( obj, function ( response ) {
                $steps.find('.replace-me').replaceWith( response.data.data.sortable );
                $settings.append( response.data.data.settings );
                self.makeActive( response.data.data.id );
                modal.close();
                hideSpinner();
                $(document).trigger('new-step');
            } );
        },

        /**
         * Make the given step active.
         *
         * @param id
         */
        makeActive : function ( id ){
            var self = this;

            var $steps = self.getSteps();
            var $settings = self.getSettings();

            $settings.find( '.step' ).addClass( 'hidden' );
            $settings.find( '.step' ).removeClass( 'active' );
            $steps.find( '.step' ).removeClass( 'active' );
            $steps.find( '.is_active' ).val( null );

            var $step = $( '#' + id );
            $step.addClass( 'active' );
            $step.find( '.is_active' ).val(1);

            var sid = '#settings-' + $step.attr( 'id' );
            var $step_settings = $( sid );

            $step_settings.removeClass( 'hidden' );
            $step_settings.addClass( 'active' );
        },
    } );

    $(function(){
        funnel.init();
    });

})( jQuery, Funnel, GroundhoggModal, FunnelChart );