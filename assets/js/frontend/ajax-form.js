(function ($, gh ) {

    $.fn.serializeFormJSON = function () {

        var o = {};
        var a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name]) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };

    $.fn.hideButton = function () {
        var $button = this.find( '.gh-submit-button' ).hide();
        $button.before( '<div class="gh-loader" style="font-size: 10px;margin: 20px"></div>' );
    };

    $.fn.showButton = function () {
        var $button = this.find( '.gh-submit-button' ).show();
        $( '.gh-loader' ).remove();
    };

    $(function () {
        $( 'form.gh-form.ajax-submit' ).on( 'submit', function ( e ) {
            e.preventDefault();
            $( '.gh-form-errors-wrapper' ).remove();
            $( '.gh-message-wrapper' ).remove();
            var $form = $(this);

            $form.hideButton();

            // console.log( $form.serializeFormJSON() );

            // var data = $form.serializeFormJSON();

            var data = new FormData($form[0]);
            data._ghnonce = gh._ghnonce;
            // data.form_data = $form.serializeArray();
            data.action = 'groundhogg_ajax_form_submit';

            // console.log(data);

            $.ajax( {
                method: 'POST',
                // dataType: 'json',
                url: gh.ajaxurl,
                data: data,
                processData: false,
                contentType: false,
                cache: false,
                timeout: 600000,
                enctype: 'multipart/form-data',
                success: function( response ){
                    if ( response.success == undefined ){
                        return;
                    }

                    if ( response.success ){

                        $form.after( '<div class="gh-message-wrapper gh-form-success-wrapper">' + response.data.message + '</div>' );
                        $form.trigger("reset");

                    } else {
                        $form.before( response.data.html );
                    }

                    $form.showButton();

                },
                error: function(){}
            } )
        } );
    });

})(jQuery, Groundhogg );