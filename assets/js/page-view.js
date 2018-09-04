jQuery(function($) {
    $.ajax({
        type: "post",
        url: wpfn_ajax_object.ajax_url,
        data: {action: 'wpfn_page_view'},
        success: function( response ){
            // console.log( events_complete )
        }
    });
});