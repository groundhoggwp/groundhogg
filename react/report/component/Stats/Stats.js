import React from "react";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import {Card} from "react-bootstrap";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import './style.scss';


class Stats extends React.Component {

    constructor(props) {
        super(props);
    }

    // componentDidMount() {
    //     // get the data for the line chart from the id
    //     this.props.fetchReport(this.props.id, this.props.start, this.props.end);
    // }

    render() {

        let reportId = this.props.id;

        if ((!this.props.reports || !this.props.reports[reportId]) || this.props.reports[reportId].isLoading) {
            return (
                <Card className="groundhogg-state-card">
                    <Card.Body className={"groundhogg-stat-card-body"}>
                        <div className="groundhogg-quick-stat">
                            <div className="groundhogg-quick-stat-title"><Loading/></div>
                            <div className="groundhogg-quick-stat-number"><Loading/></div>
                            <div className="groundhogg-quick-stat-compare"><Loading/></div>
                        </div>
                    </Card.Body>
                </Card>
            );
        }

        let report = this.props.reports[reportId];
        if (report.isFailed) {
            // return <h1>Chart not found</h1>;
            return <NotFound/>;
        } else {

            report.data = report;

            let arrow = 'groundhogg-quick-stat-arrow';
            if (report.data.chart.compare.arrow.direction) {
                arrow = arrow + ' ' + report.data.chart.compare.arrow.direction;
            }

            let arrow_color = 'groundhogg-quick-stat-previous';
            if (report.data.chart.compare.arrow.color) {
                arrow_color = arrow_color + ' ' + report.data.chart.compare.arrow.color;
            }

            return (
                <Card className="groundhogg-state-card">
                    <Card.Body className={"groundhogg-stat-card-body"}>
                        <div className="groundhogg-quick-stat">
                            <div className="groundhogg-quick-stat-title">{report.data.title} </div>
                            <div className="groundhogg-quick-stat-number">{report.data.chart.number}</div>
                            <div className={arrow_color}>
                                <span className={arrow}/>
                                <span
                                    className="groundhogg-quick-stat-prev-percent">{report.data.chart.compare.percent}</span>
                            </div>
                            <div className="groundhogg-quick-stat-compare">{report.data.chart.compare.text}</div>
                        </div>
                    </Card.Body>
                </Card>
            );
        }
    }
}


const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(Stats);
