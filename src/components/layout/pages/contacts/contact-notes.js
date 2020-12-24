import {Fragment, render, useCallback, useEffect, useState} from '@wordpress/element'
import {ListTable} from 'components/core-ui/list-table/new'
import React from 'react'
import TextField from "@material-ui/core/TextField";
import Paper from "@material-ui/core/Paper";
import {useDispatch, useSelect} from "@wordpress/data";
import {CONTACTS_STORE_NAME, TAGS_STORE_NAME} from "data";
import {NOTES_STORE_NAME} from "data/notes";
import {useParams} from "react-router-dom";
import Box from "@material-ui/core/Box";
import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import {Button} from "@material-ui/core";
import {addNotification, useKeyPress} from "utils";
import {__} from '@wordpress/i18n'
import {ListView} from "components/core-ui/list-view";
import SettingsIcon from "@material-ui/icons/Settings";
import RowActions from "components/core-ui/row-actions";

const notesTableColumn = [

    {
        ID: 'content',
        name: 'Content',
        orderBy: '',
        align: 'left',
        cell: ({data}) => {
            return <>{data.content}</>
        },
    },

    {
        ID: 'date',
        name: 'Date',
        orderBy: '',
        align: 'left',
        cell: ({data}) => {
            return <>{data.date_created}</>
        },
    },
    {
        ID: 'actions',
        name: <span><SettingsIcon/> { 'Actions' }</span>,
        align: 'right',
        cell: ({ ID, data, openQuickEdit }) => {

            const { deleteItem } = useDispatch(NOTES_STORE_NAME)

            const handleEdit = () => {
                openQuickEdit()
            }

            const handleDelete = (ID) => {
                deleteItem(ID)
            }

            return <>
                <RowActions
                    onEdit={ openQuickEdit }
                    onDelete={ () => handleDelete(ID) }
                />
            </>
        },
    },

];


/**
 * Handle the table quick edit
 *
 * @param ID
 * @param data
 * @param exitQuickEdit
 * @returns {*}
 * @constructor
 */
const NotesQuickEdit = ({ ID, data, exitQuickEdit }) => {

    const { updateItem } = useDispatch(NOTES_STORE_NAME)
    const [tempState, setTempState] = useState({
        ...data,
    })

    // Exit quick edit
    useKeyPress(27, null, () => {
        exitQuickEdit()
    })

    /**
     * Handle pressing enter in the tag name
     *
     * @param keyCode
     */
    const handleOnKeydown = ({ keyCode }) => {
        switch (keyCode) {
            case 13:
                commitChanges()
        }
    }

    /**
     * Store the changes in a temp state
     *
     * @param atts
     */
    const handleOnChange = (atts) => {
        setTempState({
            ...tempState,
            ...atts,
        })
    }

    /**
     * Commit the changes
     */
    const commitChanges = () => {
        updateItem(ID, {
            data: tempState,
        })
        exitQuickEdit()
    }

    return (
        <Box display={ 'flex' } justifyContent={ 'space-between' }>
            <Box flexGrow={ 2 }>
                <TextField
                    id="content"
                    label={ 'Content' }
                    multiline
                    fullWidth
                    rows={ 2 }
                    value={ tempState && tempState.content }
                    onChange={ (e) => handleOnChange(
                        { content: e.target.value }) }
                    variant="outlined"
                />
            </Box>
            <Box flexGrow={ 1 }>
                <Box display={ 'flex' } justifyContent={ 'flex-end' }>
                    <Button variant="contained" color="primary" onClick={ commitChanges }>
                        { 'Save Changes' }
                    </Button>
                    <Button variant="contained" onClick={ exitQuickEdit }>
                        { 'Cancel' }
                    </Button>
                </Box>
            </Box>
        </Box>
    )
}



export const ContactNotes = ({contact}) => {


    //Get notes from the db


    let {id} = useParams()

    const [note, setNote] = useState('');

    const {items, totalItems, isRequesting} = useSelect((select) => {
        const store = select(NOTES_STORE_NAME)
        return {
            items: store.getItems(),
            totalItems: store.getTotalItems(),
            isRequesting: store.isItemsRequesting(),
        }
    }, [])


    const {fetchItems, createItem} = useDispatch(NOTES_STORE_NAME)

    // display notes

    // style notes

    // add notes

    const addContactNotes = () => {
        createItem({
            data: {
                object_id: parseInt(id),
                context: "user",
                content: note,
                object_type: "contact",
                user_id: contact.data.user_id
            }
        });
        setNote('')
        addNotification({message: __('Contact note added successfully.'), type: 'success'})

    }

    const fetchNotes = (obj) => {
        fetchItems({
            ...obj,
            ...{
                where: {
                    object_id: id,
                    object_type: 'contact'
                }
            }
        })
        // fetchItems(
        //     {
        //         where: {
        //             object_id: id,
        //             object_type: 'contact'
        //         }
        //     }
        // )
        return {}
    }

    const DisplayRecord = ({index, data}) => {

        return <h1>{data.data.content}</h1>

    }

    //
    // table

    return (
        <Fragment>
            <TextField
                id="tag-description"
                label={'Note'}
                multiline
                fullWidth
                size="small"
                rows={3}
                value={note}
                onChange={(e) => setNote(e.target.value)}
                variant="outlined"
            />

            <Button onClick={addContactNotes}> Add Note </Button>

            {/*<Box flexGrow={1}>*/}
                <ListTable
                    items={items}
                    // defaultOrderBy={'ID'}
                    defaultOrder={'desc'}
                    totalItems={totalItems}
                    fetchItems={fetchNotes}
                    isRequesting={isRequesting}
                    columns={notesTableColumn}
                    QuickEdit={ NotesQuickEdit }

                />
            {/*</Box>*/}

            {/*<ListView items={items}*/}
            {/*          fetchItems={fetchNotes}*/}
            {/*          DisplayRecord={DisplayRecord}*/}
            {/*          // defaultOrderBy={'ID'}*/}
            {/*          defaultOrder={'desc'}/>*/}
        </Fragment>
    )

}
