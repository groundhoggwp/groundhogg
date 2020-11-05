import {addFilter, applyFilters} from "@wordpress/hooks";
import {Fragment, render} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import {useDispatch, useSelect} from "@wordpress/data";
import Box from "@material-ui/core/Box";
import {ListTable} from "components/core-ui/list-table/new";
import {EXPORT_STORE_NAME} from "data/export";
import RowActions from "components/core-ui/row-actions";
import Tooltip from "@material-ui/core/Tooltip/Tooltip";
import IconButton from "@material-ui/core/IconButton";
import React from "react";
import ArrowDownwardIcon from '@material-ui/icons/ArrowDownward';
import DeleteIcon from "@material-ui/icons/Delete";
import {DateTime} from 'luxon';

const ExportTableColumns = [
    {
        ID: 'ID',
        name: 'File Name',
        orderBy: 'file_name',
        align: 'left',
        cell: ({file_name}) => {
            return file_name;
        },
    },
    {
        ID: 'rows',
        name: 'Rows',
        orderBy: '',
        align: 'left',
        cell: ({rows}) => {
            return rows;
        }

    },
    {
        ID: 'timestamp',
        name: 'Timestamp',
        orderBy: 'timestamp',
        align: 'left',
        cell: ({timestamp}) => {
            return DateTime.fromSeconds(  timestamp ).toLocaleString(DateTime.DATETIME_FULL);
        }

    },
    {
        ID: 'action',
        name: 'Actions',
        orderBy: '',
        align: '',
        cell: ({file_url, file_name}) => {

            const onDownload = (event) => {
                window.open(file_url, '_blank');
            }

            const {deleteItems} = useDispatch(EXPORT_STORE_NAME);

            return (<>
                <Tooltip title={'Download'}>
                    <IconButton aria-label={'Download'} onClick={onDownload}>
                        <ArrowDownwardIcon/>
                    </IconButton>
                </Tooltip>
                <RowActions
                    onDelete={() => deleteItems([file_name])}
                />
            </>)
        }

    },
];

const exportBulkActions = [
    {
        title: 'Delete',
        action: 'delete',
        icon: <DeleteIcon/>,
    },
];

export const Export = (props) => {
    // use

    const {items, totalItems, isRequesting} = useSelect((select) => {
        const store = select(EXPORT_STORE_NAME);
        return {
            items: store.getItems(),
            totalItems: store.getTotalItems(),
            isRequesting: store.isItemsRequesting(),
        }
    }, []);

    const {fetchItems, deleteItems} = useDispatch(EXPORT_STORE_NAME);

    /**
     * Handle any bulk actions
     *
     * @param action
     * @param selected
     * @param setSelected
     * @param fetchItems
     */
    const handleBulkAction = ({action, selected, setSelected, fetchItems}) => {
        switch (action) {
            case 'delete':
                deleteItems(selected.map(item => item.file_name ) )
                setSelected([])
                break
        }
    }

    /**
     * Overrides isSelected method for the
     *
     * @param item
     * @param selected
     * @returns {boolean}
     */
    const isSelected = ( { item ,selected } ) => {
        if (selected) {
            return selected.filter(__item => __item.file_name === item.file_name).length > 0
        }
        return true;
    }

    /**
     *  Overrides core list table method which looks for ID instead of file_name
     *
     * @param item
     * @param setSelected
     * @param selected
     */
    const handleSelectItem = ( {item, setSelected ,selected}) => {
        if (isSelected({ item ,selected })) {
            // Item is selected, so remove it
            setSelected(selected.filter(__item => __item.file_name !== item.file_name))
        } else {
            // Add it to the selected array
            setSelected([...selected, item])
        }
    }

    return (
        <Fragment>
            <h1> EXPORT </h1>
            <Box display={'flex'}>

                <Box flexGrow={1}>
                    <ListTable
                        items={items}
                        defaultOrderBy={'timestamp'}
                        defaultOrder={'desc'}
                        totalItems={totalItems}
                        fetchItems={fetchItems}
                        isRequesting={isRequesting}
                        columns={ExportTableColumns}
                        onBulkAction={handleBulkAction}
                        bulkActions={exportBulkActions}
                        onSelectItem={handleSelectItem}
                        isCheckboxSelected = {isSelected}

                    />
                </Box>
            </Box>
        </Fragment>
    );
}


//Hook to push content into the page
addFilter('groundhogg.tools.tabs', 'groundhogg', (tabs) => {
    tabs.push({
        title: __("Export", 'groundhogg'),
        path: '/export',
        description: __('First Description', 'groundhogg'),
        component: (classes) => {
            return (
                <Export/>
            )
        }
    });
    return tabs;

}, 10);