import React from "react";
import {
    ItemsCommaAndList,
    ItemsCommaOrList,
    TagPicker,
    YesNoToggle
} from "../../components/BasicControls/basicControls";
import {registerStepType, SimpleEditModal} from "../steps";
import {Col, Row} from "react-bootstrap";

registerStepType("tag_applied", {

    icon: ghEditor.steps.tag_applied.icon,
    group: ghEditor.steps.tag_applied.group,

    title: ({data, context, settings}) => {

        if (!context || !context.tags_display ||
            !context.tags_display.length) {
            return <>{"Select tag requirements..."}</>;
        }

        if (settings.condition === "any") {
            return <>{"When"} <ItemsCommaOrList
                items={context.tags_display.map(tag => tag.label)}/> {"are applied"}</>;
        } else {
            return <>{"When"} <ItemsCommaAndList
                items={context.tags_display.map(tag => tag.label)}/> {"are applied"}</>;
        }
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const tagsChanged = (values) => {
            updateSettings({
                tags: values.map(tag => tag.value)
            }, {
                tags_display: values
            });
        };

        const conditionChanged = (e) => {
            updateSettings({
                condition: e.target.value
            });
        };

        const conditions = [
            {value: "any", label: "Any"},
            {value: "all", label: "All"}
        ];

        return (
            <SimpleEditModal
                title={"Tag applied..."}
                done={done}
                commit={commit}
            >
                <div><p>{"Runs when"} <select
                    value={settings.condition}
                    onChange={conditionChanged}>
                    {conditions.map(condition => <option
                        value={condition.value}>{condition.label}</option>)}
                </select> {"of the following tags are applied..."}
                </p></div>
                <TagPicker
                    id={"tags"}
                    value={(context && context.tags_display) || false}
                    update={tagsChanged}
                />
                <p className={"description"}>{"Add new tags by hitting [enter] or [tab]"}</p>
            </SimpleEditModal>
        );
    }

});