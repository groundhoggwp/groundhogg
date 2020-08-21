import React from 'react';
import moment from 'moment';
import DayPickerInput from 'react-day-picker/DayPickerInput';
import 'react-day-picker/lib/style.css';
import {formatDate, parseDate} from 'react-day-picker/moment';
import './style.scss';
import {connect} from "react-redux";
// import {dateChanged} from "../../../actions/reportNavBarActions";


class DatePicker extends React.Component {
    constructor(props) {
        super(props);
        this.handleFromChange = this.handleFromChange.bind(this);
        this.handleToChange = this.handleToChange.bind(this);
        this.state = {
            from: undefined,
            to: undefined,
        };
    }

    showFromMonth() {
        const {from, to} = this.state;
        if (!from) {
            return;
        }
        if (moment(to).diff(moment(from), 'months') < 2) {
            this.to.getDayPicker().showMonth(from);
        }
    }

    handleFromChange(from) {
        // Change the from date and focus the "to" input field
        // this.setState({from});

        // this.props.dateChanged( {from} ,  '' );

        console.log(from);
    }

    handleToChange(to) {

        // this.setState({to}, this.showFromMonth);

        // this.props.dateChanged( '' , to ) ;
        console.log(to);
    }

    render() {
        const {from, to} = this.props;
        const modifiers = {start: from, end: to};
        return (
            <div className={"owlytik-datepicker"}>
                <DayPickerInput
                    inputProps={{ className: 'form-control' }}
                    value={from}
                    placeholder="Start Date"
                    format="LL"
                    formatDate={formatDate}
                    parseDate={parseDate}
                    dayPickerProps={{
                        selectedDays: [from, {from, to}],
                        disabledDays: {after: to},
                        toMonth: to,
                        modifiers,
                        numberOfMonths: 2,
                        onDayClick: () => this.to.getInput().focus(),
                    }}
                    onDayChange={this.handleFromChange}
                />

                <DayPickerInput
                    inputProps={{ className: 'form-control' }}
                    ref={el => (this.to = el)}
                    value={to}
                    placeholder="End Date"
                    format="LL"
                    formatDate={formatDate}
                    parseDate={parseDate}
                    dayPickerProps={{
                        selectedDays: [from, {from, to}],
                        disabledDays: {before: from},
                        modifiers,
                        month: from,
                        fromMonth: from,
                        numberOfMonths: 2,
                    }}
                    onDayChange={this.handleToChange}
                />
            </div>

        );
    }
}


const mapStateToProps = (state) => {
    return {
        date: state.date
    };
};



export default connect(mapStateToProps, { dateChanged})(DatePicker);