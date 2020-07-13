import React from "react";
import {Dashicon} from "../../Dashicon/Dashicon";
import {EditDelayControl} from "./EditDelayControl/EditDelayControl";
import moment from 'moment';
import { DisplayDelay } from './DisplayDelay/DisplayDelay';

export class DelayControl extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            editing: false,
            delay: props.step.delay
        };

        this.handleClick = this.handleClick.bind(this);
        this.cancelEditing = this.cancelEditing.bind(this);
        this.doneEditing = this.doneEditing.bind(this);
    }

    handleClick(e) {
        this.setState({
            editing: true
        });
    }

    cancelEditing(){
        this.setState( {
            editing: false,
        } );
    }

    doneEditing( delay ){
        this.setState( {
            delay: delay,
            editing: false,
        } );
    }

    render() {
        const delay = this.state.delay;

        return (
            <div className={"delay"}>
                <Dashicon icon={"clock"}/>
                <span className={'delay-text'} onClick={this.handleClick}>
                    <DisplayDelay delay={delay} />
                </span>
                <EditDelayControl show={this.state.editing} delay={delay} save={this.doneEditing} cancel={this.cancelEditing}/>
            </div>
        );
    }

}