jQuery(function($){

    var iFrames = $('iframe');

    var offset = 0;

    function iResize() {
        for (var i = 0, j = iFrames.length; i < j; i++) {
            iFrames[i].height($(iFrames[i].contentWindow.document).height());
        }
    }

    if ($.browser.safari || $.browser.opera) {

        iFrames.load(function(){
            setTimeout(iResize, 0);
        });

        for (var i = 0, j = iFrames.length; i < j; i++) {
            var iSource = iFrames[i].src;
            iFrames[i].src = '';
            iFrames[i].src = iSource;
        }

    } else {
        iFrames.load(function() {
            $(this).height( $(this.contentWindow.document ).height() );
        });
    }

});