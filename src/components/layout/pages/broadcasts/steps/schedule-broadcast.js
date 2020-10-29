import BulkJob from "components/core-ui/bulk-job";
import {__} from "@wordpress/i18n";
import {BROADCASTS_STORE_NAME} from "data/broadcasts";
import {useSelect, useDispatch} from '@wordpress/data'
import Button from "@material-ui/core/Button";
import {Link} from "react-router-dom";
import {Fragment} from "@wordpress/element";
import {mergeDeep} from "utils/core";


// sets data into the broadcast data option
export const ScheduleBroadcast = (props) => {

    //code to schedule the broadcast
    // const [emailOrSmsId, setEmailOrSmsId] = useState(0); //todo add ddl
    // const [tags, setTags] = useState([]);  // todo array form multiple tag picker
    // const [excludeTags, setExcludeTags] = useState([]);  // todo array form multiple tag picker
    // const [date, setDate] = useState('');  // todo Date picker
    // const [time, setTime] = useState('');  // todo Time picker
    // const [sendNow, setSendNow] = useState('');  // todo checkbox
    // const [sendInTimezone, setSendInTimezone] = useState('');  // todo checkbox
    // const [type, setType] = useState('');  // todo type picker. returns | email or
    //
    //
    // json request to schedule broadcast
    // scheduleBroadcast({
    //     email_or_sms_id: emailOrSmsId,
    //     tags: tags,
    //     exclude_tags: excludeTags ,
    //     date: date ,
    //     time: time,
    //     send_now: sendNow ,
    //     send_in_timezone: sendInTimezone ,
    //     type: type,
    // });
    //
    // createItem({
    //     data: {
    //         email_or_sms_id: 474,
    //         tags: [156],
    //         exclude_tags: [],
    //         // date: date,
    //         // time: time,
    //         send_now: true,
    //         // send_in_timezone: sendInTimezone,
    //         type: 'email',
    //     }
    // });

    const {handleNext, setData, data} = props;

    const handleConfirm = () => {
        // merge the response.....
        setData({
            ...data
            , ...{
                email_or_sms_id: 474,
                tags: [156],
                exclude_tags: [],
                send_now: true,
                type: 'email',
            }
        });
        handleNext();
    };


    const handleChange = (event) => {
        setData({...data, ...{extra: event.target.value}});
    }
    return (
        <div style={{
            padding: 24,
            background: '#fff',

        }}>
            <input type='text' onChange={handleChange}/>
            <br />
            <Button variant="contained" color="primary" onClick={handleConfirm}>{__('Confirm', 'groundhogg')}</Button>
        </div>

    );
}