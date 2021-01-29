import {useState} from "@wordpress/element";
import {addNotification} from "utils/index";
import {__} from "@wordpress/i18n";
import BulkJob from "components/core-ui/bulk-job";
import {} from "@material-ui/core";
import { Redirect } from "react-router";

export const ScheduleEvents = (props) => {

    // track bulk-job is finished
    const [finish, setFinish ] = useState(false);

    // get Broadcast ID
    const {broadcast} = props;

    //build context for bulk-job operation
    let context = {
        broadcast_id: broadcast
    }


    const onFinish = (newValue) => {
        // handle the response and do any tasks which are required.
        addNotification({message: __("Broadcast scheduled successfully"), type: 'success'});
        setFinish(true);
    };

    // Redirect to main page once broadcast is scheduled sucessfully
    if (finish === true) {
        return <Redirect to={ '/broadcasts' } />
    }

    return (
        <div style={{
            padding: 24,
            background: '#fff',
        }}>
            <BulkJob
                jobId={Math.random()}
                perRequest={10}
                title={__('scheduling Events')}
                context={context}
                onFinish={onFinish}
                action={'gh_schedule_broadcast'}
            />
        </div>

    );

}