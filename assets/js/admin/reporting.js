function tool_tip_label(tooltipItem, data) {
    if (data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].label) {
        return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].label;
    } else {
        return data.datasets[tooltipItem.datasetIndex].label + ": " + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].y;
    }
}

function tool_tip_title() {
    return '';
}

(function (reporting, $, nonces) {

    $.extend(reporting, {

        data: {},
        calendar: null,
        myChart: [],

        init: function () {

            this.initCalendar();
            this.initFunnels();
            this.initCountry();
            this.initBroadcast();

        },

        initCalendar: function () {

            var self = this;

            this.calendar = new Calendar({
                element: $('#groundhogg-datepicker'),
                presets: [
                    {
                        label: 'Last 30 days',
                        start: moment().subtract(29, 'days'),
                        end: moment(),
                    }, {
                        label: 'This month',
                        start: moment().startOf('month'),
                        end: moment().endOf('month'),
                    }, {
                        label: 'Last month',
                        start: moment().subtract(1, 'month').startOf('month'),
                        end: moment().subtract(1, 'month').endOf('month'),
                    }, {
                        label: 'Last 7 days',
                        start: moment().subtract(6, 'days'),
                        end: moment(),
                    }, {
                        label: 'Last 3 months',
                        start: moment(this.latest_date).subtract(3, 'month').startOf('month'),
                        end: moment(this.latest_date).subtract(1, 'month').endOf('month'),
                    }, {
                        label: 'This year',
                        start: moment().startOf('year'),
                        end: moment().endOf('year'),
                    }
                ],
                earliest_date: 'January 1, 2017',
                latest_date: moment(),
                start_date: self.dates.start_date,
                end_date: self.dates.end_date,
                callback: function () {
                    self.refresh(this)
                },
            });

            // run it with defaults
            this.calendar.calendarSaveDates()
        },

        initFunnels: function () {

            var self = this;

            $('#funnel-id').change(function () {
                self.refresh(self.calendar);
            });
        },

        initBroadcast: function () {

            var self = this;

            $('#broadcast-id').change(function () {
                self.refresh(self.calendar);
            });
        },

        initCountry: function () {

            var self = this;

            $('#country').change(function () {
                self.refresh(self.calendar);
            });
        },

        refresh: function (calendar) {

            var self = this;

            self.showLoader();

            var start = moment(calendar.start_date).format('LL'),
                end = moment(calendar.end_date).format('LL');

            $.ajax({
                type: 'post',
                url: ajaxurl,
                dataType: 'json',
                data: {
                    action: 'groundhogg_refresh_dashboard_reports',
                    reports: self.reports,
                    start: start,
                    end: end,
                    data: self.get_other_data()
                },
                success: function (json) {

                    self.data = json.data.reports;
                    self.renderReports();
                    self.hideLoader();
                    $('.wrap').removeClass('blurred');

                },
                failure: function (response) {

                    alert('Unable to retrieve data...')

                },
            })

        },

        get_other_data: function () {
            return {
                funnel_id: $('#funnel-id').val(),
                broadcast_id: $('#broadcast-id').val(),
                country: $('#country').val(),
                email_id: $('#email_id').val(),
            };
        },

        renderReports: function () {

            for (var i = 0; i < this.reports.length; i++) {

                var report_id = this.reports[i];
                var report_data = this.data[report_id];

                this.renderReport(report_id, report_data);

            }

        },

        renderReport: function (report_id, report_data) {

            var $report = $('#' + report_id);


            var type = report_data.type;

            switch (type) {
                case 'quick_stat':
                    this.renderQuickStatReport($report, report_data);
                    break;
                case 'chart':
                    this.renderChartReport($report, report_data.chart, report_id);
                    break;
                case 'table':
                    this.renderTable($report, report_data);
                    break;
            }

        },

        renderQuickStatReport: function ($report, report_data) {

            $report.find('.groundhogg-quick-stat-number').html(report_data.number);
            $report.find('.groundhogg-quick-stat-previous').removeClass('green red').addClass(report_data.compare.arrow.color);
            $report.find('.groundhogg-quick-stat-compare').html(report_data.compare.text);
            $report.find('.groundhogg-quick-stat-arrow').removeClass('up down').addClass(report_data.compare.arrow.direction);
            $report.find('.groundhogg-quick-stat-prev-percent').html(report_data.compare.percent)

        },

        renderChartReport: function ($report, report_data, report_id) {

            if (typeof report_data.options.tooltips.callbacks !== 'undefined') {
                var funcName = report_data.options.tooltips.callbacks.label;
                report_data.options.tooltips.callbacks.label = window[funcName];
            }

            if (typeof report_data.options.tooltips.callbacks !== 'undefined') {
                var funcName = report_data.options.tooltips.callbacks.title;
                report_data.options.tooltips.callbacks.title = window[funcName];
            }


            if (this.myChart[$report.selector] != null) {
                this.myChart[$report.selector].destroy();
            }

            var ctx = $report[0].getContext('2d');
            this.myChart[$report.selector] = new Chart(ctx, report_data);


            // draw Hover line in the graph
            var draw_line = Chart.controllers.line.prototype.draw;
            Chart.helpers.extend(Chart.controllers.line.prototype, {
                draw: function () {
                    draw_line.apply(this, arguments);
                    if (this.chart.tooltip._active && this.chart.tooltip._active.length) {
                        var ap = this.chart.tooltip._active[0];
                        var ctx = this.chart.ctx;
                        var x = ap.tooltipPosition().x;
                        var topy = this.chart.scales['y-axis-0'].top;
                        var bottomy = this.chart.scales['y-axis-0'].bottom;

                        ctx.save();
                        ctx.beginPath();
                        ctx.moveTo(x, topy);
                        ctx.lineTo(x, bottomy);
                        ctx.lineWidth = 1;
                        ctx.strokeStyle = '#727272';
                        ctx.setLineDash([10, 10]);
                        ctx.stroke();
                        ctx.restore();
                    }
                }

            });

            Chart.plugins.register({
                afterDraw: function (chart) {
                    if (chart.data.datasets.length === 0) {
                        // No data is present
                        var ctx = chart.chart.ctx;
                        var width = chart.chart.width;
                        var height = chart.chart.height;
                        chart.clear();

                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.font = "16px normal 'Helvetica Nueue'";
                        ctx.fillText('No data to display', width / 2, height / 2);
                        ctx.restore();
                    }
                }
            });

            // legend on the side in the pie chart
            $('#' + report_id + '_legend').html(this.myChart[$report.selector].generateLegend());

            // var chart_pi = this.myChart[$report.selector];
            $('#' + report_id + '_legend' + " > ul > li").on("click", this.myChart[$report.selector], function (e) {
                var index = $(this).index();
                $(this).toggleClass("strike");
                var ci = e.data.chart;
                var meta = Object.values(ci.data.datasets[0]._meta) [0];
                var curr = meta.data[index];
                curr.hidden = !curr.hidden;
                ci.update();
            });

        },

        renderTable: function ($report, report_data) {

            var html = '';
            if (report_data.data && report_data.data.length > 0) {

                html = html + "<table class='groundhogg-report-table'>";

                var length = report_data.data.length;

                if (report_data.label.length > 0) {

                    html += '<tr>';

                    for (var key in report_data.label) {
                        html += '<th>' + report_data.label[key] + '</th>';
                    }

                    html += '</tr>';
                }

                for (var i = 0; i < length; i++) {

                    html += '<tr >';
                    for (var key in report_data.data[i]) {
                        html = html + '<td>' + report_data.data[i][key] + '</td>';
                    }
                    html += '</tr>';

                }

                html += '</table>';

            } else {

                html = report_data.no_data;

            }

            $report.html(html);
        },


        showLoader: function () {
            $('.gh-loader-overlay').show();
            $('.gh-loader').show();
        },

        hideLoader: function () {
            $('.gh-loader-overlay').hide();
            $('.gh-loader').hide();
        },

    });

    $(function () {
        reporting.init()
    })

})(GroundhoggReporting, jQuery, groundhogg_nonces);