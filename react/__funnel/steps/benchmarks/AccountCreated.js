import React from "react";
import {
    ItemsCommaAndList,
    ItemsCommaOrList,
    RolesPicker, TagSpan,
} from '../../components/BasicControls/basicControls';
import {registerStepType, SimpleEditModal} from "../steps";

registerStepType("account_created", {

    icon: ghEditor.steps.account_created.icon,
    group: ghEditor.steps.account_created.group,

    title: ({data, context, settings}) => {
        if (!context.roles_display || !context.roles_display.length) {
            return <>{"Select one or more roles..."}</>;
        }

        return <>{"When"} <ItemsCommaOrList
            separator={ '' }
            items={context.roles_display.map(role => <TagSpan icon={'admin-users'} tagName={role.label}/> )}/> {"is registered"}</>;
    },

    edit: ({data, context, settings, updateSettings, commit, done}) => {

        const rolesChanged = (values) => {
            updateSettings({
                role: values && values.map(role => role.value),
            }, {
                roles_display: values
            } );
        };

        return (
            <SimpleEditModal
                title={"User registered..."}
                done={done}
                commit={commit}
            >
                <RolesPicker
                    value={(context && context.roles_display) || false}
                    update={rolesChanged}
                />
                <p className={"description"}>
                    {"Runs when a user is created with any of the given roles."}
                </p>
            </SimpleEditModal>
        );
    }

});