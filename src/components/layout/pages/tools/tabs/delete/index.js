
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";



export const Delete = (props) =>{
    return (<h1> Delete</h1>);
}



//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Bulk Delete" , 'groundhogg' ),
        path: '/delete',
        description: __('First Description'  ,'groundhogg'),    component: (classes) => {
            return (
               <Delete />
            )
        }
    });
    return tabs;

}, 10);