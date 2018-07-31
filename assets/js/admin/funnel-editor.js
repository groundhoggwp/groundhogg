jQuery(document).ready( function() {
    jQuery( ".ui-sortable" ).sortable(
        {
            placeholder: "sortable-placeholder",
            connectWith: ".ui-sortable",
            start: function(e, ui){
                ui.placeholder.height(ui.item.height());
            }
        }
    );
    jQuery( ".ui-sortable" ).disableSelection();
    jQuery( ".ui-draggable" ).draggable({
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
                }
            });
        }
    });
});

function wpfn_delete_funnel_step() {
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