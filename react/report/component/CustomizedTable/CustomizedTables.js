import React from 'react' ;
import {Card, Table, Alert} from "react-bootstrap";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import './style.scss';


class CustomizedTables extends React.Component {

    constructor(props) {
        super(props);
    }

    componentDidMount() {
        // get the data for the line chart from the id
        this.props.fetchReport(this.props.id, this.props.start, this.props.end);
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

            if (!report.data.chart.data.length) {

                return (
                    <Card className="groundhogg-report-card" style={{padding: 0}}>
                        <Card.Header className="groundhogg-report-card-header">
                            <h6>{(report.data.title) ? report.title : ""}</h6>
                        </Card.Header>
                        <Card.Body className={"groundhogg-report-card-body"}>
                            <div className={"groundhogg-no-data-notice"}>
                                {require('html-react-parser')(report.data.no_data)}
                            </div>
                        </Card.Body>
                    </Card>
                );

            }

            return (
                <Card className="groundhogg-report-card" style={{padding: 0}}>
                    <Card.Header className="groundhogg-report-card-header">
                        <h6>{(report.data.title) ? report.title : ""}</h6>
                    </Card.Header>
                    <Card.Body className={"groundhogg-report-card-body"}>
                        <Table className={'groundhogg-report-table'}>
                            <thead>
                            <tr>
                                {report.data.chart.label.map((label, index) => {
                                    return <th key={index}> {label} </th>;
                                })}
                            </tr>
                            </thead>
                            <tbody>
                            {report.data.chart.data.map( (row ,index) =>
                                <tr key={index}>
                                    {Object.keys(row).map(key => <td
                                        key={key}>{require('html-react-parser')(String(row[key]))}</td>)}
                                </tr>)
                            }
                            </tbody>
                        </Table>
                    </Card.Body>
                </Card>
            );
        }
    }
}


const mapStateToProps = (state) => ({
    reports: state.reports,
});

export default connect(mapStateToProps, {fetchReport})(CustomizedTables);
