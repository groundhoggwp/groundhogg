import TabPanel from "components/core-ui/tab-panel";
import {SettingsSection} from "components/layout/pages/settings/settings-section";
import {addFilter, applyFilters} from "@wordpress/hooks";
import {Fragment, render} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import ReportPanel from "components/layout/pages/reports/report-panel";


export const Import = (props) =>{
    //displaying table based on the file
    return (<h1> Import</h1>);

}



//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Import" , 'groundhogg' ),
        path: '/import',
        description: __('First Description'  ,'groundhogg'),
        component: (classes) => {
            return (
               <Import />
            )
        }
    });
    return tabs;

}, 10);