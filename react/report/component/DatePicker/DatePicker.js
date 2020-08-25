import React, {useState} from 'react';
import moment from 'moment';
import DateRangePicker from 'react-bootstrap-daterangepicker';

import {dateChanged} from "../../../actions/reportDateActions";

import {connect} from 'react-redux';
import 'bootstrap-daterangepicker/daterangepicker.css';
import './style.scss';



class DatePicker extends React.Component {

    constructor(props) {
        super(props);
        this.handleCallback = this.handleCallback.bind(this);
    }

    handleCallback(start, end) {
        this.props.dateChanged(start,end);
    }

    render() {
        const {start, end} = this.props.date;
        const label = start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY');
        return (
            <DateRangePicker
                initialSettings={{
                    startDate: start.toDate(),
                    endDate: end.toDate(),
                    ranges: {
                        Today: [moment().toDate(), moment().toDate()],
                        Yesterday: [
                            moment().subtract(1, 'days').toDate(),
                            moment().subtract(1, 'days').toDate(),
                        ],
                        'Last 7 Days': [
                            moment().subtract(6, 'days').toDate(),
                            moment().toDate(),
                        ],
                        'Last 30 Days': [
                            moment().subtract(29, 'days').toDate(),
                            moment().toDate(),
                        ],
                        'This Month': [
                            moment().startOf('month').toDate(),
                            moment().endOf('month').toDate(),
                        ],
                        'Last Month': [
                            moment().subtract(1, 'month').startOf('month').toDate(),
                            moment().subtract(1, 'month').endOf('month').toDate(),
                        ],
                        'This Year': [
                            moment().startOf('year').toDate(),
                            moment().toDate(),
                        ],
                    },
                }}
                onCallback={this.handleCallback}>
                <div
                    id="reportrange"
                    className={"col-sm-12 col-md-6 col-lg-3 form-control groundhogg-report-datepicker "}>
                    <i className="fa fa-calendar"></i>&nbsp;
                    <span>{label}</span>
                </div>
            </DateRangePicker>
        );
    }
}




const mapStateToProps = (state) => {
    return {
        date: state.reportDate
    };
};


export default connect(mapStateToProps, {dateChanged})(DatePicker);
