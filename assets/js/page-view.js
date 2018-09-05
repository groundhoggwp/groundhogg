jQuery(function($) {
    $.ajax({
        type: "post",
        url: wpgh_ajax_object.ajax_url,
        data: {action: 'wpgh_page_view'},
        success: function( response ){
            // console.log( events_complete )
        }
    });
});