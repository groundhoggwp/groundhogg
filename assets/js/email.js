var emailIframe;

(function ($) {

    emailIframe = {

        init: function () {
            this.resize();
            $( window ).resize(function() {
                emailIframe.resize()
            });
        },

        resize: function ( content ) {

            var $frame = $('#browser-email-view');
            var doc = $frame[0].contentDocument;
            var body = doc.body,
                html = doc.documentElement;

            var height = Math.max(
                body.scrollHeight, body.offsetHeight,
                html.clientHeight, html.scrollHeight, html.offsetHeight
            );

            $frame.height( height );
            $frame.width( $frame.parent().width() );

        }

    };

    $(function () {
        emailIframe.init();
    });

})(jQuery);
