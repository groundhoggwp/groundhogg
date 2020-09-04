import React from "react";
import {Loading} from "../Loading/Loading";
import {NotFound} from "../NotFound/NotFound";
import {Card, Nav} from "react-bootstrap";
import {Line} from "react-chartjs-2";
import {connect} from "react-redux";
import {fetchReport} from "../../../actions/reportActions";
import {dropDownChanged} from "../../../actions/reportDataActions";
import './style.scss';


class DropDown extends React.Component {

    constructor(props) {
        super(props);

        this.selectionChange = this.selectionChange.bind(this);
    }

    // componentDidMount() {
    //     // console.log(this.props.type);
    //     this.props.fetchReport(this.props.id , this.props.type);
    // }

    selectionChange(e) {
        this.props.dropDownChanged(this.props.id, e.target.value);
    }

    render() {

        let reportId = this.props.id;

        if ((!this.props.reports || !this.props.reports[reportId]) || this.props.reports[reportId].isLoading) {
            return <Loading/>;
        }

        let report = this.props.reports[reportId];
        if (report.isFailed) {
            return <NotFound/>;
        } else {

            report.data = report;
            return (
                <select className={"form-control"} style={{float: "right"}} onChange={this.selectionChange}
                        value={this.props.data[reportId]}>
                    {Object.entries(report.data.chart).map((value, key) => {
                        return <option key={key} value={value[0]}>{value[1]}</option>
                    })}
                </select>
            );
        }
    }
}


const mapStateToProps = (state) => ({
    reports: state.reports,
    data: state.reportData.data

});

export default connect(mapStateToProps, {fetchReport, dropDownChanged})(DropDown);
