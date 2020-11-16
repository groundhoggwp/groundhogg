import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";
import React from "react";
import {Route, Switch, useRouteMatch} from "react-router-dom";
import {Table} from  './table';
import {ImportSteps} from "./import-steps";

export const Import = (props) => {

    let { path } = useRouteMatch();
    return (
        <Switch>
            <Route exact path={path}>
                <Table />
            </Route>
            <Route path={`${path}/steps`}>
                <ImportSteps />
            </Route>
        </Switch>
    )
}


//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Import", 'groundhogg'),
        path: '/import',
        description: __('First Description', 'groundhogg'),
        component: (classes) => {
            return (
                <Import/>
            )
        }
    });
    return tabs;

}, 10);