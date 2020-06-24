(function ($, reCAPTCHA ) {

    var mem = {
        token: false
    };

    function protect_forms(){
        $( '.gh-recaptcha-v3' ).closest( 'form' ).on( 'submit', function (e) {

            if ( mem.token ){
                return true;
            }

            e.preventDefault();

            var $form = $(this);

            grecaptcha.ready(function() {
                grecaptcha.execute(reCAPTCHA.site_key, {action: 'submit'}).then(function(token) {
                    // Add your logic to submit to your backend server here.
                    mem.token = token;
                    $form.append( '<input type="hidden" name="g-recaptcha-response" value="' + token + '">' );
                    $form.submit();
                });
            });
        } );
    }

    $(protect_forms);

})(jQuery, ghReCAPTCHA);