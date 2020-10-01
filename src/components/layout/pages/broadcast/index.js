/**
 * External dependencies
 */
import {Component, Fragment} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withSelect, withDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import Button from '@material-ui/core/Button';
import {castArray} from 'lodash';
import Spinner from '../../../core-ui/spinner';

/**
 * Internal dependencies
 */
import {BROADCASTS_STORE_NAME} from '../../../../data';

class Broadcasts extends Component {

    constructor() {
        super(...arguments);


        // used to manage form data. updated based on form events.
        this.state = {
            email_or_sms_id : 474,
            tags : [168] ,
            exclude_tags  : [] ,
            date : '' ,
            time : '' ,
            send_now  : true ,
            send_in_timezone  : '' ,
            type  : 'email'
        }

        this.onScheduleBroadcast = this.onScheduleBroadcast.bind(this);
        this.onCancelBroadcast = this.onCancelBroadcast.bind(this);
    }

    /**
     * Makes the post request to schedule broadcast
     */
    onScheduleBroadcast() {
        //todo validate the form to make sure all the required data is present.
        const {scheduleBroadcast,getBroadcasts} =  this.props;
        scheduleBroadcast(this.state); // creates a new broadcast still needs to run bulkjob!
        getBroadcasts();

    }

    onCancelBroadcast() {
        // its a bulk job process table


    }

    onDeleteBroadcast(){
        // its a bulk job process for table

    }


    //
    // setValue( event ) {
    // 	this.setState( {
    // 		tagValue : event.target.value
    // 	} )
    // }
    //
    // async onSubmit() {
    // 	const {
    // 		tagValue
    // 	} = this.state;
    //
    // 	const {
    // 		updateTags,
    // 		tags
    // 	} = this.props;
    //
    // 	if ( tags.tags.length ) {
    // 		this.setState( { tags : tags } );
    // 	}
    //
    // 	const updatingTags = updateTags( { tags : tagValue } )
    //
    // 	console.log(updatingTags);
    //
    // 	this.setState( { tags : updatingTags } );
    // }

    render() {

        const {isUpdateRequesting} = this.props;
        const broadcasts = castArray(this.props.broadcasts.broadcasts);

        return (
            <Fragment>
                <h2>Broadcast</h2>
                <Button variant="contained" color="primary" onClick={this.onScheduleBroadcast}>
                    {__('Schedule Broadcast', 'groundhogg')}
                </Button>

                <Button variant="contained" color="secondary" onClick={this.onCancelBroadcast}>
                    {__('Cancel Broadcast', 'groundhogg')}
                </Button>

                {(isUpdateRequesting) && (
                    <Spinner/>
                )}


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
}

// default export
export default compose(
    withSelect((select) => {
        const {
            getBroadcasts,
            isBroadcastsUpdating
        } = select(BROADCASTS_STORE_NAME);

        const broadcasts = getBroadcasts();
        const isUpdateRequesting = isBroadcastsUpdating();

        return {
            broadcasts,
            isUpdateRequesting
        };
    }),
    withDispatch((dispatch) => {

        const {
            scheduleBroadcast,
            cancelBroadcast
        } = dispatch(BROADCASTS_STORE_NAME);
        return {
            scheduleBroadcast,
            cancelBroadcast
        };
    })
)(Broadcasts);