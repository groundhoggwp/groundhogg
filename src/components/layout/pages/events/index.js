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
import {EVENTS_STORE_NAME} from '../../../../data';

class Events extends Component {

    constructor() {
        super(...arguments);


        // state will used to perform the bulk action on the list .
        this.state = {}

        this.onUnCancelEvent = this.onUnCancelEvent.bind(this);
        this.onCancelEvent = this.onCancelEvent.bind(this);
        this.onRunAgain = this.onRunAgain.bind(this);
    }

    /**
     * Makes the post request to schedule broadcast
     */

    onUnCancelEvent(event) {

        this.props.uncancelEvent(
            {
                events : []
            }
        );
    }

    onCancelEvent(event) {

        this.props.cancelEvent(
            {
                events : []
            }
        );

    }


    onRunAgain(event) {

        // get the ID of the event

        this.props.runAgain(
            {
                events : []
            }
        );

    }
    render() {

        const {isUpdateRequesting} = this.props;
        const events = castArray(this.props.events.events);

        return (
            <Fragment>
                <h2>Events</h2>

                {(isUpdateRequesting) && (
                    <Spinner/>
                )}
                <ol>
                    {
                        events.map((event) => {
                            return (
                                <li>

                                    {event.status} |{event.contact_email} | {event.funnel_title} | {event.step_title} |
                                    {event.status === 'cancelled' ? <p onClick={this.onUnCancelEvent} > uncancel </p> : ''} |
                                    {event.status === 'waiting' ? <p onClick={this.onCancelEvent} > cancel </p> : ''} |
                                    {event.status === 'complete' ? <p onClick={this.onRunAgain}> run again </p> : ''} |
                                    {event.status === 'failed' ? <p onClick={this.onRunAgain} > run again </p> : ''}


                                </li>
                            )
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
            getEvents,
            isEventsUpdating
        } = select(EVENTS_STORE_NAME);

        const events = getEvents();
        const isUpdateRequesting = isEventsUpdating();

        return {
            events,
            isUpdateRequesting
        };
    }),
    withDispatch((dispatch) => {
        const {
            runAgain,
            uncancelEvent,
            cancelEvent
        } = dispatch(EVENTS_STORE_NAME);
        return {
            runAgain,
            uncancelEvent,
            cancelEvent
        };
    })
)(Events);