import React from "react";

import "./component.scss";
import {Navbar} from "react-bootstrap";
import {ExitButton} from "../ExitButton/ExitButton";
import {SlideInBarRight} from "../SlideInBarRight/SlideInBarRight";


export function showEditStepForm ( step ) {
    const event = new CustomEvent('groundhogg-edit-step', { detail : { step: step } } );
    // Dispatch the event.
    document.dispatchEvent(event);
}

export class EditStep extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            isShowing: false,
            step: {}
        };

        this.handleExit = this.handleExit.bind(this);
        this.handleEditStep = this.handleEditStep.bind(this);
    }

    handleExit(){
        this.setState({
            isShowing: false,
        });
    }

    handleEditStep(e){
        this.setState({
            isShowing: true,
            stepChosen: false,
            step: e.detail.step,
        });
    }

    componentDidMount () {
        document.addEventListener('groundhogg-edit-step', this.handleEditStep );
    }

    render() {

        if ( ! this.state.isShowing || ! this.state.step ){
            return <div className={'hidden-step-edit'}></div>
        }

        return (
            <div className={"add-new-step"}>
                <SlideInBarRight onOverlayClick={this.handleExit}>
                    <div className={"inner"}>
                        <Navbar bg="white" expand="sm" fixed="top">
                            <Navbar.Brand>
                                {"Edit "}
                                <b>{this.state.step.title}</b>
                            </Navbar.Brand>
                            <Navbar.Toggle
                                aria-controls="basic-navbar-nav"/>
                            <ExitButton onExit={this.handleExit}/>
                        </Navbar>
                        <div className={'step-settings'}>
                            {'Settings go here...'}
                        </div>
                    </div>
                </SlideInBarRight>
            </div>
        );
    }
}