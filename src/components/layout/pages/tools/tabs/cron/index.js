import TabPanel from "components/core-ui/tab-panel";
import {SettingsSection} from "components/layout/pages/settings/settings-section";
import {addFilter, applyFilters} from "@wordpress/hooks";
import {Fragment, render} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import ReportPanel from "components/layout/pages/reports/report-panel";


export const Cron  = (props) =>{
    return (<h1> cron </h1>);
}



//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Advance Cron setup" , 'groundhogg' ),
        path: '/cron',
        description: __('First Description'  ,'groundhogg'),
        component: (classes) => {
            return (
               <Cron />
            )
        }
    });
    return tabs;

}, 10);