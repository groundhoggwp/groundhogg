import {Fragment, render, useState} from '@wordpress/element'
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
import {addNotification} from "utils";
import { __ } from '@wordpress/i18n'

const notesTableColumn = [
    {
        ID: 'tag_id',
        name: 'ID',
        orderBy: 'tag_id',
        align: 'right',
        cell: ({data}) => {
            return data.ID
        },
    },
    {
        ID: 'name',
        name: 'Name',
        orderBy: 'tag_name',
        align: 'left',
        cell: ({data}) => {
            return <>{data.content}</>
        },
    },
];
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
                user_id : contact.data.user_id
            }
        });
        setNote('')
        addNotification( {message : __( 'Contact note added successfully.' ) , type : 'success' })

    }

    const fetchNotes = (obj) => {
        fetchItems({
            where: {
                object_id: id,
                object_type: 'contact'
            }
        })
        return {}
    }

    return (
        <Fragment>
            <TextField
                id="tag-description"
                label={'Note'}
                multiline
                fullWidth
                size="small"
                rows={3}
                value={ note }
                onChange={(e) => setNote(e.target.value)}
                variant="outlined"
            />

            <Button onClick={addContactNotes}> Add Note </Button>

            <Box flexGrow={1}>
                <ListTable
                    items={items}
                    // defaultOrderBy={'ID'}
                    defaultOrder={'desc'}
                    totalItems={totalItems}
                    fetchItems={fetchNotes}
                    isRequesting={isRequesting}
                    columns={notesTableColumn}

                />
            </Box>


        </Fragment>
    )

}
