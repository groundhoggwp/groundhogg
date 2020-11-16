
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";



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