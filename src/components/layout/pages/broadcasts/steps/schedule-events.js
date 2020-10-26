import {useState} from "@wordpress/element";
import {addNotification} from "utils/index";
import {__} from "@wordpress/i18n";
import BulkJob from "components/core-ui/bulk-job";
import {} from "@material-ui/core";

export const ScheduleEvents = (props) => {


    /**
     * BULKJOB CODE
     *
     **/

    // setting values for Broadcast test
    const onFinish = (newValue) => {
        // handle the response and do any tasks which are required.

        addNotification({message: __("Broadcast scheduled "), type: 'success'}); // NOT working
    };


    // if (bulkJob) {
    return (

        <div style={{
            padding : 24,
            background : '#fff',

        }}>

        </div>

    );
    // }

}