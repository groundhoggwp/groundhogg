import React from "react";
import {CopyInput, LinkPicker} from "../../components/BasicControls/basicControls";
import {registerStepType, SimpleEditModal} from "../steps";

registerStepType("link_click", {

    icon: ghEditor.steps.link_click.icon,
    group: ghEditor.steps.link_click.group,

    title: ({data, context, settings}) => {
        return <>{"When this link is clicked"}</>;
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const linkChanged = (link) => {
            updateSettings({
                redirect_to: link
            });
        };

        return (
            <SimpleEditModal
                title={"Link clicked..."}
                done={done}
                commit={commit}
            >
                <p>{"Runs when the following link is clicked..."}</p>
                <CopyInput content={context.tracking_link} />
                <p className={"description"}>{"Copy this link and use it anywhere the contact can be tracked."}</p>
                <p>{"Then redirect to..."}</p>
                <LinkPicker
                    update={linkChanged}
                    value={settings.redirect_to}
                />
            </SimpleEditModal>
        );
    }

});