import { DateTime } from 'luxon'
import { useDispatch, useSelect } from '@wordpress/data'
import { EXPORT_STORE_NAME } from 'data/export'
import Tooltip from '@material-ui/core/Tooltip/Tooltip'
import IconButton from '@material-ui/core/IconButton'
import ArrowDownwardIcon from '@material-ui/icons/ArrowDownward'
import RowActions from 'components/core-ui/row-actions'
import DeleteIcon from '@material-ui/icons/Delete'
import React from 'react'
import { Fragment, useState } from '@wordpress/element'
import { ListTable } from 'components/core-ui/list-table/new'
import { CONTACTS_STORE_NAME } from 'data/contacts'
import { Link } from 'react-router-dom'
import { __ } from '@wordpress/i18n'
import SettingsIcon from '@material-ui/icons/Settings'
import { FormFileUpload } from '@wordpress/components'
import { Button } from '@material-ui/core'

const FileTableColumns = [
  {
    ID: 'ID',
    name: 'File Name',
    orderBy: 'file_name',
    align: 'left',
    cell: ({ file_name }) => {
      return file_name
      // return  'HELlO '
    },
  },

  {
    ID: 'date_uploaded',
    name: 'Date Uploaded',
    orderBy: 'date_uploaded',
    align: 'left',
    cell: ({ date_uploaded }) => {
      return DateTime.fromSeconds(date_uploaded).toLocaleString(DateTime.DATETIME_FULL)
      // return  'HELLO'
    }

  },
  {
    ID: 'action',
    name: 'Actions',
    orderBy: '',
    align: '',
    cell: ({ file_url, file_name }) => {

      // const onDownload = (event) => {
      //   window.open(file_url, '_blank')
      // }

      // const { deleteItems } = useDispatch(EXPORT_STORE_NAME)
      //
      return (<>
        <Tooltip title={'Download'}>
          {/*<IconButton aria-label={'Download'} onClick={onDownload}>*/}
            <ArrowDownwardIcon/>
          {/*</IconButton>*/}
        </Tooltip>
        {/*<RowActions*/}
        {/*  onDelete={() => deleteItems([file_name])}*/}
        {/*/>*/}
      </>)
    }

  },
]

export const Files = (props) => {

  let fileList = props.contact.files

  const [upload, setUpload] = useState(null)

  const { files,totalFiles, isRequesting } = useSelect((select) => {
    const store = select(CONTACTS_STORE_NAME)
    return {
      files: store.getContactFiles() ?store.getContactFiles() : []  ,
      totalFiles : store.getTotalFiles()
      // totalItems: store.getTotalItems(),
      // isRequesting: store.isItemsRequesting(),
    }
  }, [])

  const { fetchFiles } = useDispatch(CONTACTS_STORE_NAME)

  //
  // //
  // // const {
  // //   getContact,
  // //   isRequesting,
  // // } = useSelect((select) => {
  // //   const store = select(CONTACTS_STORE_NAME)
  // //
  // //   return {
  // //     // getItem: store.getItem,
  // //     files : store.requestContactFiles (props.contact.ID),
  // //     getContact : console.log(store.getItem (props.contact.ID)),
  // //
  // //   }
  // // }, [])
  //
  //
  //
  // //
  // // const { contact } = useSelect((select) => {
  // //   const store = select(CONTACTS_STORE_NAME)
  // //   return {
  // //     contact: store.getItem(id)
  // //   }
  // // }, [])
  // //
  //
  //
  // /**
  //  * Handle any bulk actions
  //  *
  //  * @param action
  //  * @param selected
  //  * @param setSelected
  //  * @param fetchItems
  //  */
  // const handleBulkAction = ({ action, selected, setSelected, fetchItems }) => {
  //   switch (action) {
  //     case 'delete':
  //       deleteItems(selected.map(item => item.file_name))
  //       setSelected([])
  //       break
  //   }
  // }
  //
  // /**
  //  * Overrides isSelected method for the
  //  *
  //  * @param item
  //  * @param selected
  //  * @returns {boolean}
  //  */
  // const isSelected = ({ item, selected }) => {
  //   if (selected) {
  //     return selected.filter(__item => __item.file_name === item.file_name).length > 0
  //   }
  //   return true
  // }
  //
  // /**
  //  *  Overrides core list table method which looks for ID instead of file_name
  //  *
  //  * @param item
  //  * @param setSelected
  //  * @param selected
  //  */
  // const handleSelectItem = ({ item, setSelected, selected }) => {
  //   if (isSelected({ item, selected })) {
  //     // Item is selected, so remove it
  //     setSelected(selected.filter(__item => __item.file_name !== item.file_name))
  //   } else {
  //     // Add it to the selected array
  //     setSelected([...selected, item])
  //   }
  // }
  //
  //




  const handleFileUpload = () => {

  }

  const fetchFileList  = ( obj ) =>{
    return  fetchFiles(props.contact.ID , obj );
  }

  return (
    <Fragment>

      <FormFileUpload
        accept="CSV/*"
        onChange={(event) => {
          setUpload(event.target.files[0])
        }}
      >
        Upload
      </FormFileUpload>

      <Button onClick={handleFileUpload}> Upload </Button>
      <ListTable
        items={files}
        // defaultOrderBy={'ID'}
        // defaultOrder={'desc'}
        totalItems={totalFiles}
        fetchItems={fetchFileList}
        columns={FileTableColumns}
        // isRequesting={isRequesting}
        // onBulkAction={handleBulkAction}
        // bulkActions={exportBulkActions}
        // onSelectItem={handleSelectItem}
        // isCheckboxSelected={isSelected}
      />

    </Fragment>
  )
}