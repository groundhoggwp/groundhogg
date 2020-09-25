import React from 'react'
import BottomBar from '../../components/BottomBar/BottomBar'
import { connect } from 'react-redux'
import { number_format } from '../../functions'
import Button from 'react-bootstrap/Button'
import { bulkJobInit } from '../../actions/bulkJobActions'
import {
  fetchContacts,
  resetQuery,
  updateQuery,
} from '../../actions/contactListActions'

const BulkActionBar = ({
  query,
  totalContacts,
  itemsSelected,
  allSelected,
  bulkJobInit,
  fetchContacts,
  updateQuery,
  resetQuery,
}) => {

  const startBulkDeleteContacts = (query, totalItems) => {

    bulkJobInit({
      action: 'gh_delete_contacts',
      actionName: 'Delete contacts...',
      totalItems: totalItems,
      context: {
        query: query,
      },
      onFinish: (result) => {
        updateQuery({
          offset: 0,
        })

        setTimeout(fetchContacts, 500)
      },
    })
  }

  const handleDelete = () => {

    if (allSelected) {

      const newQuery = {
        ...query,
        offset: 0,
        number: -1,
      }

      startBulkDeleteContacts(newQuery, totalContacts)
    }
    else {
      startBulkDeleteContacts({
        include: itemsSelected,
      }, itemsSelected.length)
    }

  }

  return (
    <BottomBar className={ 'contact-bulk-actions' }
               show={ itemsSelected.length > 0 || allSelected }>
      <div className={ 'total-selected' }>
        <span className={ 'num' }>{ allSelected
          ? number_format(totalContacts)
          : number_format(
            itemsSelected.length) }</span> { 'Contacts' }
      </div>
      <div className={ 'actions' }>
        <Button variant={ 'outline-secondary' }>
          { 'Edit' }
        </Button>
        <Button variant={ 'outline-secondary' }>
          { 'Apply / Remove Tags' }
        </Button>
        <Button variant={ 'outline-secondary' }>
          { 'Send Broadcast' }
        </Button>
        <Button variant={ 'outline-secondary' }>
          { 'Export' }
        </Button>
        <Button variant={ 'outline-secondary' }>
          { 'Start Funnel' }
        </Button>
        <Button onClick={ handleDelete } variant={ 'outline-danger' }>
          { 'Delete' }
        </Button>
      </div>
    </BottomBar>
  )
}

export default connect(state => ( {
  query: state.contactList.query,
  totalContacts: state.contactList.total,
  itemsSelected: state.itemSelection.selected,
  allSelected: state.itemSelection.allSelected,
} ), {
  bulkJobInit,
  updateQuery,
  resetQuery,
  fetchContacts,
})(BulkActionBar)