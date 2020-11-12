
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";



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