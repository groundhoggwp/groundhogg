import React from 'react'
import { Navbar, Spinner } from 'react-bootstrap'
import { ExitButton } from './../ExitButton/ExitButton'

import './component.scss'
import { FunnelStatus } from './FunnelStatus/FunnelStatus'
import { FunnelAction } from './FunnelActions'
import { TitleInput } from '../TitleInput'
import moment from 'moment'

function exit () {
  window.location = ghEditor.exit
}

const { __, _x, _n, _nx } = wp.i18n

export function Header ({ data, updateFunnel, isSaving }) {

  const updateTitle = (newTitle) => {
    updateFunnel({ title: newTitle })
  }

  const updateStatus = (newStatus) => {
    updateFunnel({ status: newStatus })
  }

  return (
    <Navbar bg="white" expand="sm" sticky="top">
      <div className={ 'big-g logo' }>
        <img
          src={ ghEditor.assets.bigG }
          alt={ 'big-g' }
        />
      </div>
      <Navbar.Brand className={'main-brand'}>
        <TitleInput
          title={ data.title }
          className={ 'funnel-title' }
          onChange={ updateTitle }
        />
      </Navbar.Brand>
      <Navbar.Toggle aria-controls="basic-navbar-nav"/>
      <Navbar.Collapse id="basic-navbar-nav"
                       className="justify-content-end groundhogg-nav">
        {
          isSaving ?
            <div className={ 'saving' }>
              { __('Saving', 'groundhogg') }
              <span>.</span><span>.</span><span>.</span>
            </div> : <div className={ 'last-updated' }>
              { __('Last updated', 'groundhogg') + ' ' +
              moment(data.last_updated).fromNow() }
            </div>
        }
        <FunnelStatus
          status={ data.status }
          onChange={ updateStatus }
          isDisabled={isSaving}
        />
        <FunnelAction/>
      </Navbar.Collapse>
      <ExitButton onExit={ exit }/>
    </Navbar>
  )
}

