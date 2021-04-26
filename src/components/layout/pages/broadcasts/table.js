/**
 * External dependencies
 */
import {Fragment, useState, getState} from '@wordpress/element';
import {useSelect, useDispatch} from '@wordpress/data';
import {__} from '@wordpress/i18n';
import Button from '@material-ui/core/Button';


/**
 * Internal dependencies
 */
import {BROADCASTS_STORE_NAME} from 'data/broadcasts';
import Box from "@material-ui/core/Box";
import ListTable from "components/core-ui/list-table/";
import {Link} from "react-router-dom";
import {Tooltip} from "@material-ui/core";
import Chip from "@material-ui/core/Chip";
import SendIcon from '@material-ui/icons/Send';
import DraftsIcon from '@material-ui/icons/Drafts';
import AssignmentTurnedInIcon from '@material-ui/icons/AssignmentTurnedIn';

const BroadcastsTableColumns = [
    {
        ID: 'object_id',
        name: 'ID',
        orderBy: 'object_id',
        align: 'left',
        cell: ({data}) => {
            return data.object_id
        },
    },
    {
        ID: 'title',
        name: 'Email/SMS',
        orderBy: '',
        align: 'left',
        cell: ({title}) => {
            return title;
        }

    },
    {
        ID: 'Schedule',
        name: 'Schedule By',
        orderBy: '',
        align: 'left',
        cell: ({user}) => {
            return user.data.display_name
        }

    },
    {
        ID: 'run_date',
        name: 'Scheduled Run Date',
        orderBy: '',
        align: 'left',
        cell: ({data}) => {
            return data.send_time // todo format using the decided library
        }

    },

    {
        ID: 'status',
        name: 'Status',
        orderBy: 'status',
        align: 'left',
        cell: ({data}) => {
            return <Chip variant="outlined" color="primary" size="small" label={data.status}/>
        }

    },
    // {
    //     ID: 'send_to',
    //     name: 'Sending TO',
    //     orderBy: '',
    //     align: 'left',
    //     cell: ({data}) => {
    //         return 'Hello'
    //     }
    //
    // },

    {
        ID: 'stats',
        name: 'Stats',
        orderBy: '',
        align: 'left',
        cell: ({report, data}) => {

            if (data.status === 'scheduled') {
                return '-';
            }
            return <>
                <Tooltip title={'Sent'}>
                    <Chip
                        icon={<SendIcon/>}
                        label={report.sent}
                    />
                </Tooltip>
                <br/>
                <Tooltip title={'Opened'}>
                    <Chip
                        icon={<DraftsIcon/>}
                        label={report.opened}
                    />
                </Tooltip>
                <br/>
                <Tooltip title={'Clicked'}>
                    <Chip
                        icon={<AssignmentTurnedInIcon/>}
                        label={report.clicked}
                    />
                </Tooltip>
            </>
        }
    },
    {
        ID: 'date_scheduled',
        name: 'Date Scheduled',
        orderBy: 'date_scheduled',
        align: 'left',
        cell: ({data}) => {
            return data.date_scheduled; // Need to resolve this to proper format
        }

    },
];

//
// const ScheduleBroadcast = () => {
//
//     const tagAppliedChange = (value) =>{
//         //
//         // let  arr= [];
//         // // if(!empty(value)) {
//         // //     let arr = value.map( (item )=> {return item.value});
//         // // }
//         // setTagApplied(arr);
//     }
//
//
//     const tagRemoveChange = (value) =>{
//         //
//         // let  arr= [];
//         // // if(!empty(value)) {
//         // //     let arr = value.map( (item )=> {return item.value});
//         // // }
//         // setTagApplied(arr);
//     }
//
//     // Builidng the Form to Schedule Boroadcast
//     return (
//
//         <Fragment>
//             <Card >
//                 Applied Tag
//                 <TagPicker onChange={tagAppliedChange} value={tagApplied}/>
//                 Remove Tag
//                 <TagPicker onChange={tagRemoveChange} value={tagApplied}/>
//             </Card>
//         </Fragment>
//
//     );
//
//
// }

export default (props) => {

    // getting all the state variables
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


    return (
        <Fragment>
            <Link to={`/broadcasts/schedule`}>
                {__('Schedule Broadcast','groundhogg')}
            </Link>
            <Box display={'flex'}>
                <Box>
                    {/*<ScheduleBroadcast/>*/}
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
