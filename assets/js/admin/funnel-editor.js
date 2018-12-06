var wpghDoingAutoSave = false;

var wpghFunnelEditor;

( function( $ ) {

    wpghFunnelEditor = {

        editorID: '#normal-sortables',
        editor: null,
        sortables: null,
        draggables: null,
        curStep: null,
        curHTML: null,
        curOrder: 0,
        reportData: null,

        init: function () {

            /* Create Editor */
            this.editor = $( this.editorID );

            /* Bind Delete */
            this.editor.on( 'click', 'button.delete-step', function ( e ) {
                wpghFunnelEditor.deleteStep( this );
            } );

            /* Bind Duplicate */
            this.editor.on( 'click', 'button.duplicate-step', function ( e ) {
                wpghFunnelEditor.duplicateStep( this );
            } );

            /* init sidebar */
            // $('.sidebar').stickySidebar({
            //     topSpacing: 40,
            //     bottomSpacing: 40
            // });

            /* Activate Spinner */
            $('form').on('submit', function( e ){
                e.preventDefault();
                wpghFunnelEditor.save();
            });

            $( document ).on('wpghModalClosed', function( e ){
                e.preventDefault();
                wpghFunnelEditor.save();
            });

            if ( window.innerWidth > 600 ){
                this.makeSortable();
                this.makeDraggable();
            }

            this.initReporting();

            this.editorSizing();

            $( window ).resize(function() {
                wpghFunnelEditor.editorSizing();
            });

            $( '#add-contacts-button' ).click( function(){
                wpghFunnelEditor.addContacts();
            });

            $( '#copy-share-link' ).click( function ( e ) {
                e.preventDefault();
                prompt( "Copy This Link", $('#share-link').val() );
            })

        },

        editorSizing: function (){
            $( '.funnel-editor-header').width( $('#poststuff').width() );
            $( '#postbox-container-2').height( $('#postbox-container-1').height() - 80 );
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

        save: function () {
            // e.preventDefault();
          //  var chart = loadChart();

            $('.spinner').css('visibility','visible');

            var fd = $('form').serialize();

            fd = fd +  '&action=gh_save_funnel_via_ajax';

            var ajaxCall = $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: fd,
                success: function ( response ) {
                    // response = JSON.parse(response);
                    // console.log( response );
                    $( '#notices' ).html( response.notices );
                    $( '#normal-sortables' ).html( response.steps );
                    $( '#confirm' ).fadeOut( 300 );
                    $( '.spinner' ).css( 'visibility','hidden' );
                    wpghFunnelEditor.makeDismissible();
                    $(document).trigger('wpghAddedStep');
                    funnelChart.data = response.chartData;

                    if( ! $( '#funnel-chart' ).hasClass( 'hidden' ) ){
                        funnelChart.draw();
                    }
                }
            });

        },

        makeDismissible: function()
        {
            $( "<button type='button' class='notice-dismiss'><span class='screen-reader-text'>Dismiss This Notice</span></button>" ).appendTo( '.is-dismissible' );
            $( '.notice-dismiss' ).on( 'click', function ( e ) {
                $(this).parent().fadeOut( 500, function () {
                    $(this).remove();
                } );
            } )
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
            $(document).trigger('wpghAddedStep');
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
                var data = {action: "wpgh_get_step_html", step_type: step_type, step_order: order};
                this.getStepHtml(data);

            }
        },

        /**
         * Initializes the draggable state of the steps
         */
        makeDraggable: function () {
            this.draggables = $(".ui-draggable").draggable({
                connectToSortable: ".ui-sortable",
                helper: "clone",
                stop: function ( e, ui ) {
                    /* double check we dropped in a step... */
                    if ( ui.helper.closest( '#normal-sortables' ).length > 0 ){
                        console.log( ui.helper.parent() );
                        wpghFunnelEditor.convertDraggableToStep( this )
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
            var step = $(e).closest('.step');
            var result = confirm("Are you sure you want to delete this step?");
            if (result) {
                var ajaxCall = $.ajax({
                    type: "post",
                    url: ajaxurl,
                    data: {action: "wpgh_delete_funnel_step", step_id: step.attr( 'id' ) },
                    success: function (result) {
                        // console.log(step.attr( 'id' ));
                        step.remove();
                    }
                });
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
            var ajaxCall = $.ajax({
                type: "post",
                url: ajaxurl,
                data: obj,
                success: function (html) {
                    wpghFunnelEditor.curHTML = html;
                    wpghFunnelEditor.replaceDummyStep(html);
                }
            });
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

            $('.spinner').css('visibility','visible');

            var ajaxCall = $.ajax({
                type: "post",
                url: ajaxurl,
                data: { action: 'gh_add_contacts_to_funnel', tags: tags, step: stepId },
                success: function ( response ) {
                    $( '.spinner' ).css( 'visibility','hidden' );
                    $( '.add-contacts-response' ).html( response );
                    $( '.add-contacts-response' ).removeClass( 'hidden' );
                    wpghFunnelEditor.makeDismissible();
                }
            });

        }

    };
    $(function(){wpghFunnelEditor.init();})
})( jQuery );