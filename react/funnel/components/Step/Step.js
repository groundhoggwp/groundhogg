import React from "react";

import {StepControls} from "./StepControls/StepControls";
import {StepTitle} from "./StepTitle/StepTitle";
import {StepIcon} from "./StepIcon/StepIcon";

import "./component.scss";
import {showAddStepForm} from "../AddStep/AddStep";
import {EditStep, showEditStepForm} from "../EditStep/EditStep";
import axios from "axios";
import {reloadEditor} from "../Editor/Editor";
import {FadeOut} from "../Animations/Animations";

export class Step extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            showControls: false,
            deleting: false,
            deleted: false
        };

        this.handleOnMouseEnter = this.handleOnMouseEnter.bind(this);
        this.handleMouseLeave = this.handleMouseLeave.bind(this);
        this.handleControlAction = this.handleControlAction.bind(this);
        this.handleClickAction = this.handleClickAction.bind(this);
        this.handleDelete = this.handleDelete.bind(this);
        this.afterFadeOut = this.afterFadeOut.bind(this);
    }

    handleOnMouseEnter(e) {
        this.setState({
            showControls: true
        });
    }

    handleMouseLeave(e) {
        this.setState({
            showControls: false
        });
    }

    handleClickAction(e) {
        showEditStepForm(this.props.step);
    }

    handleDuplicate() {

    }

    afterFadeOut() {
        reloadEditor();
        this.setState({
            deleted: true,
            deleting: false
        });
    }

    handleDelete() {
        axios.delete(groundhogg_endpoints.steps, {
            data: {
                step_id: this.props.step.id
            }
        }).then(result => this.setState({
            deleting: true,
            showControls: false
        }));
    }

    handleControlAction(key, e) {

        switch (key) {
            case "edit":
                showEditStepForm(this.props.step);
                break;
            case "duplicate":
            case "delete":
                this.handleDelete();
                break;
            case "add_action":
                showAddStepForm("action", this.props.step.id);
                break;
            case "add_benchmark":
                showAddStepForm("benchmark", this.props.step.id);
                break;

        }
    }

    render() {

        if (this.state.deleted) {
            return <div className={"step-deleted"}></div>;
        }

        const step = this.props.step;
        const classes = [
            step.group,
            step.type,
            "step",
            "gh-box"
            // 'round-borders'
        ];

        const controls = (
            <div
                key={this.props.key}
                onMouseEnter={this.handleOnMouseEnter}
                onMouseLeave={this.handleMouseLeave}
                className={'step-wrap'}
            >
                { step.group === 'action' && <div className={'delay'}>{'Wait at least 3 days then...'}</div> }
                <div id={step.id} className={classes.join(" ")}>
                    <StepIcon type={step.type} group={step.group}
                              src={step.icon}/>
                    <StepTitle title={step.title}/>
                    {this.state.showControls && <StepControls
                        handleSelect={this.handleControlAction}
                        handleClick={this.handleClickAction}
                    />}
                    <div className={"wp-clearfix"}></div>
                </div>
            </div>
        );

        if (this.state.deleting) {
            return (
                <FadeOut then={this.afterFadeOut}>
                    {controls}
                </FadeOut>
            );
        }

        return controls;
    }

}