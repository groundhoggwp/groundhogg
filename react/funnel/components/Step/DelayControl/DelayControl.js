import React from "react";
import {Dashicon} from "../../Dashicon/Dashicon";
import {EditDelayControl} from "./EditDelayControl/EditDelayControl";
import moment from 'moment';

export class DelayControl extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            editing: false,
            delay: props.step.delay
        };

        this.handleClick = this.handleClick.bind(this);
        this.doneEditing = this.doneEditing.bind(this);
    }

    handleClick(e) {
        this.setState({
            editing: true
        });
    }

    doneEditing( delay ){

        console.debug( delay );

        this.setState( {
            delay: delay,
            editing: false,
        } );
    }

    render() {
        const delay = this.state.delay;

        let timeDisplay;

        switch (delay.type) {
            default:
            case "instant":
                timeDisplay = "Run immediately...";
                break;
            case "fixed":
                timeDisplay = "Wait at least {0} {1} then run at {2}...".format(delay.period, delay.interval, moment(delay.time, 'HH:mm' ).format( 'H:mm a' ) );
                break;
            case "date":
                timeDisplay = "Wait until {0} then run at {2}...".format(delay.date, delay.time );
                break;
            case "dynamic":
                timeDisplay = "Wait until the contact's {0} then run at {2}...".format(delay.field, delay.time);
                break;
        }

        return (
            <div className={"delay"}>
                <Dashicon icon={"clock"}/>
                {!this.state.editing && <span className={"delay-text"} onClick={this.handleClick}>{timeDisplay}</span>}
                {this.state.editing && <EditDelayControl delay={delay} done={this.doneEditing}/>}
            </div>
        );
    }

}