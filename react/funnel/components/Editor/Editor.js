import React from "react";
import "./component.scss";
import Spinner from "react-bootstrap/Spinner";
import {StepGroup} from "../StepGroup/StepGroup";
import {AddStep} from "../AddStep/AddStep";
import axios from "axios";
import {EditStep} from "../EditStep/EditStep";

export function reloadEditor() {
    const event = new CustomEvent('groundhogg-reload-editor' );
    document.dispatchEvent(event);
}

export class Editor extends React.Component {

    constructor(props) {
        super(props);

        this.state = {
            funnel: {},
            steps: []
        };

        // this.handleSetList = this.handleSetList.bind(this);
        this.handleStepsSorted = this.handleStepsSorted.bind(this);
        this.handleReloadEditor = this.handleReloadEditor.bind(this);
    }

    componentDidMount() {

        // document.addEventListener('groundhogg-add-step', this.handleAddStep );
        document.addEventListener("groundhogg-steps-sorted", this.handleStepsSorted);
        document.addEventListener("groundhogg-reload-editor", this.handleReloadEditor);

        this.setState({
            funnel: ghEditor.funnel,
            steps: ghEditor.funnel.steps
        });
    }

    handleStepsSorted(e) {

        let id;
        let self = this;

        const newStepOrder = [];

        const steps = jQuery(".step");

        steps.each(function () {
            id = jQuery(this).attr("id");
            newStepOrder.push(self.state.steps.find(step => step.id == id));
        });

        this.setState({steps: newStepOrder});

        axios.patch(groundhogg_endpoints.funnels, {
            funnel_id: ghEditor.funnel.id,
            steps: newStepOrder
        });
    }

    handleReloadEditor(e) {
        axios.get(groundhogg_endpoints.funnels + "?funnel_id=" + ghEditor.funnel.id
        ).then(result => this.setState({
            steps: result.data.funnel.steps,
            funnel: result.data.funnel
        }));
    }

    render() {

        if (!this.state.steps.length) {
            return <Spinner animation={"border"}/>;
        }

        const inner = [];

        const groups = this.state.steps.reduce(function (prev, curr) {
            if (prev.length && curr.group === prev[prev.length - 1][0].group) {
                prev[prev.length - 1].push(curr);
            } else {
                prev.push([curr]);
            }
            return prev;
        }, []);

        const self = this;

        groups.forEach(function (group, i) {
            inner.push(<StepGroup
                steps={group}
                isFirst={i === 0}
                isLast={i === groups.length - 1}
            />);
        });

        console.debug(groups);

        return (
            <div
                id="groundhogg-funnel-editor"
                className="groundhogg-funnel-editor"
            >
                <div className={"step-groups"}>
                    {inner}
                </div>
                <div className={"editor-controls"}>
                    <AddStep/>
                    <EditStep/>
                </div>
            </div>
        );

    }

}