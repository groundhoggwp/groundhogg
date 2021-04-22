import {Fragment, render, useCallback, useEffect, useState} from '@wordpress/element'
import {ListTable} from 'components/core-ui/list-table/'
import React from 'react'
import TextField from "@material-ui/core/TextField";
import Paper from "@material-ui/core/Paper";
import {useDispatch, useSelect} from "@wordpress/data";
import {CONTACTS_STORE_NAME, TAGS_STORE_NAME} from "data";
import {NOTES_STORE_NAME} from "data/notes";
import {useParams} from "react-router-dom";
import Box from "@material-ui/core/Box";
import LocalOfferIcon from "@material-ui/icons/LocalOffer";
import {Button, withStyles,} from "@material-ui/core";
import {addNotification, useKeyPress} from "utils";
import {__} from '@wordpress/i18n'
import {ListView} from "components/core-ui/list-view";
import SettingsIcon from "@material-ui/icons/Settings";
import RowActions from "components/core-ui/row-actions";
import {makeStyles} from "@material-ui/core/styles";
import Divider from "@material-ui/core/Divider";
import Tooltip from "@material-ui/core/Tooltip/Tooltip";
import IconButton from "@material-ui/core/IconButton";
import EditIcon from "@material-ui/icons/Edit";
import FileCopyIcon from "@material-ui/icons/FileCopy";


import {createMuiTheme, ThemeProvider} from '@material-ui/core/styles';

import MuiTableCell from "@material-ui/core/TableCell";
import * as PropTypes from "prop-types";
import DeleteIcon from "@material-ui/icons/Delete";
import Grid from "@material-ui/core/Grid";

const theme = createMuiTheme({
    overrides: {
        MuiTableCell: {
            root: {
                border: '1px solid #ccd0d4',
                backgroundColor: '#FFFFFF',
                borderRadius: 1,
// margin: 10px 0;
//                 borderRadius: 3,
//                 border: 1,
                height: 48,
                padding: '0 30px',
                borderBottom: "none",

            },
        },
        MuiButtonBase: {},
        MuiIconButton: {
            sizeSmall: {

                flot: 'right !important'
            }
        }
    },
    '& button, & h1': {
        float: 'right'
    }


});

const useStyles = makeStyles((theme) => ({
    content: {
        background: '#FFFFFF',
        position: 'relative',
        zIndex: '3',
        borderRadius: '10px',

    },
    actions: {
        float: 'right',
        color: '#aaa',
        margin: '1px 1px 10px 10px',
        width: '100%',
        // fontSize : 10
    },

    '& button,h1': {
        float: 'right'
    }

}))


const notesTableColumn = [

    // {
    //     ID: 'content',
    //     name: 'Content',
    //     orderBy: '',
    //     align: 'left',
    //     cell: ({data}) => {
    //         return <>{data.content}</>
    //
    //     },
    // },
    //
    // {
    //     ID: 'date',
    //     name: 'Date',
    //     orderBy: '',
    //     align: 'left',
    //     cell: ({data}) => {
    //         return <>{data.date_created}</>
    //     },
    // },
    {
        ID: 'actions',
        name: <span><SettingsIcon/> {'Actions'}</span>,
        align: 'left',
        cell: ({ID, data, openQuickEdit}) => {


            const classes = useStyles()

            const {deleteItem} = useDispatch(NOTES_STORE_NAME)

            const handleEdit = () => {
                openQuickEdit()
            }

            const handleDelete = (ID) => {
                deleteItem(ID)
            }

            return (
                <Fragment>
                    {/*<div className={classes.content}>*/}


                    <Grid container>
                        <Grid item className={classes.actions}>
                            <div align={'right'}>
                                {data.date_created}
                                <Tooltip title={'Edit'}>
                                    <IconButton size={'small'} style={{flot: 'right !important'}}
                                                aria-label={'Edit item'} onClick={openQuickEdit}>
                                        <EditIcon fontSize={'small'} fontVariant={'small'}/>
                                    </IconButton>
                                </Tooltip>
                                <Tooltip title={'Delete'}>
                                    <IconButton style={{flot: 'right'}} size={'small'} aria-label={'Delete item'}
                                                onClick={() => handleDelete(ID)}>
                                        <DeleteIcon fontSize={'small'} fontVariant={'small'}/>
                                    </IconButton>
                                </Tooltip>
                            </div>
                        </Grid>


                        <Grid item> {data.content}</Grid>

                    </Grid>


                </Fragment>
            )
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
const NotesQuickEdit = ({ID, data, exitQuickEdit}) => {

    const {updateItem} = useDispatch(NOTES_STORE_NAME)
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
    const handleOnKeydown = ({keyCode}) => {
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
        <Box display={'flex'} justifyContent={'space-between'}>
            <Box flexGrow={2}>
                <TextField
                    id="content"
                    label={'Content'}
                    multiline
                    fullWidth
                    rows={2}
                    value={tempState && tempState.content}
                    onChange={(e) => handleOnChange(
                        {content: e.target.value})}
                    variant="outlined"
                />
            </Box>
            <Box flexGrow={1}>
                <Box display={'flex'} justifyContent={'flex-end'}>
                    <Button variant="contained" color="primary" onClick={commitChanges}>
                        {'Save Changes'}
                    </Button>
                    <Button variant="contained" onClick={exitQuickEdit}>
                        {'Cancel'}
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
            <ThemeProvider theme={theme}>
                <ListTable
                    items={items}
                    // defaultOrderBy={'ID'}
                    defaultOrder={'desc'}
                    totalItems={totalItems}
                    fetchItems={fetchNotes}
                    isRequesting={isRequesting}
                    columns={notesTableColumn}
                    QuickEdit={NotesQuickEdit}
                    isCheckboxHidden={true}
                    isHeaderHidden={true}
                    isToolbarHidden={true}

                />

            </ThemeProvider>
            {/*</Box>*/}

            {/*<ListView items={items}*/}
            {/*          fetchItems={fetchNotes}*/}
            {/*          DisplayRecord={DisplayRecord}*/}
            {/*          // defaultOrderBy={'ID'}*/}
            {/*          defaultOrder={'desc'}/>*/}
        </Fragment>
    )

}
