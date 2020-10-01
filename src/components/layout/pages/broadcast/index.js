/**
 * External dependencies
 */
import {Fragment, useState, getState} from '@wordpress/element';
import {useSelect, useDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import Button from '@material-ui/core/Button';
import {castArray} from 'lodash';
import Spinner from '../../../core-ui/spinner';


/**
 * Internal dependencies
 */
import {BROADCASTS_STORE_NAME} from '../../../../data';

export const Broadcasts = (props) => {

    // getting all the state variables
    const [emailOrSmsId, setEmailOrSmsId] = useState(0); //todo add ddl
    const [tags, setTags] = useState([]);  // todo array form multiple tag picker
    const [excludeTags, setExcludeTags] = useState([]);  // todo array form multiple tag picker
    const [date, setDate] = useState('');  // todo Date picker
    const [time, setTime] = useState('');  // todo Time picker
    const [sendNow, setSendNow] = useState('');  // todo checkbox
    const [sendInTimezone, setSendInTimezone] = useState('');  // todo checkbox
    const [type, setType] = useState('');  // todo type picker. returns | email or SMS

    const {scheduleBroadcast, cancelBroadcast} = useDispatch(BROADCASTS_STORE_NAME);

    const {broadcasts, isRequesting, isUpdating} = useSelect((select) => {

        const store = select(BROADCASTS_STORE_NAME);
        return {
            broadcasts: castArray(store.getBroadcasts().broadcasts),
            isRequesting: store.isBroadcastsRequesting(),
            isUpdating: store.isBroadcastsUpdating()
        }
    });

    if (isRequesting || isUpdating) {
        return <Spinner/>;
    }


    // setting values for Broadcast test


    return (
        <Fragment>
            <h2>Broadcast</h2>
            <Button variant="contained" color="primary" onClick={() => {
                //print the value

                //set the value statically

                // json request to schedule broadcast
                scheduleBroadcast({
                    email_or_sms_id: emailOrSmsId,
                    tags: tags,
                    exclude_tags: excludeTags ,
                    date: date ,
                    time: time,
                    send_now: sendNow ,
                    send_in_timezone: sendInTimezone ,
                    type: type,
                });
            }}>
                {__('Schedule Broadcast', 'groundhogg')}
            </Button>
            <br/>
            <Button variant="contained" color="secondary" onClick={() => {

                setEmailOrSmsId(474);
                setTags( [168] );
                setSendNow(true);
                setType('email');

            }}>
                {__('Cancel Broadcast', 'groundhogg')}
            </Button>

            {(isUpdating) && (<Spinner/>)}

            <ol>
                {
                    broadcasts.map((broadcast) => {
                        return (<li>{broadcast.title}</li>)
                    })
                }
            </ol>
        </Fragment>
    );
}
