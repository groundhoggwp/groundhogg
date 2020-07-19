import React from "react";
import {EmailPicker} from "../../components/BasicControls/basicControls";
import {registerStepType, SimpleEditModal} from "../steps";
import {Col, Row} from "react-bootstrap";

registerStepType("send_email", {

    icon: ghEditor.steps.send_email.icon,
    group: ghEditor.steps.send_email.group,

    title: ({data, context, settings}) => {

        if (!context || !context.email_display) {
            return <>{"Select and email to send..."}</>;
        }

        return <>{"Send"} <b>{context.email_display.label}</b></>;
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const emailChanged = (value) => {
            updateSettings({
                email_id: value.value
            }, {
                email_display: value
            });
        };

        return (
            <SimpleEditModal
                title={"Send email..."}
                done={done}
                commit={commit}
                modalBodyProps={{
                    className: "no-padding"
                }}
            >
                <Row className={'no-margins'}>
                    <Col>
                        <EmailPicker
                            id={"email"}
                            value={(context &&
                                context.email_display) || false}
                            update={emailChanged}/>
                    </Col>
                </Row>
                <iframe
                    src={context.email_url_base + settings.email_id}
                    className={"email-preview"}
                />
            </SimpleEditModal>
        );
    }

});