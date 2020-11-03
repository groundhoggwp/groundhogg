import TabPanel from "components/core-ui/tab-panel";
import {SettingsSection} from "components/layout/pages/settings/settings-section";
import {addFilter, applyFilters} from "@wordpress/hooks";
import {Fragment, render} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import ReportPanel from "components/layout/pages/reports/report-panel";


export const Updates  = (props) =>{
    return (<h1>Updates </h1>);
}



//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("updates" , 'groundhogg' ),
        path: '/updates',
        description: __('First Description'  ,'groundhogg'),
        component: (classes) => {
            return (
               <Updates />
            )
        }
    });
    return tabs;

}, 10);