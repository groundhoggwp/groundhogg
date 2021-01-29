import React from "react";
import {Col, Row} from "react-bootstrap";
import {YesNoToggle} from "../../components/BasicControls/basicControls";
import {registerStepType, SimpleEditModal} from "../steps";

registerStepType("email_confirmed", {

    icon: ghEditor.steps.email_confirmed.icon,
    group: ghEditor.steps.email_confirmed.group,

    title: ({data, settings}) => {
        return "Email address was confirmed";
    },

    edit: ({data, settings, updateSettings, commit, done}) => {

        const valueChanged = (value) => {
            updateSettings({
                skip_to: value
            });
        };

        return (
            <SimpleEditModal
                title={"Email confirmed..."}
                done={done}
                commit={commit}
            >
                <Row className={"step-setting-control"}>
                    <Col sm={8}>
                        <label>{"Skip to here if already confirmed?"}</label>
                    </Col>
                    <Col sm={4}>
                        <YesNoToggle
                            value={settings.skip_to}
                            update={valueChanged}
                        />
                    </Col>
                </Row>
                <p className={"description"}>{"If the contact enters this funnel, but their email address has already been confirmed, automatically skip to this point."}</p>
            </SimpleEditModal>
        );
    }
});
