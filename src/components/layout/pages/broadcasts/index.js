/**
 * External dependencies
 */
import {Fragment, useState, getState} from '@wordpress/element';
import {useSelect, useDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import Button from '@material-ui/core/Button';
import {castArray} from 'lodash';
import Spinner from '../../../core-ui/spinner';

import BulkJob from '../../../core-ui/bulk-job'


/**
 * Internal dependencies
 */
import {BROADCASTS_STORE_NAME} from '../../../../data';
import {addNotification} from "utils/index";
import Box from "@material-ui/core/Box";
import {ListTable} from "components/core-ui/list-table/new";
import {TagPicker} from "components/core-ui/tag-picker";
import {Card} from "@material-ui/core";


const BroadcastsTableColumns = [
    {
        ID: 'object_id',
        name: 'ID',
        orderBy: 'objcet_id',
        align: 'right',
        cell: ({data}) => {
            return data.object_id
        },
    },


    // {
    //     ID: 'title',
    //     name: <span><LocalOfferIcon { ...iconProps }/> { 'Name' }</span>,
    //     orderBy: '',
    //     align: 'left',
    //     cell: ({ title }) => {
    //         return title
    //     },
    // },
    // {
    //     ID: 'description',
    //     name: <span>{ 'Description' }</span>,
    //     align: 'left',
    //     cell: ({ data }) => {
    //         return <>{ data.tag_description }</>
    //     },
    // },
    // {
    //     ID: 'contacts',
    //     name: <span><GroupIcon { ...iconProps }/> { 'Contacts' }</span>,
    //     orderBy: 'contact_count',
    //     align: 'right',
    //     cell: ({ data }) => {
    //         return <>{ data.contact_count }</>
    //     },
    // },
    // {
    //     ID: 'contacts',
    //     name: <span><SettingsIcon { ...iconProps }/> { 'Actions' }</span>,
    //     align: 'right',
    //     cell: ({ ID, data, openQuickEdit }) => {
    //
    //         const { deleteItem } = useDispatch(TAGS_STORE_NAME)
    //
    //         const handleEdit = () => {
    //             openQuickEdit()
    //         }
    //
    //         const handleDelete = (ID) => {
    //             deleteItem(ID)
    //         }
    //
    //         return <>
    //             <RowActions
    //                 onEdit={ openQuickEdit }
    //                 onDelete={ () => handleDelete(ID) }
    //             />
    //         </>
    //     },
    // },
];

const ScheduleBroadcast = () => {

    const tagAppliedChange = (value) =>{
        //
        // let  arr= [];
        // // if(!empty(value)) {
        // //     let arr = value.map( (item )=> {return item.value});
        // // }
        // setTagApplied(arr);
    }


    const tagRemoveChange = (value) =>{
        //
        // let  arr= [];
        // // if(!empty(value)) {
        // //     let arr = value.map( (item )=> {return item.value});
        // // }
        // setTagApplied(arr);
    }

    // Builidng the Form to Schedule Boroadcast
    return (

        <Fragment>
            <Card >
                Applied Tag
                <TagPicker onChange={tagAppliedChange} value={tagApplied}/>
                Remove Tag
                <TagPicker onChange={tagRemoveChange} value={tagApplied}/>
            </Card>
        </Fragment>

    );


}

export const Broadcasts = (props) => {

    // // getting all the state variables
    // const [emailOrSmsId, setEmailOrSmsId] = useState(0); //todo add ddl
    // const [tags, setTags] = useState([]);  // todo array form multiple tag picker
    // const [excludeTags, setExcludeTags] = useState([]);  // todo array form multiple tag picker
    // const [date, setDate] = useState('');  // todo Date picker
    // const [time, setTime] = useState('');  // todo Time picker
    // const [sendNow, setSendNow] = useState('');  // todo checkbox
    // const [sendInTimezone, setSendInTimezone] = useState('');  // todo checkbox
    // const [type, setType] = useState('');  // todo type picker. returns | email or SMS

    const {items, totalItems, isRequesting} = useSelect((select) => {
        const store = select(BROADCASTS_STORE_NAME)

        return {
            items: store.getItems(),
            totalItems: store.getTotalItems(),
            isRequesting: store.isItemsRequesting(),
        }
    }, [])

    const {fetchItems} = useDispatch(BROADCASTS_STORE_NAME)


    // const {scheduleBroadcast, cancelBroadcast} = useDispatch(BROADCASTS_STORE_NAME);
    //
    // const {broadcasts, isRequesting, isUpdating} = useSelect((select) => {
    //
    //     const store = select(BROADCASTS_STORE_NAME);
    //     return {
    //         broadcasts: castArray(store.getBroadcasts().broadcasts),
    //         isRequesting: store.isBroadcastsRequesting(),
    //         isUpdating: store.isBroadcastsUpdating()
    //     }
    // });
    //
    // if (isRequesting || isUpdating) {
    //     return <Spinner/>;
    // }


    /**
     * BULKJOB CODE
     *
     **/
    const [bulkJob, setBulkJob] = useState(false);

    // setting values for Broadcast test
    const handleClose = (newValue) => {
        // handle the response
        setBulkJob(false);
        addNotification({message: __("Broadcast scheduled "), type: 'success'}); // NOT working
    };


    if (bulkJob) {
        return (
            <BulkJob
                keepMounted
                id="ringtone-menu"
                open={true}
                onClose={handleClose}
                title={__('Schedule Broadcast')}
                action='gh_schedule_broadcast' //Bulk job action name which you want to perfrom
                jobId={Math.random()} //any random number to create the unique transient
                context={{
                    broadcast_id: 218
                }}// object set by the scheduler to perform the task
                perRequest={10}
            />
        );
    }

    return (
        <Fragment>

            {/*    // set variables for the request*/}
            {/*    setEmailOrSmsId(474);*/}
            {/*    setTags( [168] );*/}
            {/*    setSendNow(true);*/}
            {/*    setType('email');*/}

            {/*}}>*/}
            {/*    {__('Cancel Broadcast', 'groundhogg')}*/}
            {/*</Button>*/}

            {/*{(isUpdating) && (<Spinner/>)}*/}


            <Button variant="contained" color="primary" onClick={() => {
                //print the value

                //set the value statically

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
                // setBulkJob(true);

                addNotification({message: __("Broadcast scheduled "), type: 'success'});
            }}>
                {__('Schedule Broadcast', 'groundhogg')}
            </Button>

            <Box display={'flex'}>
                <Box>
                    <ScheduleBroadcast/>
                </Box>
                <Box flexGrow={1}>
                    <ListTable
                        items={items}
                        defaultOrderBy={'object_id'}
                        defaultOrder={'desc'}
                        totalItems={totalItems}
                        fetchItems={fetchItems}
                        isRequesting={isRequesting}
                        columns={BroadcastsTableColumns}
                        // onBulkAction={ handleBulkAction }
                        // bulkActions={ tagTableBulkActions }
                        // QuickEdit={ TagsQuickEdit }
                    />
                </Box>
            </Box>
        </Fragment>
    );
}


