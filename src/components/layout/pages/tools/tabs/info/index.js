import TabPanel from "components/core-ui/tab-panel";
import {SettingsSection} from "components/layout/pages/settings/settings-section";
import {addFilter, applyFilters} from "@wordpress/hooks";
import {Fragment, render} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import ReportPanel from "components/layout/pages/reports/report-panel";
import {Updates} from "components/layout/pages/tools/tabs/update";


export const Info = (props) => {
    return (<h1> Info Page</h1>);
}


//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("System Info & Debug" , 'groundhogg' ),
        path: '/info',
        description: __('First Description'  ,'groundhogg'),
        component: (classes) => {
            return (
                <Info />
            )
        }
    });
    return tabs;

}, 10);