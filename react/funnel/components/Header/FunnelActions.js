import React from 'react'
import { Dashicon } from '../Dashicon/Dashicon'
import { Dropdown, DropdownButton } from 'react-bootstrap'

export const FunnelAction = () => {

  const onSelect = (key, e) => {

  }

  return (
    <DropdownButton
      id={ 'funnel-actions' }
      variant={ 'outline-secondary' }
      title={
        <span>
            <Dashicon icon={ 'admin-tools' }/>
          { ' Actions' }
          </span>
      }
      className={'funnel-actions'}
      onSelect={ onSelect }
    >
      <Dropdown.Item eventKey="reporting">
        <Dashicon icon={ 'chart-area' }/> { 'Reporting' }
      </Dropdown.Item>
      <Dropdown.Item eventKey="settings">
        <Dashicon icon={ 'admin-generic' }/> { 'Settings' }
      </Dropdown.Item>
      <Dropdown.Item eventKey="add-contacts">
        <Dashicon icon={ 'groups' }/> { 'Add Contacts' }
      </Dropdown.Item>
      <Dropdown.Divider/>
      <Dropdown.Item eventKey="export">
        <Dashicon icon={ 'download' }/> { 'Export' }
      </Dropdown.Item>
      <Dropdown.Item eventKey="share-link">
        <Dashicon icon={ 'share' }/> { 'Share Link' }
      </Dropdown.Item>
      <Dropdown.Divider/>
      <Dropdown.Item eventKey="delete" className={ 'text-danger' }>
        <Dashicon icon={ 'trash' }/> { 'Delete' }
      </Dropdown.Item>
    </DropdownButton>
  )
}