(function ($,step) {
    $.extend(step, {
        init: function () {

            var self = this;

            $(document).on( 'click', '.test-webhook', function ( e ) {
                var $step = $(e.target).closest( '.step' );
                self.test( $step );
            });

        },

        test: function ( $step ) {
            var step_id = $step.attr( 'id' );
            var intpos = step_id.indexOf( '-' );
            step_id = step_id.substr( intpos+1 );

            if ( ! step_id ){
                alert( 'Please enter a valid webhook.' )
            } else {

                $.ajax({
                    type: "post",
                    url: ajaxurl,
                    dataType: 'json',
                    data: { action: 'groundhogg_test_webhook', step_id: step_id },
                    success: function ( response ) {
                        if ( response.success == true ){
                            alert( 'Success!'  );
                            console.log( response );
                        } else {
                            alert( 'Error: ' + response.data[0].message );
                            console.log( response );
                        }
                    }
                });
            }
        }
    } );

    $( function () {
        step.init();
    })
})(jQuery,WebhookStep);