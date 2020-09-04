import React, {Component} from 'react' ;
import {Chart, Line} from 'react-chartjs-2';
import {Card} from "react-bootstrap";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import {mergeDeep} from "../../../functions";


/**
 * Renders a table
 */
class LineChart extends Component {

    constructor(props) {
        super(props);
    }

    drawToolTipLine() {
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
    }

    getLineChartOptions(options = {}) {
        let declaredOptions = {
            maintainAspectRatio: false,
            tooltips: {
                callbacks: {
                    label: (tooltipItem, data) => {
                        if (data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].label) {
                            return data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].label;
                        } else {
                            return data.datasets[tooltipItem.datasetIndex].label + ": " + data.datasets[tooltipItem.datasetIndex].data[tooltipItem.index].y;
                        }
                    },
                    title: () => {
                        return '';
                    }
                },
                mode: "index",
                intersect: false,
                backgroundColor: "#FFF",
                bodyFontColor: "#000",
                borderColor: "#727272",
                borderWidth: 2
            },
            scales: {
                xAxes: [{
                    type: "time",
                    time: {
                        parser: "YYY-MM-DD HH:mm:ss",
                        tooltipFormat: "l HH:mm"
                    },
                    scaleLabel: {
                        display: true,
                        labelString: "Date"
                    },
                    gridLines: {display: false},
                }],
                yAxes: [{
                    scaleLabel: {
                        display: true,
                        labelString: "Numbers"
                    }
                }]
            },
            labels: {
                generateLabels: function (chart) {
                    return chart.data.datasets.map(function (dataset, i) {
                        return {
                            text: dataset.label,
                            lineCap: dataset.borderCapStyle,
                            // lineDash:[],
                            lineDashOffset: 0,
                            lineJoin: dataset.borderJoinStyle,
                            fillStyle: dataset.borderColor,
                            strokeStyle: dataset.borderColor,
                            lineWidth: dataset.pointBorderWidth,
                            // lineDash:dataset.borderDash,
                        }
                    })
                },

            },
        };
        // return Object.assign(declaredOptions,options);
        return mergeDeep(declaredOptions , options);
    }

    render() {

        let reportId = this.props.id;

        if ((!this.props.reports || !this.props.reports[reportId]) || this.props.reports[reportId].isLoading) {
            return <Loading/>;
        }

        let report = this.props.reports[reportId];
        if (report.isFailed) {
            // return <h1>Chart not found</h1>;
            return <NotFound/>;
        } else {
            this.drawToolTipLine();
            report.data = report;
            return (
                // <Card className="groundhogg-report-card">
                //     <Card.Header className="groundhogg-report-card-header">
                //         <h6>{report.data.title}</h6>
                //     </Card.Header>
                //     <Card.Body className={"groundhogg-report-card-body"}>
                        <div className={"groundhogg-report-chart-wrapper"}>
                            <Line id={reportId}
                                  data={report.data.chart.data}
                                  options={this.getLineChartOptions(report.data.chart.options)}
                            />
                        </div>
                //     </Card.Body>
                // </Card>
            );
        }
    }

    // componentDidMount() {
    //     // get the data for the line chart from the id
    //     this.props.fetchReport(this.props.id);
    // }

}

const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(LineChart);
