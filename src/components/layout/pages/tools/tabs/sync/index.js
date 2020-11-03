import TabPanel from "components/core-ui/tab-panel";
import {SettingsSection} from "components/layout/pages/settings/settings-section";
import {addFilter, applyFilters} from "@wordpress/hooks";
import {Fragment, render} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import ReportPanel from "components/layout/pages/reports/report-panel";


export const Sync = (props) =>{
    return (<h1> Info Page</h1>);
}



//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Sync" , 'groundhogg' ),
        path: '/sync',
        description: __('First Description'  ,'groundhogg'),
        component: (classes) => {
            return (
               <Sync />
            )
        }
    });
    return tabs;

}, 10);