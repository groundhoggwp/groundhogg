
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";

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