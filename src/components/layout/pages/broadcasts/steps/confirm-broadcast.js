import {useDispatch} from "@wordpress/data";
import {BROADCASTS_STORE_NAME} from "data/broadcasts";
import Button from "@material-ui/core/Button";
import {__} from "@wordpress/i18n";
import {useState } from "@wordpress/element";



export const ConfirmBroadcast = (props) => {


    const [scheduling, setScheduling] = useState(false);

    const {handleBack, handleNext, data ,setBroadcast} = props;

    // make request to schedule the broadcast
    const {scheduleBroadcast} = useDispatch(BROADCASTS_STORE_NAME);

    const handleSchedule = () => {

        // setScheduling(true);
        // scheduleBroadcast(data).then((result) => {
        //     //set the broadcast ID
        //     setScheduling(false);
        //     setBroadcast(result.broadcast_id);
        //
        // });
        setBroadcast(219);
        handleNext();
    }

    return (
        <div style={{
            padding: 24,
            background: '#fff',
        }}>
            <h1>{console.log(data)}</h1>
            <Button variant="contained" color="secondary" onClick={handleBack} disabled={scheduling}>
                { __('Back', 'groundhogg')}
            </Button>
            <Button variant="contained" color="primary" onClick={handleSchedule} disabled={scheduling}>
                {scheduling ? __('Loading..') :__('Schedule', 'groundhogg')}
            </Button>


        </div>

    )
}