import React, { Component } from 'react'
import axios from 'axios'
import ButtonGroup from 'react-bootstrap/ButtonGroup'
import Button from 'react-bootstrap/Button'

import './component.scss'

const { __, _x, _n, _nx } = wp.i18n

export const FunnelStatus = ({ status, onChange }) => {

  const setActive = () => {
    onChange('active')
  }

  const setInactive = () => {

    const message = __(
      'Are you sure you want to deactivate this funnel? Any contacts currently active within the funnel will be paused.',
      'groundhogg')

    if (!confirm(message)) {
      return
    }

    onChange('inactive')
  }

  return (
    <div className={ 'funnel-status' }>
      <ButtonGroup>
        <Button
          onClick={ setActive }
          variant={ status === 'active'
            ? 'primary'
            : 'outline-primary' }
        >
          { __('Active', 'status', 'groundhogg') }
        </Button>
        <Button
          onClick={ setInactive }
          variant={ status === 'active'
            ? 'outline-secondary'
            : 'secondary' }
        >
          { __('Inactive', 'status', 'groundhogg') }
        </Button>
      </ButtonGroup>
    </div>

  )

}