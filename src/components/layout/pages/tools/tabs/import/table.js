import TabPanel from 'components/core-ui/tab-panel'
import { SettingsSection } from 'components/layout/pages/settings/settings-section'
import { addFilter, applyFilters } from '@wordpress/hooks'
import { Fragment, render } from '@wordpress/element'
import { __ } from '@wordpress/i18n'
import ReportPanel from 'components/layout/pages/reports/report-panel'
import { useDispatch, useSelect } from '@wordpress/data'
import { EXPORT_STORE_NAME } from 'data/export'
import Box from '@material-ui/core/Box'
import { ListTable } from 'components/core-ui/list-table/new'
import React from 'react'
import { DateTime } from 'luxon'
import Tooltip from '@material-ui/core/Tooltip/Tooltip'
import IconButton from '@material-ui/core/IconButton'
import RowActions from 'components/core-ui/row-actions'
import DeleteIcon from '@material-ui/icons/Delete'
import { IMPORT_STORE_NAME } from 'data/import'
import InputIcon from '@material-ui/icons/Input'
import Button from '@material-ui/core/Button'
import { useHistory, useRouteMatch } from 'react-router-dom'

const importTableColumns = [
  {
    ID: 'ID',
    name: 'File Name',
    orderBy: 'file_name',
    align: 'left',
    cell: ({ file_name }) => {
      return file_name
    },
  },
  {
    ID: 'rows',
    name: 'Rows',
    orderBy: '',
    align: 'left',
    cell: ({ rows }) => {
      return rows
    }

  },
  {
    ID: 'timestamp',
    name: 'Timestamp',
    orderBy: 'timestamp',
    align: 'left',
    cell: ({ timestamp }) => {
      return DateTime.fromSeconds(timestamp).toLocaleString(DateTime.DATETIME_FULL)
    }

  },
  {
    ID: 'action',
    name: 'Actions',
    orderBy: '',
    align: '',
    cell: ({ file_url, file_name, file_path, rows, timestamp }) => {

      let history = useHistory()
      let { path } = useRouteMatch()

      const onImport = (event) => {
        history.push({
          pathname: path + '/steps',
          file: {
            file_name: file_name,
            file_path: file_path,
            file_url: file_url,
            rows: rows,
            timestamp: timestamp

          }
        })

      }

      const { deleteItems } = useDispatch(IMPORT_STORE_NAME)

      return (<>
        <Tooltip title={'Import'}>
          <IconButton aria-label={'Import'} color={'primary'} onClick={onImport}>
            <InputIcon/>
          </IconButton>
        </Tooltip>
        <RowActions
          onDelete={() => {deleteItems([file_name])}}
        />
      </>)
    }

  },
]

const importBulkActions = [
  {
    title: 'Delete',
    action: 'delete',
    icon: <DeleteIcon/>,
  },
]

export const Table = (props) => {
  //displaying table based on the file

  const { items, totalItems, isRequesting } = useSelect((select) => {
    const store = select(IMPORT_STORE_NAME)
    return {
      items: store.getItems(),
      totalItems: store.getTotalItems(),
      isRequesting: store.isItemsRequesting(),
    }
  }, [])

  const { fetchItems, deleteItems } = useDispatch(IMPORT_STORE_NAME)

  /**
   * Handle any bulk actions
   *
   * @param action
   * @param selected
   * @param setSelected
   * @param fetchItems
   */
  const handleBulkAction = ({ action, selected, setSelected, fetchItems }) => {
    switch (action) {
      case 'delete':
        deleteItems(selected.map(item => item.file_name))
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
  const isSelected = ({ item, selected }) => {
    if (selected) {
      return selected.filter(__item => __item.file_name === item.file_name).length > 0
    }
    return true
  }

  /**
   *  Overrides core list table method which looks for ID instead of file_name
   *
   * @param item
   * @param setSelected
   * @param selected
   */
  const handleSelectItem = ({ item, setSelected, selected }) => {
    if (isSelected({ item, selected })) {
      // Item is selected, so remove it
      setSelected(selected.filter(__item => __item.file_name !== item.file_name))
    } else {
      // Add it to the selected array
      setSelected([...selected, item])
    }
  }

  let history = useHistory()

  let { path } = useRouteMatch()

  return (
    <Fragment>
      <h1> Import </h1>
      <Button variant="contained" color="secondary" onClick={() => {
        history.push(path + '/steps')
      }}>
        {__('Import', 'groundhogg')}
      </Button>
      <Box display={'flex'}>

        <Box flexGrow={1}>
          <ListTable
            items={items}
            defaultOrderBy={'timestamp'}
            defaultOrder={'desc'}
            totalItems={totalItems}
            fetchItems={fetchItems}
            isRequesting={isRequesting}
            columns={importTableColumns}
            onBulkAction={handleBulkAction}
            bulkActions={importBulkActions}
            onSelectItem={handleSelectItem}
            isCheckboxSelected={isSelected}

          />
        </Box>
      </Box>
    </Fragment>
  )

}

