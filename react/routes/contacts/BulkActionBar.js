import React from 'react'
import BottomBar from '../../components/BottomBar/BottomBar'
import { connect } from 'react-redux'
import { number_format } from '../../functions'
import { number } from 'prop-types'
import Button from 'react-bootstrap/Button'

const BulkActionBar = ({totalContacts, itemsSelected, allSelected}) => {

  return (
    <BottomBar className={ 'contact-bulk-actions' } show={itemsSelected.length > 0 || allSelected }>
      <div className={'total-selected'}>
        { allSelected && number_format( totalContacts ) }
        { ! allSelected && number_format( itemsSelected.length ) }
      </div>
      <div className={'actions'}>
        <Button variant={'outline-secondary'}>
          { 'Edit' }
        </Button>
        <Button variant={'outline-secondary'}>
          { 'Send Broadcast' }
        </Button>
        <Button variant={'outline-secondary'}>
          { 'Export' }
        </Button>
        <Button variant={'outline-secondary'}>
          { 'Add to a funnel' }
        </Button>
        <Button variant={'outline-danger'}>
          { 'Delete' }
        </Button>
      </div>
    </BottomBar>
  )
}

export default connect(state => ({
  totalContacts: state.contactList.total,
  itemsSelected: state.itemSelection.selected,
  allSelected: state.itemSelection.allSelected,
}), null )(BulkActionBar)