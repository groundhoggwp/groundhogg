(function ($,q) {
    q = Object.assign( q, {

        tabIsActive: true,
        interval:null,

        setQueueTimer:function()
        {
            this.interval = setTimeout( this.processQueue, this.timeInterval );
        },

        processQueue: function(){
            if ( q.tabIsActive ){
                $.ajax({
                    type: "post",
                    url: q.ajax_url,
                    dataType: 'json',
                    data: {action: 'gh_process_queue' },
                    success: function (response) {
                        console.log( response.eventsCompleted );
                        q.timeInterval = parseInt( response.nextRequestTime );
                        q.lastRun = response.lastRun;
                        q.setQueueTimer();
                    }
                });
            }
        },

        init: function () {
            $(window).focus(function() {
                q.tabIsActive = true;

                if ( ! q.interval ){
                    q.setQueueTimer();
                }
            });

            $(window).blur(function() {
                q.tabIsActive = false;
            });
        }
    } );

    $(function () {
        q.init();
    })
})(jQuery,wpghQueue);