import React from 'react'
import { Dashicon } from '../Dashicon/Dashicon'
import { Dropdown, DropdownButton } from 'react-bootstrap'

function redirect (location) {
  window.location = location
}

const { __, _x, _n, _nx } = wp.i18n

export const FunnelAction = () => {

  const onSelect = (key, e) => {

    switch (key) {
      case 'reporting':
        redirect(ghEditor.actions.reporting)
        break
      case 'settings':
      case 'add-contacts':
      case 'export':
        redirect(ghEditor.actions.export_url)
        break
      case 'share-link':
        prompt(__('Copy this link', 'groundhogg'), ghEditor.actions.export_url)
        break
      case 'delete':
        if (confirm(
          __('Are you sure you want to delete this funnel?', 'groundhogg'))) {
          redirect(ghEditor.actions.delete)
        }

        break
    }
  }

  return (
    <DropdownButton
      id={ 'funnel-actions' }
      variant={ 'outline-secondary' }
      title={ <span className={ 'funnel-actions-span' }>
        <Dashicon icon={ 'admin-tools' }/> { _x('Actions', 'funnel action',
        'groundhogg') }
      </span>
      }
      className={ 'funnel-actions' }
      onSelect={ onSelect }
    >
      <Dropdown.Item eventKey="reporting">
        <Dashicon icon={ 'chart-area' }/> { _x('Reporting', 'funnel action',
        'gronudhogg') }
      </Dropdown.Item>
      <Dropdown.Item eventKey="settings">
        <Dashicon icon={ 'admin-generic' }/> { _x('Settings', 'funnel action',
        'groundhogg') }
      </Dropdown.Item>
      <Dropdown.Item eventKey="add-contacts">
        <Dashicon icon={ 'groups' }/> { _x('Add Contacts', 'funnel action',
        'groundhogg') }
      </Dropdown.Item>
      <Dropdown.Divider/>
      <Dropdown.Item eventKey="export">
        <Dashicon icon={ 'download' }/> { _x('Export', 'funnel action',
        'groundhogg') }
      </Dropdown.Item>
      <Dropdown.Item eventKey="share-link">
        <Dashicon icon={ 'share' }/> { _x('Share Link', 'funnel action',
        'groundhogg') }
      </Dropdown.Item>
      <Dropdown.Divider/>
      <Dropdown.Item eventKey="delete" className={ 'text-danger' }>
        <Dashicon icon={ 'trash' }/> { __('Delete') }
      </Dropdown.Item>
    </DropdownButton>
  )
}