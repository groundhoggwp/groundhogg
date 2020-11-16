import BulkJob from "components/core-ui/bulk-job";
import {__} from "@wordpress/i18n";
import {BROADCASTS_STORE_NAME} from "data/broadcasts";
import {useSelect, useDispatch} from '@wordpress/data'
import Button from "@material-ui/core/Button";
import {Link} from "react-router-dom";
import {Fragment} from "@wordpress/element";
import {mergeDeep} from "utils/core";
import {TagPicker} from "components/core-ui/tag-picker";
import TextField from '@material-ui/core/TextField';


// sets data into the broadcast data option
export const ScheduleBroadcast = (props) => {

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
    const {date, time, tags, exclude_tags, email_or_sms_id} = data;


    const handleConfirm = () => {
        // merge the response.....
        setData({
            ...data
            , ...{
                //Success Test
                email_or_sms_id: 474,
                tags: [156],
                exclude_tags: [],
                send_now: true,
                type: 'email',

            // Error test
            // email_or_sms_id: 479,
            //     tags: [156],
            // exclude_tags: [],
            // send_now: true,
            // type: 'email',

            }
        });
        handleNext();
    };


    const handleChange = (event) => {
        setData({...data, ...{email_or_sms_id: event.target.value}});
    }

    const handleDateChange = (event) => {
        setData({...data, ...{date: event.target.value}})
    }

    const handleTimeChange = (event) => {
        setData({...data, ...{time: event.target.value}})
    }


    return (

        <Fragment>
            <div style={{
                padding: 24,
                background: '#fff',

            }}>

                <h1>TODO WHEN component library is completed </h1>


                <h3>Email ID</h3>
                <input type='text' value={email_or_sms_id} onChange={handleChange}/>
                <br/>

                <h3> Apply Tags </h3>

                <h3> Exclude Tags </h3>


                <h3> Send on </h3>
                <br/>

                <TextField
                    id="date"
                    label={__('Date')}
                    type="date"
                    value={date}
                    onChange={handleDateChange}
                    InputLabelProps={{
                        shrink: true,
                    }}
                    InputProps={{inputProps: {min: "2020-10-29"}}}
                />

                <TextField
                    id="time"
                    label={__('Time', 'groundhogg')}
                    type="time"
                    defaultValue="09:00"
                    value={time}
                    onChange={handleTimeChange}
                    InputLabelProps={{
                        shrink: true,
                    }}
                    inputProps={{
                        step: 300, // 5 min
                    }}
                />

            </div>

            <div style={{
                padding: 24,
                background: '#fff',
                marginTop: 20

            }}>
                <Button variant="contained" color="primary"
                        onClick={handleConfirm}>{__('Confirm', 'groundhogg')}</Button>
            </div>
        </Fragment>

    );
}