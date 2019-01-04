(function ($,d) {
    d = Object.assign( d,{

        report: null,
        reportId: null,

        init: function () {

            $(document).on( 'click', '.export', function (e) {
                d.report = $(e.target).closest( '.postbox' );
                console.log( d.report );
                d.setup();
                d.export();
            });

        },

        setup: function () {
            this.reportId = this.report.attr( 'id' );
        },

        showSpinner: function () {
            this.report.find( '.spinner' ).css( 'visibility', 'visible' );
        },

        hideSpinner: function () {
            this.report.find( '.spinner' ).css( 'visibility', 'hidden' );
        },

        export: function () {

            this.showSpinner();

            //esc_attr( $this->get_url_var( 'custom_date_range_start' ) )

            $.ajax({
                type: "post",
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action:                     'wpgh_export_' + d.reportId,
                    date_range:                 d.date_range,
                    custom_date_range_start:    d.custom_date_range_start,
                    custom_date_range_end:      d.custom_date_range_end },
                success: function ( json ) {
                    var CSV = Papa.unparse( json, {
                        quotes: false,
                        quoteChar: '"',
                        escapeChar: '"',
                        delimiter: ",",
                        header: true,
                        newline: "\r\n"
                    } );

                    d.makeFile( CSV );
                    d.hideSpinner();
                },
                failure : function (response) {
                    alert( response );
                }
            });
        },

        makeFile : function (text) {
            var element = document.createElement('a');
            element.setAttribute('href', 'data:text/csv;charset=utf-8,' + encodeURIComponent(text));

            var today = new Date();
            var date = today.getFullYear()+'-'+(today.getMonth()+1)+'-'+today.getDate();
            var time = today.getHours() + ":" + today.getMinutes() + ":" + today.getSeconds();
            var dateTime = date+' '+time;

            element.setAttribute('download', 'report-' + this.reportId + '.csv' );

            element.style.display = 'none';
            document.body.appendChild(element);

            element.click();

            document.body.removeChild(element);
        },

    });

    $(function () {
        d.init();
    });
})(jQuery,wpghDashboard);