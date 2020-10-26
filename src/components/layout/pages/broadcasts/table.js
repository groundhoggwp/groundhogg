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
import {addNotification} from "utils/index";
import Box from "@material-ui/core/Box";
import {ListTable} from "components/core-ui/list-table/new";
import {TagPicker} from "components/core-ui/tag-picker";
import {Card} from "@material-ui/core";
import Single from "components/layout/pages/funnels/single";
import {Link, Route, useRouteMatch} from "react-router-dom";


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

            {/*    // set variables for the request*/}
            {/*    setEmailOrSmsId(474);*/}
            {/*    setTags( [168] );*/}
            {/*    setSendNow(true);*/}
            {/*    setType('email');*/}

            {/*}}>*/}
            {/*    {__('Cancel Broadcast', 'groundhogg')}*/}
            {/*</Button>*/}

            {/*{(isUpdating) && (<Spinner/>)}*/}
            <Link to={ `/broadcasts/schedule` }>
                Schedule Broadcast
            </Link>

            <Button variant="contained" color="primary" onClick={() => {

                //open a new page



                //print the value

                //set the value statically

                // setBulkJob(true);
                <Link to={ `/broadcasts/schedule` }>
                    <img className={classes.contactRowImage} src={ data.gravatar } />
                </Link>

                // addNotification({message: __("Broadcast scheduled "), type: 'success'});
            }}>
                {__('Schedule Broadcast', 'groundhogg')}
            </Button>

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


