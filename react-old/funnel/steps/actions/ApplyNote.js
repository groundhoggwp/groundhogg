import React from "react";
import {registerStepType, SimpleEditModal} from "../steps";
import {TextArea} from "../../components/BasicControls/basicControls";

registerStepType("apply_note", {

    icon: ghEditor.steps.apply_note.icon,
    group: ghEditor.steps.apply_note.group,

    title: ({data, context, settings}) => {

        if (!settings.note_title) {
            return <>{"Write a note to add..."}</>;
        }

        return <>{"Add note "} <b>{settings.note_title}</b></>;
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const updateSetting = (id, value) => {
            updateSettings({
                [id]: value
            });
        };

        return (
            <SimpleEditModal
                title={"Add note..."}
                done={done}
                commit={commit}
            >
                <p>
                    <input
                        type={"text"}
                        className={"note-title"}
                        value={settings.note_title}
                        onChange={(e) => {
                            updateSetting("note_title", e.target.value);
                        }}
                        placeholder={"Note title..."}
                    />
                </p>
                <p>
                    <TextArea
                        id={"note_text"}
                        update={(v) => updateSetting("note_text", v)}
                        hasReplacements={true}
                        value={settings.note_text}
                        options={{
                            placeholder: "Note content...",
                            className: "note-content"
                        }}
                    />
                </p>
            </SimpleEditModal>
        );
    }

});