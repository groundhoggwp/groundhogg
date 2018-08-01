jQuery(document).ready( function() {
    var funnelSortables = jQuery( ".ui-sortable" ).sortable(
        {
            placeholder: "sortable-placeholder",
            connectWith: ".ui-sortable",
            axis: 'y',
            start: function(e, ui){
                ui.helper.css( 'left', ui.item.parent().width() - ui.item.width() );
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
        stop: function (){
            console.log( this.id );
            var el = this;
            var step_type = el.id;

            jQuery('#normal-sortables').find('.ui-draggable').replaceWith(
                '<div class="postbox replace-me"><h3 class="hndle"></h3><div class="inside"></div></div>'
            );

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
                }});}});
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
}

function wpfn_update_funnel_step_order()
{
    jQuery( "input[name$='_order']" ).each(
        function( index ){
            jQuery( this ).val( index + 1 );
            //console.log( index);
        }
    );
}
