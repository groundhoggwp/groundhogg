import {useDispatch, useSelect} from "@wordpress/data";
import {BROADCASTS_STORE_NAME} from "data/broadcasts";
import Button from "@material-ui/core/Button";
import {__} from "@wordpress/i18n";
import {useState, Fragment} from "@wordpress/element";
import {EMAILS_STORE_NAME} from "data/emails";
import {CONTACTS_STORE_NAME} from "data/contacts";


export const ConfirmBroadcast = (props) => {

    const [scheduling, setScheduling] = useState(false);

    const {handleBack, handleNext, data, setBroadcast} = props;
    const {email_or_sms_id, type, tags, exclude_tags} = data;

    // make request to schedule the broadcast
    const {scheduleBroadcast} = useDispatch(BROADCASTS_STORE_NAME);

    //finding Email Or SMS details

    const {object} = useSelect((select) => {
        if (email_or_sms_id > 0 && type === 'email') {
            const store = select(EMAILS_STORE_NAME);
            return {
                object: store.getItem(email_or_sms_id),
            }
        } else {
            return {
                object: ''
            }
        }
    }, []);


    // Finding details about contact

    const {contacts, total} = useSelect((select) => {
        const store = select(CONTACTS_STORE_NAME);
        return {
            contacts: store.getItems({
                tags_include: tags,
                tags_exclude: exclude_tags
            }),
            total: store.getTotalItems()
        }

    }, []);

    const handleSchedule = () => {
        setScheduling(true);
        scheduleBroadcast(data).then((result) => {
            //set the broadcast ID
            setScheduling(false);
            setBroadcast(result.broadcast_id);
            handleNext();
        }).catch((e) => {
            console.log();
        });
    }

    var title = '';
    if (object && object.hasOwnProperty('data') && object.data.hasOwnProperty('title')) {
        title = object.data.title;

    }
    var sending_count = 0;
    if (total) {
        sending_count = total;
    }


    return (
        <Fragment>
            <div style={{
                padding: 24,
                background: '#fff',
            }}>
                <h3> Email: {title} </h3>
                <h3> sending TO: {sending_count} contacts </h3>
            </div>

            <div style={{
                padding: 24,
                background: '#fff',
            }}>
                <Button variant="contained" color="secondary" onClick={handleBack} disabled={scheduling}>
                    {__('Back', 'groundhogg')}
                </Button>
                <Button variant="contained" color="primary" onClick={handleSchedule} disabled={scheduling}>
                    {scheduling ? __('Loading..') : __('Schedule', 'groundhogg')}
                </Button>
            </div>
        </Fragment>
    )
}