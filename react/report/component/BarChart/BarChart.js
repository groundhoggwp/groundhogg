import React, {Component} from 'react' ;
import {Bar} from 'react-chartjs-2';
import {Card} from "react-bootstrap";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import {mergeDeep} from "../../../functions";


/**
 * Renders a table
 */
class BarChart extends Component {

    constructor(props) {
        super(props);
        this.getBarChartOption = this.getBarChartOption.bind(this);
    }

    getBarChartOption(options = {} ) {
        let declaredOptions  =  {
            options: {
                maintainAspectRatio: false,
                tooltips: {
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
                        }
                    }],
                    yAxes: [{
                        scaleLabel: {
                            display: true,
                            labelString: "Numbers"
                        }
                    }]
                }
            }
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
            return (
                // <Card className="groundhogg-report-card">
                //     <Card.Header className="groundhogg-report-card-header">
                //         <h6>{report.data.title}</h6>
                //     </Card.Header>
                //     <Card.Body className={"groundhogg-report-card-body"}>
                        <div className={"groundhogg-report-chart-wrapper"}>
                            <Bar id={reportId}
                                  data={report.data.chart.data}
                                  options={this.getBarChartOption(report.data.chart.options)}
                            />
                        </div>
                //     </Card.Body>
                // </Card>
            );
        }
    }

    componentDidMount() {
        // get the data for the line chart from the id
        this.props.fetchReport(this.props.id);
    }

}

const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(BarChart);
