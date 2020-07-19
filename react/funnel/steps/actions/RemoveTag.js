import React from "react";
import {ItemsCommaAndList, TagPicker} from "../../components/BasicControls/basicControls";
import {registerStepType, SimpleEditModal} from "../steps";

registerStepType("remove_tag", {

    icon: ghEditor.steps.remove_tag.icon,
    group: ghEditor.steps.remove_tag.group,

    title: ({data, context, settings}) => {

        if (!context || !context.tags_display ||
            !context.tags_display.length) {
            return <>{"Select tags to add..."}</>;
        }

        return <>{"Remove"} <ItemsCommaAndList
            items={context.tags_display.map(tag => tag.label)}/></>;
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const tagsChanged = (values) => {
            updateSettings({
                tags: values.map(tag => tag.value),
            }, {
                tags_display: values
            } );
        };

        return (
            <SimpleEditModal
                title={"Remove tags..."}
                done={done}
                commit={commit}
            >
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