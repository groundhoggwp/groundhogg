var wpfnDoingAutoSave = false;

jQuery( function($) {
    var funnelSortables = jQuery( ".ui-sortable" ).sortable(
        {
            placeholder: "sortable-placeholder",
            connectWith: ".ui-sortable",
            axis: 'y',
            start: function(e, ui){
                ui.helper.css( 'left', ( ui.item.parent().width() - ui.item.width() ) / 2 );
                ui.placeholder.height(ui.item.height());
                ui.placeholder.width(ui.item.width());
            },
            stop: function (e, ui) {
                wpfn_update_funnel_step_order();
            }
        });
    funnelSortables.disableSelection();
    var funnelDraggables = jQuery( ".ui-draggable" ).draggable({
        connectToSortable: ".ui-sortable",
        helper: "clone",
        stop: function ( e, ui ){
            console.log( this.id );
            var el = this;
            var step_type = el.id;

            var sortables = jQuery('#normal-sortables');

            sortables.find('.ui-draggable').replaceWith(
                '<div class="postbox replace-me" style="width: 500px;margin-right: auto;margin-left: auto;"><h3 class="hndle">Please Wait...</h3><div class="inside">Loading content...</div></div>'
            );

            if ( sortables.find( '.replace-me' ).length ){
                var ajaxCall = jQuery.ajax({
                    type : "post",
                    url : ajaxurl,
                    data : {action: "wpfn_get_step_html",step_type: step_type, step_order: 1 },
                    success: function( html )
                    {
                        var wrapper = document.createElement('div');
                        wrapper.innerHTML = html;

                        var newEl = wrapper.firstChild;
                        jQuery('#normal-sortables').find('.replace-me').replaceWith( html );

                        wpfn_update_funnel_step_order();
                    }
                });
            }
        }});
});

function wpfn_delete_funnel_step()
{
    var el = this;
    var id = el.parentNode.id;
    var result = confirm("Are you sure you want to delete this step?");
    if ( result ){
        var ajaxCall = jQuery.ajax({
            type : "post",
            url : ajaxurl,
            data : {action: "wpfn_delete_funnel_step",step_id: id },
            success: function( result )
            {
                el.parentNode.remove();
            }
        });
    }

    wpfn_auto_save_funnel();
}

function wpfn_update_funnel_step_order()
{
    jQuery( "input[name$='_order']" ).each(
        function( index ){
            jQuery( this ).val( index + 1 );
        }
    );

    wpfn_auto_save_funnel();
}

function wpfn_auto_save_funnel()
{
    if ( wpfnDoingAutoSave )
        return;

    wpfnDoingAutoSave = true;

    var fd = jQuery('form').serialize();

    fd = fd +  '&action=wpfn_auto_save_funnel_via_ajax';

    var ajaxCall = jQuery.ajax({
        type : "post",
        url : ajaxurl,
        //data : fd,
        data : fd,
        success: function( result )
        {
            console.log(result);
            wpfnDoingAutoSave = false;
        }
    });
}
