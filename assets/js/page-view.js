var GH = {
    leadSource: 'gh_leadsource',
    setCookie: function(cname, cvalue, exdays){
        var d = new Date();
        d.setTime(d.getTime() + (exdays*24*60*60*1000));
        var expires = "expires="+ d.toUTCString();
        document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
    },
    getCookie: function( cname ){
        var name = cname + "=";
        var ca = document.cookie.split(';');
        for(var i = 0; i < ca.length; i++) {
            var c = ca[i];
            while (c.charAt(0) == ' ') {
                c = c.substring(1);
            }
            if (c.indexOf(name) == 0) {
                return c.substring(name.length, c.length);
            }
        }
        return null;
    },
    pageView : function(){
        jQuery.ajax({
            type: "post",
            url: wpgh_ajax_object.ajax_url,
            data: {action: 'wpgh_page_view'},
            success: function( response ){
                // console.log( events_complete )
            }
        });
    },
    init: function(){
        var referrer = this.getCookie( this.leadSource );
        if ( ! referrer ){
            this.setCookie( this.leadSource, document.referrer, 3 )
        }
        this.pageView();
    }
};

jQuery(function(){
    GH.init();
});