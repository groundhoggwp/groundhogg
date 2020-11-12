
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";


export const Sync = (props) =>{
    return (<h1>Sync Page </h1>);
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