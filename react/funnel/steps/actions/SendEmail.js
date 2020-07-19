import React from "react";
import {EmailPicker} from "../../components/BasicControls/basicControls";
import {registerStepType, SimpleEditModal} from "../steps";

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
            >
                <EmailPicker
                    id={"email"}
                    value={(context &&
                        context.email_display) || false}
                    update={emailChanged}/>
            </SimpleEditModal>
        );
    }

});