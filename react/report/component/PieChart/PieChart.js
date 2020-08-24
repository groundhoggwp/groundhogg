import React, {Component} from 'react' ;
import {Chart, Doughnut, Line} from 'react-chartjs-2';
import {Card} from "react-bootstrap";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import {mergeDeep} from "../../../functions";

//
// var ChartLegend = React.createClass({
//
//     render: function () {
//         var datasets = _.map(this.props.datasets, function (ds) {
//             return <li><span className="legend-color-box" style={{ backgroundColor: ds.strokeColor }}></span> { ds.label }</li>;
//         });
//
//         return (
//             <ul className={ this.props.title + "-legend" }>
//                 { datasets }
//             </ul>
//         );
//     }
// });

/**
 * Renders a table
 */
class PieChart extends Component {

    constructor(props) {
        super(props);
        this.chartReference = React.createRef();
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
            // var legend = this.refs.chart.getChart().generateLegend();

            return (
                <Card className="groundhogg-report-card">
                    <Card.Header className="groundhogg-report-card-header">
                        <h6>{report.data.title}</h6>
                    </Card.Header>
                    <Card.Body className={"groundhogg-report-card-body"}>
                        <div className={"groundhogg-report-chart-wrapper"}>
                            <div>
                            <Doughnut
                                id={reportId}
                                  data={report.data.chart.data}
                                  options={report.data.chart.options}
                                ref={this.chartReference}
                            />
                            </div>
                        </div>
                    </Card.Body>
                </Card>
            );
        }
    }

    componentDidMount() {
        // get the data for the line chart from the id
        this.props.fetchReport(this.props.id, this.props.start, this.props.end);



    }

}

const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(PieChart);
