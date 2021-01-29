
import {addFilter, applyFilters} from "@wordpress/hooks";
import {__} from "@wordpress/i18n";
import EmailPicker from 'components/core-ui/email-picker'
import { useState } from '@wordpress/element'



export const Updates  = (props) =>{
    const  [email , setEmail ] =useState ();

    console.log(email)

    console.log(email)
    return (
      <EmailPicker onChange = {setEmail} value={email} />
    );
}



//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Updates" , 'groundhogg' ),
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