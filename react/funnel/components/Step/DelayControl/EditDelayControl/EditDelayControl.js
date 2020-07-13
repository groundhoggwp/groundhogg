import React from "react";
import {Form, FormControl, InputGroup} from "react-bootstrap";
import Button from "react-bootstrap/Button";

export class EditDelayControl extends React.Component {

    constructor(props) {
        super(props);

        const delay = props.delay;

        this.state = {
            type: delay.type,
            interval: delay.interval,
            period: delay.period,
            date: delay.date,
            time: delay.time,
            field: delay.field
        };

        this.handleTypeChange = this.handleTypeChange.bind(this);
        this.handleInputChange = this.handleInputChange.bind(this);
        this.handleDone = this.handleDone.bind(this);
    }

    handleTypeChange(e) {
        this.setState({
            type: e.target.value
        });
    }

    handleInputChange(e){
        const target = e.target;
        const name = target.name;
        const value = target.value;

        this.setState({
            [name]: value
        });
    }

    handleDone(e){
        this.props.done(this.state);
    }

    render() {

        const controls = [];

        controls.push(
            <select
                name={'type'}
                value={this.state.type}
                onChange={this.handleTypeChange}
            >
                <option value={"instant"}>Instant</option>
                <option value={"fixed"}>Fixed delay</option>
                <option value={"date"}>Date delay</option>
                <option value={"dynamic"}>Dynamic delay</option>
            </select>
        );

        switch (this.state.type) {
            case "fixed":
                controls.push(
                    " Wait at least ",
                    <input
                        name={'period'}
                        className={"period"}
                        value={this.state.period}
                        min={0}
                        type={"number"}
                        onChange={this.handleInputChange}
                    />,
                    " ",
                    <select
                        name={'interval'}
                        className={"interval"}
                        value={this.state.interval}
                        onChange={this.handleInputChange}
                    >
                        <option value={'minutes'}>Minutes</option>
                        <option value={'hours'}>Hours</option>
                        <option value={'days'}>Days</option>
                        <option value={'weeks'}>Weeks</option>
                        <option value={'months'}>Months</option>
                    </select>,
                    " then run at ",
                    <input
                        name={'time'}
                        type={"time"}
                        value={this.state.time}
                        onChange={this.handleInputChange}
                    />
                );
                break;
            case "date":
                // timeDisplay = "Wait until {0} then run at {2}...".format(step.delay.date, step.delay.time);
                break;
            case "dynamic":
                // timeDisplay = "Wait until the contact's {0} then run at {2}...".format(step.delay.field, step.delay.time);
                break;
        }

        controls.push(
            " ",
            <Button variant={"primary"} size={"sm"} onClick={this.handleDone}>{"Done"}</Button>
        );

        return (
            <div className={"edit-delay-controls"}>
                {controls}
            </div>);
    }

}