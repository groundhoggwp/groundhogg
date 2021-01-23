import React from 'react'
import { OverlayTrigger, Tooltip as BSTooltip } from 'react-bootstrap'
import { Dashicon } from '../Dashicon/Dashicon'

import './component.scss';

export const Tooltip = ({ id, placement, content }) => {
  return (
    <OverlayTrigger
      key={ id }
      placement={ placement }
      overlay={ <BSTooltip id={ `tooltip-${ id }` }>
        { content }
      </BSTooltip> }
    >
      <span className={ 'tooltip-wrap'}>
        <Dashicon icon={ 'info' }/>
      </span>
    </OverlayTrigger>
  )
}

Tooltip.defaultProps = {
  placement: 'right',
  id: 'mytooltip',
  content: ''
}