var Groundhogg;
(function ($) {
    Groundhogg = {
        leadSource: 'gh_referer',
        refID: 'gh_ref_id',
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
            $.ajax({
                type: "post",
                url: gh_frontent_object.page_view_endpoint,
                data: { ref: window.location.href },
                success: function( response ){
                    // console.log( events_complete )
                },
                error: function(){}
            });
        },
        logFormImpressions : function() {
            var forms = $( '.gh-form' );
            $.each( forms, function ( i, e ) {
                var fId = $(e).find( 'input[name="step_id"]' ).val();
                Groundhogg.formImpression( fId );
            });
        },
        formImpression : function( id ){
            $.ajax({
                type: "post",
                url: gh_frontent_object.form_impression_endpoint,
                dataType: 'json',
                data: { form_id: id },
                success: function( response ){
                    if( typeof response.ref_id !== 'undefined' ) {
                        Groundhogg.setCookie( Groundhogg.refID, response.ref_id, 30 );
                    }
                },
                error: function(){}
            });
        },
        init: function(){
            var referer = this.getCookie( this.leadSource );
            if ( ! referer ){
                this.setCookie( this.leadSource, document.referrer, 3 )
            }
            this.pageView();
            this.logFormImpressions();
        }
    };
    $(function(){
        Groundhogg.init();
    });
})(jQuery);

