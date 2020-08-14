import React from 'react'
import BottomBar from '../../components/BottomBar/BottomBar'
import { connect } from 'react-redux'
import { number_format } from '../../functions'
import Button from 'react-bootstrap/Button'
import { bulkJobInit } from '../../actions/bulkJobActions'

const BulkActionBar = ({ totalContacts, itemsSelected, allSelected, bulkJobInit }) => {

  const handleDelete = () => {
    bulkJobInit( {
      action: 'gh_delete_contacts',
      actionName: 'Delete contacts...',
      items: itemsSelected,
    } )
  }

  return (
    <BottomBar className={ 'contact-bulk-actions' }
               show={ itemsSelected.length > 0 || allSelected }>
      <div className={ 'total-selected' }>
        <span className={'num'}>{ allSelected ? number_format(totalContacts) : number_format(
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
        <Button onClick={handleDelete} variant={ 'outline-danger' }>
          { 'Delete' }
        </Button>
      </div>
    </BottomBar>
  )
}

export default connect(state => ( {
  totalContacts: state.contactList.total,
  itemsSelected: state.itemSelection.selected,
  allSelected: state.itemSelection.allSelected,
} ), {
  bulkJobInit
})(BulkActionBar)