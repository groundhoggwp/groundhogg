/**
 * External dependencies
 */
import {Component, Fragment, useState} from '@wordpress/element';
import {compose} from '@wordpress/compose';
import {withSelect, withDispatch, useDispatch, useSelect} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import Button from '@material-ui/core/Button';
import {castArray} from 'lodash';
import Spinner from '../../../core-ui/spinner';

/**
 * Internal dependencies
 */
import {EVENTS_STORE_NAME} from '../../../../data';

export const Events = (props) => {
    // {}
    const [view, setView] = useState('');

    const {runAgain, uncancelEvent, cancelEvent, } = useDispatch(EVENTS_STORE_NAME); // call these methods to handle event // cancelEvent( { events : [] });

    const {events, isRequesting, isUpdating, getEvents } = useSelect((select) => {
        const store = select(EVENTS_STORE_NAME);
        return {
            events: castArray(store.getEvents().events),
            isRequesting: store.isEventsRequesting(), // used for get request
            isUpdating: store.isEventsUpdating(), // used for any other operation
            getEvents : store.getEvents
        }
    });

    if (isRequesting || isUpdating) {
        return <Spinner/>;
    }

    function LoadEvents(event) {
        console.log(event.target.value);
        getEvents(event.target.value);

    }

    return (
        <Fragment>
            <h2>Events</h2>

            <input type="button" value={'waiting'}   onClick={LoadEvents}  />
            <input type="button" value={'cancelled'} onClick={LoadEvents}  />
            <input type="button" value={'complete'}  onClick={LoadEvents} />
            <input type="button" value={'failed'}    onClick={LoadEvents} />
            <input type="button" value={'skipped'}   onClick={LoadEvents} />

            <table>
                <th>
                    <td>
                        ID
                    </td>
                    <td>
                        Email
                    </td>
                    <td>
                        Funnel Title
                    </td>
                    <td>
                        step Title
                    </td>
                    <td>
                        Status
                    </td>
                    <td>
                        ACTION
                    </td>
                </th>
                {
                    events.map((event) => {
                        return (
                            <tr>
                                <td>
                                    {event.ID}
                                </td><td>
                                    {event.contact_email}
                                </td>
                                <td>
                                    {event.funnel_title}
                                </td>
                                <td>
                                    {event.step_title}
                                </td>
                                <td>
                                    {event.status}
                                </td>
                                <td>
                                    {event.status === 'cancelled' ?    <input type="button" data-event_id={event.ID} data-status={event.status} value={'uncancel '} onClick={(event ) => { cancelEvent( { events : [event.target.dataset.event_id] }); /*this.onUnCancelEvent*/ } } />  /*  */  : ''}
                                    {event.status === 'waiting' ?      <input type="button" data-event_id={event.ID} data-status={event.status} value={'cancel   '} onClick={(event ) => {   cancelEvent( { events : [event.target.dataset.event_id] }); /*this.onCancelEvent  */ } } />  /*  */  : ''}
                                    {event.status === 'complete' ?     <input type="button" data-event_id={event.ID} data-status={event.status} value={'run again'} onClick={(event ) => {  runAgain( { events : [event.target.dataset.event_id] }); /*this.onRunAgain     */ } } />  /*  */  : ''}
                                    {event.status === 'failed' ?       <input type="button" data-event_id={event.ID} data-status={event.status} value={'run again'} onClick={(event ) => {  runAgain( { events : [event.target.dataset.event_id] }); /*this.onRunAgain     */ } } />  /*  */  : ''}
                                    {event.status === 'skipped' ?      <input type="button" data-event_id={event.ID} data-status={event.status} value={'run again'} onClick={(event ) => {  runAgain( { events : [event.target.dataset.event_id] }); /*this.onRunAgain     */ } } /> /* '*/  : ''}
                                </td>
                            </tr>
                        )
                    })
                }
            </table>
        </Fragment>
    );
};


//
// class Events extends Component {
//
//     constructor() {
//         super(...arguments);
//
//
//         // state will used to perform the bulk action on the list .
//         this.state = {}
//
//         this.onUnCancelEvent = this.onUnCancelEvent.bind(this);
//         this.onCancelEvent = this.onCancelEvent.bind(this);
//         this.onRunAgain = this.onRunAgain.bind(this);
//     }
//
//     /**
//      * Makes the post request to schedule broadcast
//      */
//
//     onUnCancelEvent(event) {
//
//         this.props.uncancelEvent(
//             {
//                 events : []
//             }
//         );
//     }
//
//     onCancelEvent(event) {
//
//         this.props.cancelEvent(
//             {
//                 events : []
//             }
//         );
//
//     }
//
//
//     onRunAgain(event) {
//
//         // get the ID of the event
//
//         this.props.runAgain(
//             {
//                 events : []
//             }
//         );
//
//     }
//     render() {
//
//         const {isUpdateRequesting} = this.props;
//         const events = castArray(this.props.events.events);
//
//         return (
//             <Fragment>
//                 <h2>Events</h2>
//
//                 {(isUpdateRequesting) && (
//                     <Spinner/>
//                 )}
//                 <ol>
//                     {
//                         events.map((event) => {
//                             return (
//                                 <li>
//
//                                     {event.status} |{event.contact_email} | {event.funnel_title} | {event.step_title} |
//                                     {event.status === 'cancelled' ? <p onClick={this.onUnCancelEvent} > uncancel </p> : ''} |
//                                     {event.status === 'waiting' ? <p onClick={this.onCancelEvent} > cancel </p> : ''} |
//                                     {event.status === 'complete' ? <p onClick={this.onRunAgain}> run again </p> : ''} |
//                                     {event.status === 'failed' ? <p onClick={this.onRunAgain} > run again </p> : ''}
//
//
//                                 </li>
//                             )
//                         })
//                     }
//                 </ol>
//             </Fragment>
//         );
//     }
// }
//
// // default export
// export default compose(
//     withSelect((select) => {
//         const {
//             getEvents,
//             isEventsUpdating
//         } = select(EVENTS_STORE_NAME);
//
//         const events = getEvents();
//         const isUpdateRequesting = isEventsUpdating();
//
//         return {
//             events,
//             isUpdateRequesting
//         };
//     }),
//     withDispatch((dispatch) => {
//         const {
//             runAgain,
//             uncancelEvent,
//             cancelEvent
//         } = dispatch(EVENTS_STORE_NAME);
//         return {
//             runAgain,
//             uncancelEvent,
//             cancelEvent
//         };
//     })
// )(Events);