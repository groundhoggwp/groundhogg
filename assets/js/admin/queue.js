var ghQueue;
(function ($) {
    ghQueue = {
        setQueueTimer:function()
        {
            ghQueue.processQueue();
            setInterval(ghQueue.processQueue, 30000);
        },

        processQueue: function(){
            $.ajax({
                type: "post",
                url: ajaxurl,
                data: {action: 'gh_process_queue' }
            });
        },

        init: function () {
            this.setQueueTimer();
        }
    };

    $(function () {
        ghQueue.init();
    })
})(jQuery);