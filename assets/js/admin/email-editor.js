jQuery(document).ready( function() {
    var emailSortables = jQuery( ".ui-sortable" ).sortable(
        {
            placeholder: "sortable-placeholder",
            connectWith: ".ui-sortable",
            axis: 'y',
            start: function(e, ui){
                // ui.helper.css( 'left', ui.item.parent().width() - ui.item.width() );
                ui.placeholder.height(ui.item.height());
                //ui.placeholder.width(ui.item.width());
            },
            handle: '.handle',
            stop: function (e, ui) {
                //wpfn_update_funnel_step_order();
            }
        });
    //emailSortables.disableSelection();
    var emailDraggables = jQuery( ".ui-draggable" ).draggable({
        connectToSortable: ".ui-sortable",
        helper: "clone",
        stop: function (e, ui ) {

            var el = this;
            var block_type = el.id;

            jQuery('#email-content').find('.ui-draggable').replaceWith( "<div class='replace-me'></div>" );


            var ajaxCall = jQuery.ajax({
                type : "post",
                url : ajaxurl,
                data : {action: "get_email_block_html", block_type: block_type },
                success: function( html )
                {
                    jQuery('#email-content').find('.replace-me').replaceWith(html);
                }
            });
        }
    });
});