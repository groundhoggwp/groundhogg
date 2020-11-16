
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";



export const Install = (props) => {
    return (<h1> Install</h1>);
}


//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Install" , 'groundhogg' ),
        path: '/install',
        description: __('First Description'  ,'groundhogg'),
        component: (classes) => {
            return (
                <Install/>
            )
        }
    });
    return tabs;

}, 10);