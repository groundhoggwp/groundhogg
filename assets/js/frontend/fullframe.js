jQuery(function($){

    var iFrames = $('iframe');

    var offset = 0;

    function iResize() {
        for (var i = 0, j = iFrames.length; i < j; i++) {
            iFrames[i].style.height = iFrames[i].contentWindow.document.body.offsetHeight + offset + 'px';}
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
            this.style.height = this.contentWindow.document.body.offsetHeight + offset + 'px';
        });
    }

});